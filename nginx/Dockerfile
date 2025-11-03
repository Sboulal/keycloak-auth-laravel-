FROM openresty/openresty:alpine

# Install lua-resty-http
RUN apk add --no-cache git make gcc musl-dev \
    && cd /tmp \
    && git clone https://github.com/ledgetech/lua-resty-http.git \
    && cd lua-resty-http \
    && make install \
    && cd / \
    && rm -rf /tmp/lua-resty-http

# Create custom lua directory
RUN mkdir -p /usr/local/openresty/lualib/custom

EXPOSE 80 443

CMD ["/usr/local/openresty/bin/openresty", "-g", "daemon off;"]