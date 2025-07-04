#!/bin/bash

echo "Starting custom nginx configuration..."

# Create nginx configuration directory if it doesn't exist
mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled

# Copy custom nginx configuration
if [ -f /home/site/wwwroot/nginx/default ]; then
    echo "Copying custom nginx configuration..."
    cp /home/site/wwwroot/nginx/default /etc/nginx/sites-available/default
    
    # Create symlink for enabled sites
    ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
    
    # Remove default nginx configuration
    rm -f /etc/nginx/sites-enabled/default.conf
    
    echo "Custom nginx configuration applied."
else
    echo "Custom nginx configuration not found, using default."
fi

# Test nginx configuration
echo "Testing nginx configuration..."
if nginx -t; then
    echo "Nginx configuration is valid."
else
    echo "Nginx configuration is invalid, falling back to default."
fi

# Reload nginx
echo "Reloading nginx..."
nginx -s reload

echo "Nginx configuration complete."

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
