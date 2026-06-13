FROM php:8.2-apache
# Cấu hình để Apache chạy đúng đường dẫn
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
EXPOSE 80