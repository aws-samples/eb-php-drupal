#!/usr/bin/env bash

# We need to expose these environment variables to webapp user
# for being able to access to the database.
sudo chmod 644 /opt/elasticbeanstalk/deployment/env

# Make sure Drupal files are symlinked and
# have proper ownership.
if [[ ! -e /var/www/html/web/sites/default/files ]];
then
  sudo ln -s /drupalfiles /var/www/html/web/sites/default/files
fi
sudo chown -R webapp /drupalfiles/*
sudo chgrp -R webapp /drupalfiles/*
