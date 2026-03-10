FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    mariadb-client \
    zip \
    unzip \
    git \
    curl \
    libglib2.0-0 \
    libnss3 \
    libnspr4 \
    libdbus-1-3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libxkbcommon0 \
    libasound2 \
    libcups2 \
    libpango-1.0-0 \
    libcairo2 \
    libgdk-pixbuf-2.0-0 \
    libgtk-3-0 \
    libx11-xcb1 \
    libxcb-dri3-0 \
    libxss1 \
    libdrm2 \
    libxcb1 \
    libxcb-shm0 \
    libxext6 \
    libxrender1 \
    libxtst6 \
    fonts-liberation \
    fonts-dejavu-core \
    fonts-freefont-ttf \
    libharfbuzz0b \
    libcairo-gobject2 \
    libpangocairo-1.0-0 \
    libjpeg62-turbo \
    libpng16-16 \
    libwebp7 \
  && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
  && apt-get install -y nodejs \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /roots/autoloader
