#!/bin/bash

# exit if any command will fail
set -e;

if [ -f "./.is-installed" ]; then
  printf "Project is already installed! \n"
  exit 1;
fi;

printf "STOPPING SUPERVISOR \n";
supervisorctl stop all;

if [  -f "./.env" ]; then
  ENV_NEW_NAME=".env.backup_$(date +"%Y_%m_%d__%H_%M_%S")_$(date | shasum -a 256 | sed  's/[ ]-//g' | xargs)"
  printf "Backing up .env file under ${ENV_NEW_NAME} \n";
  cp "./.env" "${ENV_NEW_NAME}";
fi

printf "SETTING .env file \n";
cp "./.env.default" "./.env";

printf "INSTALLING COMPOSER PACKAGES \n";
composer install --ignore-platform-reqs;

printf "COMPOSER DUMP AUTOLOAD \n";
composer dump-autoload --ignore-platform-reqs;

printf "CREATING DATABASE \n";
php -d xdebug.mode=off bin/console doctrine:database:create;

printf "SETTING DATABASE TABLES \n";
php -d xdebug.mode=off bin/console doctrine:migrations:migrate --no-interaction;

printf "PREPARING CACHE \n";
php -d xdebug.mode=off bin/console cache:clear && php -d xdebug.mode=off bin/console cache:warmup;

printf "SETTING DIRS RIGHTS \n";
chmod 777 ./var -R && chown www-data:www-data ./var -R;

# make the rotated log files automatically become www-data owned
if test -d var/log/prod/; then chmod g+s var/log/prod/; fi
if test -d var/log/dev/; then chmod g+s var/log/dev/; fi

# make the rotated log files automatically become 775
if test -d var/log/prod/; then umask 002 var/log/prod/; fi
if test -d var/log/dev/; then umask 002 var/log/dev/; fi

printf "STARTING SUPERVISOR \n";
supervisorctl start all;

touch "./.is-installed";
printf "DONE \n"