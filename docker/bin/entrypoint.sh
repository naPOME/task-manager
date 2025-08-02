#!/bin/sh

# Create the database file if it doesn't exist
if [ ! -f /var/www/html/storage/database/database.sqlite ]; then
    echo "Creating database..."
    mkdir -p /var/www/html/storage/database
    touch /var/www/html/storage/database/database.sqlite
    # You can add any initial database setup commands here, e.g., running migrations
    # php artisan migrate --seed
fi

# Set the correct permissions for the storage directory
chown -R www-data:www-data /var/www/html/storage

# Execute the CMD
exec "$@"
