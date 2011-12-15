#!/bin/bash

# Regular setup
./tests/travis.sh

# Copy MySQL config
cp ./data/midgard2_mysql.conf /tmp/Midgard2CR/midgard2.conf
