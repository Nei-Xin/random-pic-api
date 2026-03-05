FROM php:alpine

WORKDIR /var/www/html

COPY index.php /var/www/html/
COPY lib /var/www/html/lib
COPY pc /var/www/html/pc
COPY mobile /var/www/html/mobile

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD php -r "exit(@file_get_contents('http://127.0.0.1:80/') === false ? 1 : 0);"

CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/"]

