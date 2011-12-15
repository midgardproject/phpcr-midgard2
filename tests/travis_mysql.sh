#!/bin/bash

# Install libgda MySQL connector
sudo apt-get install -y libgda-4.0-mysql

# Regular setup
./tests/travis.sh
