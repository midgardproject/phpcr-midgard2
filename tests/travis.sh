#!/bin/bash
git submodule init
git submodule update

# Build and install Midgard2 extension
./tests/travis_midgard.sh

# Copy Midgard2 PHPCR schemas
cp -r ./data/share/schema/* /usr/share/midgard2/schema/

# Install dependencies with Composer
wget -q http://getcomposer.org/composer.phar
php composer.phar install
