#!/usr/bin/env bash
set -e


# The Apache webserver runs as webapp, so we need to execute
# the deploy procedure with that user, otherwise we might create
# inacessible files under sites/default/files .
# By default, we login using `ec2-user`.
if [ "$(whoami)" != "webapp" ]; then

  # As root, we make sure we have access to the environment variables.
  chmod 644 /opt/elasticbeanstalk/deployment/env

  SCRIPTPATH="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
  sudo -uwebapp "$SCRIPTPATH/drupal.sh"
  exit 0
fi

export $(cat /opt/elasticbeanstalk/deployment/env | xargs)

cd /var/www/html || exit 1
DRUSH="./vendor/bin/drush"
$DRUSH core-cron
