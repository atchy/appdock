#!/bin/sh

set -eux

# directories permissions
chown -R www-data:www-data /var/apps
chown -R www-data:www-data /var/files

# apache directories
APACHELOG_DIR="/var/log/apache2"
if [ ! -d "$APACHELOG_DIR" ]; then
    mkdir -p "$APACHELOG_DIR" && chmod 755 "$APACHELOG_DIR"
    touch "$APACHELOG_DIR/access.log"
    touch "$APACHELOG_DIR/error.log"
    touch "$APACHELOG_DIR/other_vhosts_access.log"
    touch "$APACHELOG_DIR/suexec.log"
    touch "$APACHELOG_DIR/apps-access.log"
    touch "$APACHELOG_DIR/apps-error.log"
fi

a2ensite apps.httpd && a2enmod rewrite

# DBスクリプトの実行
php /usr/local/bin/db-setup.php "$@"

# apacheを起動
apache2-foreground
