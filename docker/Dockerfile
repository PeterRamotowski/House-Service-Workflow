FROM php:8.4-fpm-bookworm

ENV APP_HOME /var/www/app
ARG HOST_UID=1000
ARG HOST_GID=1000
ENV USERNAME=www-data
ENV DEBIAN_FRONTEND noninteractive
ENV DEBCONF_NOWARNINGS="yes"
ENV APP_ENV=dev
ENV APP_DEBUG=1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Install system dependencies - optimized and minimal
RUN apt-get update -y && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    dumb-init \
    build-essential \
    autoconf \
    nginx \
    pkg-config \
    libpng-dev \
    libjpeg-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libsodium-dev \
    libxml2-dev \
    libicu-dev \
    git \
    unzip \
    ca-certificates \
    curl \
    wget \
    netcat-openbsd \
    graphviz \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions - only required ones
RUN docker-php-ext-configure gd \
    --enable-gd \
    --with-jpeg \
    --with-webp \
    --with-freetype \
    && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure intl

# Install all PHP extensions in one RUN to optimize layers
RUN NUMPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) && \
    docker-php-ext-install -j${NUMPROC} \
    ctype \
    iconv \
    mbstring \
    xml \
    gd \
    pgsql \
    pdo \
    pdo_pgsql \
    opcache \
    sodium \
    bcmath \
    intl \
    zip \
    pcntl \
    sockets \
    && pecl bundle -d /usr/src/php/ext apcu \
    && docker-php-ext-install /usr/src/php/ext/apcu

# Copy PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# Copy PHP-FPM configuration
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-override.conf

# Create document root directories
RUN mkdir -p $APP_HOME && \
    mkdir -p /home/$USERNAME && \
    mkdir -p /var/log/nginx && \
    mkdir -p /var/log/php-fpm && \
    mkdir -p /var/run/php-fpm

# Set up www-data user with host UID
RUN usermod -o -u $HOST_UID $USERNAME -d /home/$USERNAME && \
    groupmod -o -g $HOST_GID $USERNAME && \
    chown $USERNAME:$USERNAME /home/$USERNAME

# Set permissions for nginx and php-fpm logs
RUN chown -R www-data:www-data /var/log/nginx && \
    chown -R www-data:www-data /var/log/php-fpm && \
    chown -R www-data:www-data /var/run/php-fpm && \
    chmod -R 2775 /var/run/php-fpm

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony && \
    chmod +x /usr/local/bin/symfony

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - && \
    apt-get update && apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR $APP_HOME

# Copy application code
COPY app/ .

# Install PHP dependencies
RUN composer install --optimize-autoloader

# Install npm dependencies and build Vite
RUN npm install && \
    npm run build && \
    npm cache clean --force

# Create Symfony var directories with proper permissions
RUN mkdir -p var/cache var/log var/run var/test && \
    chown -R www-data:www-data var/ && \
    chmod -R 2775 var/

# Create public uploads directory
RUN mkdir -p public/uploads && \
    chown -R www-data:www-data public/uploads && \
    chmod -R 2775 public/uploads

# Set PATH for vendor binaries
ENV PATH=$PATH:$APP_HOME/vendor/bin

# Configure Nginx
RUN mkdir -p /etc/nginx/sites-available && \
    mkdir -p /etc/nginx/sites-enabled && \
    rm -f /etc/nginx/sites-enabled/default

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default-host.conf /etc/nginx/sites-available/default-host.conf

# Copy startup script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set ownership for application
RUN chown -R www-data:www-data $APP_HOME && \
    find $APP_HOME -type f -exec chmod 644 {} \; && \
    find $APP_HOME -type d -exec chmod 755 {} \;

# Expose port
EXPOSE 80

# Use dumb-init for proper signal handling
ENTRYPOINT ["/usr/bin/dumb-init", "--"]
CMD ["/usr/local/bin/start.sh"]
