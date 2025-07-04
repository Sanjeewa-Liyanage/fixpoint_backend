#!/bin/bash
echo "Checking current nginx configuration..."
nginx -T 2>&1 | grep -A 10 -B 10 "server"

echo "Testing if index.php exists..."
ls -la /home/site/wwwroot/index.php

echo "Testing PHP execution..."
php -v

echo "Current working directory:"
pwd

echo "Directory contents:"
ls -la /home/site/wwwroot/

echo "Testing basic PHP execution..."
php -r "echo 'PHP is working\n';"

echo "Testing if FastCGI is running..."
ps aux | grep php-fpm
