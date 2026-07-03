# Minimal nginx serving canned WMS Capabilities + tile PNGs.
# Used by characterization tests to make external WMS calls deterministic.
FROM nginx:1.27-alpine

COPY docker/wms-mock/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/wms-mock/fixtures/ /usr/share/nginx/html/
