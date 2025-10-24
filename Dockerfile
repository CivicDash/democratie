FROM php:8.3-fpm-alpine

# Arguments
ARG UID=1000
ARG GID=1000

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    bash \
    shadow

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql pcntl bcmath gd xml

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN addgroup -g ${GID} -S civicdash && \
    adduser -u ${UID} -S civicdash -G civicdash

# Set working directory
WORKDIR /var/www

# Copy existing application directory permissions
COPY --chown=civicdash:civicdash . /var/www

# Change current user to civicdash
USER civicdash

# Expose port 8000 and start application
EXPOSE 8000

