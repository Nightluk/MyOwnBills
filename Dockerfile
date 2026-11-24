FROM php:8.3-apache

# Driver do Postgres (Supabase). curl e mbstring já vêm na imagem oficial.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# PHP em modo produção (não mostra erros na tela) e Apache sem versão exposta.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/seguranca.conf \
    && printf '<DirectoryMatch "^/var/www/html/(config|includes|banco|testes)">\n    Require all denied\n</DirectoryMatch>\n<FilesMatch "^\\.">\n    Require all denied\n</FilesMatch>\n' > /etc/apache2/conf-available/bloqueios.conf \
    && a2enconf seguranca bloqueios

WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# O Render informa a porta pela variável PORT (padrão 10000).
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
