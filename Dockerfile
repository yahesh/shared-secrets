FROM alpine:latest
WORKDIR /

# install required software

RUN apk add --no-cache aspell nginx php php-bcmath php-bz2 php-common php-ctype php-curl php-dom php-exif php-fileinfo php-fpm php-ftp php-gd php-gettext php-gmp php-iconv php-imap php-intl php-json php-ldap php-mbstring php-mysqli php-mysqlnd php-opcache php-openssl php-pcntl php-pdo php-pdo_mysql php-pdo_sqlite php-pgsql php-posix php-pspell php-session php-simplexml php-sockets php-sodium php-sqlite3 php-xml php-xmlreader php-xmlwriter php-xsl php-zip openssl tzdata

# fix missing links

RUN ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm

# fix missing users

RUN adduser -h /var/lib/php-fpm -s /sbin/nologin -D php-fpm

RUN chmod 750 /var/lib/php-fpm

# set the timezone

RUN ln -s /usr/share/zoneinfo/Europe/Berlin /etc/localtime

# create directories

RUN mkdir -p /defaults
RUN mkdir -p /defaults/htdocs
RUN mkdir -p /defaults/nginx
RUN mkdir -p /defaults/php-fpm
RUN mkdir -p /www

# copy files

COPY ./defaults/cmd.sh /
COPY ./defaults/nginx /default/nginx
COPY ./defaults/php-fpm /default/php-fpm
COPY ./html /default/htdocs

# limit file access

RUN chmod 700 /defaults
RUN chmod 700 /cmd.sh
RUN chmod 755 /www

# execute webserver

CMD ["/cmd.sh"]
