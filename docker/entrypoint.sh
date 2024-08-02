#!/bin/bash

service apache2 restart;

# Prepare & run supervisor
    mkdir -p /var/www/html/var/log/supervisor \
&& service supervisor start \
&& supervisorctl reread \
&& supervisorctl update \
&& supervisorctl start all;

service cron start;

echo -e "[DEBUG] Calling install-or-update \n";
cd /var/www/html && ./install-or-update.sh;