FROM ubuntu:bionic

RUN apt-get update && apt-get install -y zip

RUN apt-get clean && apt-get update && apt-get install -y locales
RUN locale-gen nl_BE.utf8

RUN apt-get update && apt-get install -y software-properties-common && apt-get update 
RUN add-apt-repository -y ppa:ondrej/php
RUN apt-get update
RUN apt-get install -y php7.3 php7.3-fpm php7.3-zip php7.3-mysql php7.3-curl php7.3-json php7.3-xml php7.3-soap php7.3-gd php7.3-mbstring php7.3-apcu php7.3-opcache php-imagick

RUN apt-get install -y nginx

RUN mkdir -p /var/run/php/
ADD php/conf.d/99-scouts.ini /etc/php/7.3/fpm/pool.d/99-scouts.ini

RUN touch /var/log/php_errors.log

ENV DEBUG 1
cmd DEBUG=1 php-fpm7.3 -D; nginx -g 'daemon off;'