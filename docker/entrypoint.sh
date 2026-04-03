#!/bin/bash
set -e

echo "==> Starting SAUS-ES application setup..."

# Run composer install if vendor directory is empty
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "==> Running composer install..."
    cd /var/www/html
    composer install --no-interaction --optimize-autoloader
fi

# Generate APP_KEY if it's still the placeholder
if [ "$APP_KEY" = "base64:placeholder_key_will_be_generated" ]; then
    echo "==> Generating application key..."
    cd /var/www/html
    php artisan key:generate --no-interaction --force
fi

# Wait for database to be ready (extra safety beyond healthcheck)
echo "==> Waiting for database connection..."
max_retries=30
retry_count=0
until php -r "
    try {
        new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
        echo 'connected';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    retry_count=$((retry_count + 1))
    if [ $retry_count -ge $max_retries ]; then
        echo "==> ERROR: Could not connect to database after $max_retries attempts."
        break
    fi
    echo "==> Database not ready yet, retrying in 2s... ($retry_count/$max_retries)"
    sleep 2
done

# Run Laravel migrations
echo "==> Running database migrations..."
cd /var/www/html
php artisan migrate --force --no-interaction 2>&1 || echo "==> WARNING: Migration encountered issues (legacy tables may already exist)"

# Set proper permissions
echo "==> Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Seed database on first run
echo "==> Seeding database if empty..."
php artisan db:seed --no-interaction 2>&1 || echo "==> Database already seeded or seeding skipped"

# Create upload directories
mkdir -p /var/www/html/php/uploads/tickets
mkdir -p /var/www/html/php/uploads/news
chown -R www-data:www-data /var/www/html/php/uploads

echo "==> Setup complete. Starting Apache..."
exec apache2-foreground
