FROM composer:2 AS VendorBuilder
WORKDIR /app
COPY composer.json composer.lock .
RUN composer install --optimize-autoloader --no-interaction --ignore-platform-reqs --prefer-dist --no-scripts

FROM node:24-alpine AS FrontendBuilder
WORKDIR /app
COPY package.json package-lock.json .
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www
RUN apk add --no-cache linux-headers oniguruma-dev
RUN docker-php-ext-install pdo pdo_mysql mbstring pcntl
COPY --from=VendorBuilder /app/vendor /var/www/vendor
COPY --from=FrontendBuilder /app/public/build /var/www/public/build
COPY . .
RUN chmod -R 755 /var/www/storage && chown -R www-data:www-data /var/www

USER www-data
