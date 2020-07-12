FROM debian:buster-slim

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y \
    git \
    vim \
    --no-install-recommends && rm -r /var/lib/apt/lists/*

# appが使用するディレクトリを生成する
RUN mkdir /apps/ && \
    mkdir /apps/files/

# エントリポイントスクリプトをコピー
COPY ./apps-entrypoint.sh /usr/local/bin/apps-entrypoint.sh

ENTRYPOINT ["apps-entrypoint.sh"]