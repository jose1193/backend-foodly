#!/bin/sh

echo "🚀 Starting deployment process..."

# Optimizar configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Ejecutar migraciones (solo si es necesario)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force
fi

# Crear enlaces simbólicos para storage
php artisan storage:link

# Limpiar cache si es necesario
php artisan cache:clear

echo "✅ Deployment completed successfully!"

# Iniciar supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf