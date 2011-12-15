#!/bin/bash
git submodule init
git submodule update

# Build and install Midgard2 extension
./travis_midgard.sh > /dev/null

# Set up test environment
mkdir /tmp/Midgard2CR
mkdir /tmp/Midgard2CR/share
mkdir /tmp/Midgard2CR/blobs
mkdir /tmp/Midgard2CR/var
mkdir /tmp/Midgard2CR/cache
cp -r data/share/* /tmp/Midgard2CR/share
cp data/midgard2.conf /tmp/Midgard2CR/midgard2.conf

# Install dependencies with Composer
wget -q http://getcomposer.org/composer.phar
php composer.phar install
