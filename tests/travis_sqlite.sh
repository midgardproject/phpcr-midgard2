#!/bin/bash

# Regular setup
./tests/travis.sh

# Copy SQLite config
cp ./data/midgard2_sqlite.conf /tmp/Midgard2CR/midgard2.conf
