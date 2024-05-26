# 使用官方 PHP 镜像作为基础镜像
FROM php:latest

# 将本地文件复制到容器中
COPY index.php /var/www/html/

# 暴露容器的 80 端口
EXPOSE 80

# 设置容器启动时执行的命令
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/"]
