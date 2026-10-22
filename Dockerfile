# Includes the fixes for CVE-2026-109618 and CVE-2026-109619.
FROM php:8.5.12-cli-alpine

COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-security.ini

WORKDIR /var/www/html

COPY index.php /var/www/html/
COPY lib /var/www/html/lib
COPY pc /var/www/html/pc
COPY mobile /var/www/html/mobile

# Fail the build if the runtime is unpatched or request parsing is re-enabled.
RUN php -r 'exit(PHP_VERSION_ID >= 80512 && !ini_get("file_uploads") && !ini_get("enable_post_data_reading") ? 0 : 1);' \
    && php -l index.php \
    && php -l lib/random_image.php \
    && php -l pc/index.php \
    && php -l mobile/index.php

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD php -r "exit(@file_get_contents('http://127.0.0.1:80/') === false ? 1 : 0);"

CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/"]

