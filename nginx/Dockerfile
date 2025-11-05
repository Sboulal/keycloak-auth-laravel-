FROM openresty/openresty:alpine

# Install Perl for opm and any other dependencies
RUN apk add --no-cache perl

# Install lua-resty-http via OPM (OpenResty Package Manager)
# RUN /usr/local/openresty/bin/opm get ledgetech/lua-resty-http

# Expose ports
EXPOSE 87 443

# Default command
CMD ["openresty", "-g", "daemon off;"]
