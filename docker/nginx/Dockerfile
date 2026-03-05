FROM nginx:alpine

# Remove the default config and copy our custom one
RUN rm -f /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Run nginx as root to allow reading of bind-mounted volumes on macOS
RUN sed -i 's/user  nginx;/user  root;/' /etc/nginx/nginx.conf
