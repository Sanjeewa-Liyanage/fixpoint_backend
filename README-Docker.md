# Fixpoint Backend Docker Deployment

This Docker setup provides a complete deployment environment for the Fixpoint Backend application.

## Features

- **Simplified Routing**: All traffic is redirected to `index.php` except for `/playground` which serves `src/playground/index.php`
- **PHP 8.1 FPM**: Modern PHP runtime with optimized performance
- **Nginx**: High-performance web server with proper PHP handling
- **Supervisor**: Process management for PHP-FPM and Nginx

## Quick Start

### Using Docker Compose (Recommended)

```bash
# Build and start the application
docker-compose up --build

# Or run in detached mode
docker-compose up -d --build
```

### Using Docker directly

```bash
# Build the image
docker build -t fixpoint-backend .

# Run the container
docker run -p 80:80 fixpoint-backend
```

## Access the Application

- **Main Application**: http://localhost/
- **Playground**: http://localhost/playground
- **API Endpoints**: http://localhost/api/[endpoint]

## Routing Logic

- `/playground` → serves `src/playground/index.php`
- All other requests → redirected to `index.php`

## Development

For development, you can mount your local files:

```bash
docker-compose up -d
# Your changes will be reflected immediately
```

## Production Deployment

For production, consider:

1. Using environment variables for configuration
2. Setting up SSL/TLS certificates
3. Configuring proper logging
4. Database connection setup
5. Enabling PHP OPcache for better performance

## File Structure

```
├── Dockerfile              # Main Docker configuration
├── docker-compose.yml      # Docker Compose setup
├── docker/
│   ├── nginx.conf          # Nginx configuration
│   └── supervisord.conf    # Process management
├── index.php               # Main application entry point
└── src/playground/         # Playground files
```

## Customization

- Edit `docker/nginx.conf` to modify web server settings
- Edit `docker/supervisord.conf` to adjust process management
- Edit `Dockerfile` to add additional PHP extensions or system packages
