FROM composer:2 AS vendor-builder
WORKDIR /app
COPY composer.json composer.lock .
RUN composer install --optimize-autoloader --no-interaction --ignore-platform-reqs --prefer-dist --no-scripts

FROM node:24-alpine AS frontend-builder
WORKDIR /app
COPY package.json package-lock.json .
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js .
COPY tailwind.config.js .
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www
RUN apk add --no-cache linux-headers oniguruma-dev
RUN docker-php-ext-install pdo pdo_mysql mbstring pcntl
COPY --chown=82:82 . .
COPY --from=vendor-builder --chown=82:82 /app/vendor /var/www/vendor
COPY --from=frontend-builder --chown=82:82 /app/public/build /var/www/public/build
RUN chmod -R 755 /var/www/storage /var/www/bootstrap/cache

USER www-data
CMD ["php-fpm", "-F"]
