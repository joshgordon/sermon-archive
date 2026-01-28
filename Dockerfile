FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mbstring 2>/dev/null || true

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY index.php functions.php Parsedown.php style.css favicon.ico ./
COPY config.example.php ./config.php
COPY getid3/ ./getid3/

# Create data directory for sermon files
RUN mkdir -p /data/spep/spepmedia.com

# Configure PHP for development
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/ || exit 1
