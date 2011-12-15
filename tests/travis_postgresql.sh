#!/bin/bash

# Install libgda MySQL connector
sudo apt-get install -y libgda-4.0-postgres

# Create the database
psql -c 'create database midgard2_test;' -U postgres

# Regular setup
./tests/travis.sh
