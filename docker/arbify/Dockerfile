FROM php:8.4-fpm

ARG user=arbify
ARG uid=1000

# Environment variables from docker-compose
ENV DB_CONNECTION=mysql \
    DB_HOST=localhost \
    DB_PORT=3306 \
    DB_DATABASE=arbify \
    DB_USERNAME=root \
    DB_PASSWORD="" \
    DB_SOCKET=/var/run/mysqld/mysqld.sock

RUN apt-get update && apt-get install -y \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    mariadb-client \
    mariadb-server \
    nginx \
    supervisor

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Node.js
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs

# Blackfire
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > /usr/local/etc/php/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

WORKDIR /var/www

# Copy application files
COPY --chown=$user:$user . .

# Install composer dependencies
RUN composer install --no-interaction --optimize-autoloader

# Install node dependencies and build assets
RUN npm install && npm run production

# Copy nginx configuration
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default /etc/nginx/conf.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/sites-available/arbify.conf
RUN ln -s /etc/nginx/sites-available/arbify.conf /etc/nginx/sites-enabled/arbify.conf

# Copy supervisor configuration
COPY docker/arbify/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/arbify/entrypoint-all.sh /usr/local/bin/entrypoint-all.sh
RUN chmod +x /usr/local/bin/entrypoint-all.sh

# Create necessary directories
RUN mkdir -p /var/lib/mysql /var/run/mysqld /var/log/supervisor && \
    chown -R mysql:mysql /var/lib/mysql /var/run/mysqld && \
    chown -R $user:$user /var/www/storage /var/www/bootstrap/cache

# Create volume mount point for MySQL data
# VOLUME ["/var/lib/mysql"]

# Expose ports
EXPOSE 8080 3306

# Set working directory
WORKDIR /var/www

CMD ["/usr/local/bin/entrypoint-all.sh"]
