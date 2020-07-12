FROM php:5.6.10-apache

ENV DEBIAN_FRONTEND noninteractive

# PHP拡張モジュールの依存モジュールのインストール
RUN apt-get update && apt-get install -y \
    vim \
    less \
    --no-install-recommends && rm -r /var/lib/apt/lists/*

# PHP拡張モジュールのインストール
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql

# appが配置されるディレクトリを生成する
RUN mkdir -p /var/apps/ && \
    chown -R www-data:www-data /var/apps/
RUN mkdir -p /var/files/ && \
    chown -R www-data:www-data /var/files/

# AH00558 対策
RUN echo "ServerName localhost" | tee /etc/apache2/conf-available/fqdn.conf
RUN a2enconf fqdn

# error_reporting = E_ALL を置換したphp.iniを生成する
#RUN sed -e '/^error_reporting.*$/c error_reporting = E_ALL & ~E_STRICT & ~E_DEPRECATED' \
#    /usr/local/etc/php/php.ini-development > \
#    /usr/local/etc/php/php.ini

ARG app_ver

# app用の設定値を持つphp.iniをコピー
COPY ./$app_ver/conf.d/apps.php.ini /usr/local/etc/php/conf.d/

# httpdの設定ファイルをコピー
COPY ./$app_ver/conf.d/apps.httpd.conf /etc/apache2/sites-available/

# DBスクリプトをコピー
COPY ./db-setup.php /usr/local/bin/db-setup.php

# エントリポイントスクリプトをコピー
COPY ./$app_ver/web-entrypoint.sh /usr/local/bin/web-entrypoint.sh

ENTRYPOINT ["web-entrypoint.sh"]
