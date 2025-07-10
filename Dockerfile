# Use official PHP image
FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y git unzip && \
    docker-php-ext-install sockets pdo pdo_mysql mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy Composer files first (for layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Now copy the rest of the app files
COPY . .

# Optional: expose your port
EXPOSE 8080

# Run the PHP WebSocket server
CMD ["php", "server.php"]