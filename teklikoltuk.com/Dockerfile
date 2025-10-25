# PHP + Apache
FROM php:8.2-apache

# Gerekli paketleri yükle ve pdo_sqlite kurulumu
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite aktif et
RUN a2enmod rewrite

# Çalışma dizini
WORKDIR /var/www/html

# Proje dosyalarını kopyala
COPY . /var/www/html

# Dosya izinleri
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80