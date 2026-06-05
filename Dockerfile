FROM php:8.3-cli

# PDO MySQL ve diğer eklentiler
RUN docker-php-ext-install pdo pdo_mysql

# GD eklentisi
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Diğer eklentiler
RUN docker-php-ext-install mbstring fileinfo

# Çalışma dizini
WORKDIR /app
COPY . /app

# Logs ve uploads klasörleri
RUN mkdir -p /app/logs /app/uploads/products

# Port
EXPOSE 8080

# PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
