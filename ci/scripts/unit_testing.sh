# Copy over testing configuration.
cp test-app/.env.testing test-app/.env

# Install dependencies 
composer install

# Generate an application key. Re-cache.
php artisan key:generate
# php artisan config:cache

# Run database migrations.
php artisan migrate

# Run phpunit
/vendor/bin/phpunit