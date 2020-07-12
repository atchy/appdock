#!/bin/sh

set -eux

# MySQL公式イメージの entrypoint を呼び出す
/usr/local/bin/docker-entrypoint.sh "mysqld"
