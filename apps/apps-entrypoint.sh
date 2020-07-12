#!/bin/sh

set -eux

APPS_CSV_PATH=$1

if [ ! -f "${APPS_CSV_PATH}" ]; then
  echo "No such file: ${APPS_CSV_PATH}"
  exit 1
fi

for line in `cat ${APPS_CSV_PATH}`
do
  app_name=`echo ${line} | cut -d ',' -f 1`

  # ディレクトリが無ければ作る
  FILE_DIR="/apps/files/${app_name}/"
  if [ ! -d "$FILE_DIR" ]; then
    mkdir -p "$FILE_DIR"
  fi

done

exec /bin/sh
