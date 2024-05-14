ARG COMPOSER_VERSION
ARG PHP_VERSION

FROM ghcr.io/violinist-dev/php-base:${PHP_VERSION}-multi AS build
MAINTAINER eiriksm <eirik@morland.no>

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

ARG COMPOSER_VERSION
ARG PHP_VERSION

ENV COMPOSER_VERSION=${COMPOSER_VERSION}
ENV VIOLINIST=1
ENV CI=1

RUN composer install --no-dev --optimize-autoloader \
    # Make sure our php is always used.
    && ln -s /usr/local/bin/php vendor/bin/php \
    && rm -rf /usr/local/bin/composer \
    && wget https://getcomposer.org/download/latest-${COMPOSER_VERSION}.x/composer.phar -O /tmp/composer \
    && chmod 755 /tmp/composer \
    && mv /tmp/composer /usr/local/bin/composer

RUN /usr/local/bin/composer global require humbug/box && \
  /root/.composer/vendor/bin/box compile

FROM ghcr.io/violinist-dev/php-base:${PHP_VERSION}-multi

COPY --from=build /usr/src/myapp/runner.phar /app/runner.phar

CMD ["php", "/app/runner.phar"]
