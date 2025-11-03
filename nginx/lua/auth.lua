local http = require "resty.http"
local cjson = require "cjson.safe"
local _M = {}

-- helper: get env variable
local function get_env(name, default)
  local v = os.getenv(name)
  if v == nil or v == "" then
    return default
  end
  return v
end

local AUTH_URL = get_env("AUTH_SERVICE_URL", "http://auth-service:8000/api/auth/check-token")
local CACHE_TTL = tonumber(get_env("AUTH_CACHE_TTL", "300"))

local auth_cache = ngx.shared.auth_cache

local function cache_key(token)
  return "tok:" .. token
end

function _M.validate_token(ngx)
  local auth_header = ngx.req.get_headers()["Authorization"]
  if not auth_header then
    ngx.log(ngx.WARN, "Missing Authorization header")
    return false, { status = 401, body = cjson.encode({ error = "Missing Authorization header" }) }
  end

  local m = ngx.re.match(auth_header, "Bearer\\s+(.+)")
  if not m then
    ngx.log(ngx.WARN, "Invalid Authorization format: ", auth_header)
    return false, { status = 401, body = cjson.encode({ error = "Invalid Authorization format" }) }
  end

  local token = m[1]
  local k = cache_key(token)
  local cached = auth_cache:get(k)
  if cached then
    local ok, parsed = pcall(cjson.decode, cached)
    if ok and parsed and parsed.exp and os.time() < parsed.exp then
      ngx.log(ngx.INFO, "Token found in cache for user: ", parsed.user_id)
      return true, parsed
    end
  end

  ngx.log(ngx.INFO, "Validating token with auth service via GET header")
  local httpc = http.new()
  httpc:set_timeout(2000)

  local res, err = httpc:request_uri(AUTH_URL, {
    method = "GET",
    headers = {
      ["Authorization"] = "Bearer " .. token,
      ["Content-Type"] = "application/json"
    }
  })

  if not res then
    ngx.log(ngx.ERR, "Auth service unreachable: ", err or "unknown")
    return false, { status = 500, body = cjson.encode({ error = "Auth service unreachable" }) }
  end

  if res.status ~= 200 then
    ngx.log(ngx.WARN, "Auth service returned ", res.status, ": ", res.body)
    return false, { status = res.status, body = res.body }
  end

  local data, perr = cjson.decode(res.body)
  if not data then
    ngx.log(ngx.ERR, "Invalid JSON from auth service: ", perr or "unknown")
    return false, { status = 500, body = cjson.encode({ error = "Invalid auth response" }) }
  end

  if not data.user_id then
    ngx.log(ngx.ERR, "Auth response missing user_id")
    return false, { status = 500, body = cjson.encode({ error = "Invalid auth response" }) }
  end

  local exp_time = data.exp or (os.time() + CACHE_TTL)
  local to_cache = cjson.encode({ user_id = tostring(data.user_id), exp = exp_time })
  local succ, set_err = auth_cache:set(k, to_cache, CACHE_TTL)
  if not succ then
    ngx.log(ngx.WARN, "Cache set failed: ", tostring(set_err))
  end

  return true, { user_id = tostring(data.user_id) }
end

return _M
