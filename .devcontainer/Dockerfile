FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

# Outil de build pour Composer / extensions PHP
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
        unzip zip zstd\
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- PHP + extensions utiles pour Symfony / Doctrine ---
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
        php-cli \
        php-mbstring \
        php-xml \
        php-curl \
        php-zip \
        php-sqlite3 \
        php-mysql \
        php-intl \
        php-gd \
        php-bcmath \
        mysql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- Composer ---
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- Symfony CLI ---
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash \
    && apt-get update && apt-get -y install --no-install-recommends symfony-cli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node.js, NPM et Docker (engine + compose) sont fournis
# via les "features" déclarées dans devcontainer.json.
