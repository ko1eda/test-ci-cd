# run the following commands inside the application directory 
cd test-app

# Copy over testing configuration.
cp .env.testing .env

# Install dependencies 
composer install

# Generate an application key. Re-cache.
php artisan key:generate

# Run database migrations.
php artisan migrate

# Run phpunit
phpunit 