FROM php:7.3-fpm

RUN apt-get update && apt-get install -y libmagickwand-6.q16-dev --no-install-recommends \
&& ln -s /usr/lib/x86_64-linux-gnu/ImageMagick-6.8.9/bin-Q16/MagickWand-config /usr/bin \
&& pecl install imagick \
&& echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN apt-get install -y zip

RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev

RUN apt-get clean && apt-get update && apt-get install -y locales
RUN locale-gen nl_BE.utf8

cmd php-fpm