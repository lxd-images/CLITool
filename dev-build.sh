#!/bin/bash

SRC="src"
VENDOR="vendor"
#
function block {
    inotifywait -q -r -e modify,move,create,delete $SRC $VENDOR
}
#
function main {
    #
    clear
    #
    /usr/bin/php -c /etc/php/7.2/cli/php.ini -f box.phar build -v
    #
    clear
    #
    /usr/bin/php lxd-images.phar -w
}
#
main
#
while block; do
    main
done