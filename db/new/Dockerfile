FROM mysql:8

ARG app_ver

COPY ./$app_ver/conf.d/app.my.cnf /etc/mysql/mysql.conf.d/

COPY ./$app_ver/db-entrypoint.sh /usr/local/bin/db-entrypoint.sh

ENTRYPOINT ["db-entrypoint.sh"]
