FROM nginx:1.27-alpine
COPY nginx.conf /etc/nginx/nginx.conf
WORKDIR /var/www/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]