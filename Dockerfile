FROM php:8.3-cli

# Sistem bağımlılıkları
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentileri
RUN docker-php-ext-install pdo pdo_mysql mbstring
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Çalışma dizini
WORKDIR /app
COPY . /app

# Logs ve uploads klasörleri
RUN mkdir -p /app/logs /app/uploads/products

# Port
EXPOSE 8080

# PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
