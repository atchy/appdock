#!/usr/bin/env bash
# volumeを削除してdownする
docker-compose -p myapps down -v
# 不要なコンテナ、イメージを一括削除する
docker system prune -f
# 不要なボリュームも一括削除する
docker volume prune -f


