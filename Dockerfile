FROM drupaldocker/php:7.0-alpine-cli
MAINTAINER eiriksm <eirik@morland.no>
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD ["php", "runner.php"]

