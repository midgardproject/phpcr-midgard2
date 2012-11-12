#!/bin/bash

# Install libgda MySQL connector
sudo apt-get install -y libgda-4.0-mysql

# Create the database
mysql -e 'create database midgard2_test;'
sudo mysql -e 'SET GLOBAL sql_mode="";'

# Regular setup
./tests/travis.sh
