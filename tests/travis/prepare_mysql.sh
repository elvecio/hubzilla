#!/usr/bin/env bash

#
# Copyright (c) 2016 Hubzilla
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#

# Exit if anything fails
set -e

echo "Preparing for MySQL ..."

# Print out some MySQL information
mysql --version
mysql -e "SELECT VERSION();"
mysql -e "SHOW VARIABLES LIKE 'max_allowed_packet';"
mysql -e "SHOW VARIABLES LIKE 'collation_%';"
mysql -e "SHOW VARIABLES LIKE 'character_set%';"
mysql -e "SELECT @@sql_mode;"

# Create Hubzilla database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hubzilla;";
mysql -u root -e "CREATE USER 'hubzilla'@'localhost' IDENTIFIED BY 'hubzilla';"
mysql -u root -e "GRANT ALL ON hubzilla.* TO 'hubzilla'@'localhost';"

# Import table structure
mysql -u root hubzilla < ./install/schema_mysql.sql

# Show databases and tables
mysql -u root -e "SHOW DATABASES;"
mysql -u root -e "USE hubzilla; SHOW TABLES;"
