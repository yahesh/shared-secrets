#!/usr/bin/env sh
set -e

# create default directories

if [ ! -d /www/htdocs ]
then
  mkdir -p /www/htdocs
  chmod 755 /www/htdocs

  cp -R /default/htdocs/* /www/htdocs
fi

if [ ! -d /www/nginx ]
then
  mkdir -p /www/nginx
  chmod 700 /www/nginx

  cp -R /default/nginx/* /www/nginx
fi

if [ ! -d /www/php-fpm ]
then
  mkdir -p /www/php-fpm
  chmod 700 /www/php-fpm

  cp -R /default/php-fpm/* /www/php-fpm
fi

if [ ! -f /www/nginx/dhparam ]
then
  if [ -z "${SKIP_DHPARAM}" ]
  then
    openssl dhparam -out /www/nginx/dhparam 4096
  else
    touch /www/nginx/dhparam
  fi
  chmod 600 /www/nginx/dhparam
fi

# prepare custom container paths

if [ ! -d /config ]
then
  mkdir -p /config
fi;

chmod 777 /config
rm -rf /www/htdocs/config
ln -s /config /www/htdocs/config

if [ ! -d /db ]
then
  mkdir -p /db
fi

chmod 777 /db
rm -rf /www/htdocs/db
ln -s /db /www/htdocs/db

# execute nginx and php-fpm

if [ -f /www/php-fpm/php.ini ] && [ -f /www/php-fpm/php-fpm.conf ]
then
  /usr/sbin/php-fpm -c /www/php-fpm/php.ini -y /www/php-fpm/php-fpm.conf -D
fi

if [ -f /www/nginx/nginx.conf ]
then
  /usr/sbin/nginx -c /www/nginx/nginx.conf -g "daemon off;"
fi
