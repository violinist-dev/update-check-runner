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


# Used when we are doing the actual commits.
ENV GIT_AUTHOR_NAME=violinist-bot
ENV GIT_AUTHOR_EMAIL=violinistdevio@gmail.com
ENV GIT_COMMITTER_NAME=violinist-bot
ENV GIT_COMMITTER_EMAIL=violinistdevio@gmail.com

RUN composer install --no-dev --optimize-autoloader \
    && git log --pretty=%h -n1 HEAD > VERSION \
    # Make sure our php is always used.
    && ln -s /usr/local/bin/php vendor/bin/php \
    && rm -rf /usr/local/bin/composer \
    && wget https://getcomposer.org/download/latest-${COMPOSER_VERSION}.x/composer.phar -O /tmp/composer \
    && chmod 755 /tmp/composer \
    && mv /tmp/composer /usr/local/bin/composer \
    && /usr/local/bin/composer22 global require humbug/box \
    && /root/.composer/vendor/bin/box compile

FROM ghcr.io/violinist-dev/php-base:${PHP_VERSION}-multi

ARG COMPOSER_VERSION
ARG PHP_VERSION

ENV COMPOSER_VERSION=${COMPOSER_VERSION}
ENV VIOLINIST=1
ENV CI=1

WORKDIR /app

COPY --from=build /usr/src/myapp/runner.phar /runner

COPY --from=build /usr/src/myapp/VERSION /

COPY --from=build /usr/local/bin/composer /usr/local/bin/composer

CMD ["php", "/runner"]
