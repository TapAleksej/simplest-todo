FROM php:8.3-apache
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY . /var/www/html/
WORKDIR /var/www/html/
RUN a2enmod rewrite
ENTRYPOINT ["apache2-foreground"]
