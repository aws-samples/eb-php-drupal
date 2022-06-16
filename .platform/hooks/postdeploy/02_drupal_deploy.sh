#!/usr/bin/env bash
set -e

# The Apache webserver runs as webapp, so we need to execute
# the deploy procedure with that user, otherwise we might create
# inacessible files under sites/default/files .
# By default, we login using `ec2-user`.
if [ "$(whoami)" != "webapp" ]; then
  SCRIPTPATH="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
  sudo -uwebapp "$SCRIPTPATH/02_drupal_deploy.sh"
  exit 0
fi

date

export $(cat /opt/elasticbeanstalk/deployment/env | xargs)

if ! grep -q elasticbeanstalk "$HOME/.bashrc"; then
  echo 'export $(cat /opt/elasticbeanstalk/deployment/env | xargs)' >> "$HOME/.bashrc"
fi

source "$HOME/.bashrc"

cd /var/www/html || exit 1
DRUSH="./vendor/bin/drush"
$DRUSH updb --no-interaction
$DRUSH cr
$DRUSH cim --no-interaction
$DRUSH cim --no-interaction
$DRUSH cr
$DRUSH cr
# Uncomment this if you use Search API
# $DRUSH sapi-c
# $DRUSH sapi-i

date
