#!/bin/sh
set -ex
hhvm --version

if [ "$TRAVIS_PHP_VERSION" = 'hhvm-3.24' ]; then
  cp composer.lock-3.24 composer.lock
fi

composer install

hh_client

hhvm vendor/bin/phpunit tests/

echo > .hhconfig
hh_server --check $(pwd)
