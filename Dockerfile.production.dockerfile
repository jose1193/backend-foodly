# Dockerfile.production
FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    supervisor \
    nginx

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instalar Redis extension
RUN apk add --no-cache redis
RUN pecl install redis && docker-php-ext-enable redis

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario del sistema para ejecutar Composer y Artisan Commands
RUN addgroup -g 1000 www && adduser -D -s /bin/sh -u 1000 -G www www

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de composer
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-scripts --no-autoloader --optimize-autoloader --no-dev

# Copiar código de la aplicación
COPY . .
COPY --chown=www:www . /var/www

# Instalar dependencias de Node.js (si las tienes)
RUN if [ -f "package.json" ]; then \
    apk add --no-cache nodejs npm && \
    npm ci --only=production && \
    npm run build && \
    apk del nodejs npm; \
    fi

# Finalizar instalación de Composer
RUN composer dump-autoload --optimize

# Configurar permisos
RUN chown -R www:www /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Configurar Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configurar Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorio para logs
RUN mkdir -p /var/log/supervisor

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]