#!/bin/bash

#
# Dependencies installer for debians.
#

setup_system() {
    #
    # Update package lists
    sudo apt-get update
    #
    # Install system packages
    sudo apt-get -yq install curl wget
    sudo apt-get -yq install unzip
    sudo apt-get -yq install git
}

install_php() {
    #
    echo "Installing PHP7.0"
    sudo apt-get -yq install php7.0 php7.0-cli
    sudo apt-get -yq install php7.0-{mbstring,curl,gd,mcrypt,json,xml,mysql,sqlite}
}

install_composer() {
    #
    # Install composer
    sudo curl -sS https://getcomposer.org/installer | sudo php
    sudo mv composer.phar /usr/local/bin/composer
    sudo ln -s /usr/local/bin/composer /usr/bin/composer
}

install_inotify() {
    #
    # Install nodejs
    sudo apt-get install inotify-tools
}

#
# Main 
#
main() {
    #
    setup_system
    #
    install_php
    #
    install_composer
    #
    install_inotify

    echo "Install finished."
}

main