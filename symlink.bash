#!/bin/bash

# Symlink to a Joomla installation by running "bash symlink.bash /var/www/site"

site_path=$1
script_path="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

# Check if site path is a valid Joomla installation
FILE=$site_path/configuration.php
if [ ! -f "$FILE" ]; then
    echo "$site_path is not a valid Joomla installation path."
    exit 1;
fi

ln -sf $script_path/source/plugins/system/* $site_path/plugins/system/

echo "Symlink completed"
