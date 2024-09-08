ARG COMPOSER_VERSION=2
ARG PHP_VERSION=8.3

FROM ghcr.io/violinist-dev/php-base:${PHP_VERSION}-multi AS build

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

ARG COMPOSER_VERSION=2
ARG PHP_VERSION=8.3

ENV COMPOSER_VERSION=${COMPOSER_VERSION}
ENV VIOLINIST=1
ENV CI=1

RUN composer install --no-dev --optimize-autoloader \
    # The version has the "compiled" suffix to indicate that it's... compiled.
    && git log --pretty=%h -n1 HEAD | echo "$(cat -)-compiled" > VERSION \
    # Make sure our php is always used.
    && ln -s /usr/local/bin/php vendor/bin/php \
    # The composer of the PHP base will be just the lowest supported. At the time of 
    # writing this is coincidentally also the highest (we only support Composer version 2)
    # but this has very recently changed, and will probably change again in the future.
    && rm -rf /usr/local/bin/composer \
    && wget https://getcomposer.org/download/latest-${COMPOSER_VERSION}.x/composer.phar -O /tmp/composer \
    && chmod 755 /tmp/composer \
    && mv /tmp/composer /usr/local/bin/composer \
    # Globally require box to compile a phar. We really don't need it as a dependency
    # so we do this in the build step, which is executed in the tests anyway.
    && /usr/local/bin/composer global require humbug/box \
    && /root/.composer/vendor/bin/box compile

FROM ghcr.io/violinist-dev/php-base:${PHP_VERSION}-multi
LABEL org.opencontainers.image.authors="support@violinist.io"

ARG COMPOSER_VERSION=2
ARG PHP_VERSION=8.3

ENV COMPOSER_VERSION=${COMPOSER_VERSION}
ENV VIOLINIST=1
ENV CI=1

# Used when we are doing the actual commits.
ENV GIT_AUTHOR_NAME=violinist-bot
ENV GIT_AUTHOR_EMAIL=violinistdevio@gmail.com
ENV GIT_COMMITTER_NAME=violinist-bot
ENV GIT_COMMITTER_EMAIL=violinistdevio@gmail.com

WORKDIR /app

COPY --from=build /usr/src/myapp/runner.phar /app/runner

COPY --from=build /usr/local/bin/composer /usr/local/bin/composer

# If the command is ever updated, please remember to also update the corresponding
# section in the queue starter project, and probably in some CI templates or something as well.
# For now we symlink old paths for backwards compatibility.
RUN ln -s /app/runner /runner
CMD ["php", "/app/runner"]
