FROM php:7.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libldap2-dev \
    libsasl2-dev \
    unzip \
    curl \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        zip \
        gd \
        exif \
        pdo_mysql \
        mysqli \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap \
    && npm install -g less less-plugin-clean-css \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN if [ -f composer.json-dist ]; then \
        cp composer.json-dist composer.json && \
        composer install --no-dev --no-interaction --optimize-autoloader; \
    fi

# Install JS dependencies
RUN if [ -f bin/install-jsdeps.sh ]; then \
        php bin/install-jsdeps.sh; \
    fi

# Compile LESS to CSS for Elastic skin
RUN cd skins/elastic && \
    lessc --clean-css="--s1 --advanced" styles/styles.less > styles/styles.min.css && \
    lessc --clean-css="--s1 --advanced" styles/print.less > styles/print.min.css && \
    lessc --clean-css="--s1 --advanced" styles/embed.less > styles/embed.min.css && \
    ln -s styles.min.css styles/styles.css && \
    ln -s print.min.css styles/print.css && \
    ln -s embed.min.css styles/embed.css

# Set permissions
RUN chown -R www-data:www-data /var/www/html/temp /var/www/html/logs \
    && chmod -R 755 /var/www/html

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Configure Apache DocumentRoot to public_html
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose port 80
EXPOSE 80

CMD ["apache2-foreground"]
