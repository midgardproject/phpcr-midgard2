#!/bin/bash
# Build and install Midgard2 extension
./tests/travis_midgard.sh

# Copy Midgard2 PHPCR schemas
sudo cp -r ./data/share/schema/* /usr/share/midgard2/schema/
sudo cp -r ./data/share/views/* /usr/share/midgard2/views/

# Install dependencies with Composer
wget -q http://getcomposer.org/composer.phar
php composer.phar install --dev
