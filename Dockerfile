# 使用官方 PHP 镜像作为基础镜像
FROM php:alpine

# 将本地文件复制到容器中
COPY index.php /var/www/html/
COPY pc /var/www/html/pc
COPY mobile /var/www/html/mobile

# 暴露容器的 80 端口
EXPOSE 80

# 设置容器启动时执行的命令
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/"]
