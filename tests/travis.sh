#!/bin/bash
git submodule init
git submodule update

# Install Pake
pyrus channel-discover pear.indeyets.ru
pyrus install -f http://pear.indeyets.ru/get/pake-1.6.3.tgz

# Install Midgard2 library from OBS
sudo apt-get install -y dbus libglib2.0-dev libgda-4.0-4 libgda-4.0-dev 
wget -q http://download.opensuse.org/repositories/home:/midgardproject:/ratatoskr/xUbuntu_10.04/i386/libmidgard2-2010_10.05.5.1-1_i386.deb
wget -q http://download.opensuse.org/repositories/home:/midgardproject:/ratatoskr/xUbuntu_10.04/i386/midgard2-common_10.05.5.1-1_i386.deb 
wget -q http://download.opensuse.org/repositories/home:/midgardproject:/ratatoskr/xUbuntu_10.04/i386/libmidgard2-dev_10.05.5.1-1_i386.deb 
sudo dpkg -i --force-depends libmidgard2-2010_10.05.5.1-1_i386.deb
sudo dpkg -i midgard2-common_10.05.5.1-1_i386.deb
sudo dpkg -i libmidgard2-dev_10.05.5.1-1_i386.deb

# Build and install Midgard2 PHP extension
wget -q https://github.com/midgardproject/midgard-php5/tarball/ratatoskr
tar zxf ratatoskr
sh -c "cd midgardproject-midgard-php5-*&&php `pyrus get php_dir|tail -1`/pake.php install > /dev/null"
echo "extension=midgard2.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

# Set up test environment
mkdir /tmp/Midgard2CR
mkdir /tmp/Midgard2CR/share
mkdir /tmp/Midgard2CR/blobs
mkdir /tmp/Midgard2CR/var
mkdir /tmp/Midgard2CR/cache
cp -r data/share/* /tmp/Midgard2CR/share
cp data/midgard2.conf /tmp/Midgard2CR/midgard2.conf

# Install dependencies with Composer
wget http://getcomposer.org/composer.phar
php composer.phar install
