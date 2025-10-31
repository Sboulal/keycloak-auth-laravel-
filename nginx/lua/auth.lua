-- auth.lua
local http = require "resty.http"
local cjson = require "cjson.safe"
local _M = {}

local function get_env(name, default)
  local v = os.getenv(name)
  if v == nil or v == "" then
    return default
  end
  return v
end

local AUTH_URL = get_env("AUTH_SERVICE_URL", "")
local CACHE_TTL = tonumber(get_env("AUTH_CACHE_TTL", "300"))

local auth_cache = ngx.shared.auth_cache

local function cache_key(token)
  return "tok:" .. token
end

-- call auth service (synchronous HTTP POST)
local function call_auth_service(token)
  local httpc = http.new()
  httpc:set_timeout(2000)

  local body = cjson.encode({ token = token })
  local res, err = httpc:request_uri(AUTH_URL, {
      method = "POST",
      body = body,
      headers = {
          ["Content-Type"] = "application/json",
      },
  })

  if not res then
      return nil, { status = 500, body = "Auth service error: " .. (err or "unknown") }
  end

  if res.status ~= 200 then
      return nil, { status = res.status, body = res.body }
  end

  local data, perr = cjson.decode(res.body)
  if not data then
      return nil, { status = 500, body = "Invalid auth response" }
  end

  return data, nil
end

-- main validate_token function used by nginx conf
function _M.validate_token(ngx)
  local auth_header = ngx.req.get_headers()["Authorization"]
  if not auth_header then
    return false, { status = 401, body = "Missing Authorization header" }
  end

  local m = ngx.re.match(auth_header, "Bearer\\s+(.+)")
  if not m then
    return false, { status = 401, body = "Invalid Authorization format" }
  end
  local token = m[1]

  -- check cache first
  local k = cache_key(token)
  local cached = auth_cache:get(k)
  if cached then
    local ok, parsed = pcall(cjson.decode, cached)
    if ok and parsed and os.time() < parsed.exp then
       return true, parsed
    end
  end

  -- call auth-service
  local data, err = call_auth_service(token)
  if err then
    return false, err
  end

  -- optionally store minimal info in cache as JSON
  local to_cache = cjson.encode({
    user_id = data.user_id,
    exp = data.exp,
  })

  if data.new_token
    k = cache_key(data.new_token)
  end

  local succ, set_err = auth_cache:set(k, to_cache, CACHE_TTL)
  if not succ then
    ngx.log(ngx.WARN, "auth cache set failed: ", tostring(set_err))
  end

  return true, { user_id = data.user_id, new_token = data.new_token }
end

return _M
