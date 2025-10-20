FROM php:8.2-apache

# Install system dependencies and Python3
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Install PyYAML (vulnerable version for CVE-2020-14343)
RUN pip3 install PyYAML==5.3.1 --break-system-packages

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/inquiries \
    && chmod +x /var/www/html/parse_yaml.py

# Expose port 80
EXPOSE 80
