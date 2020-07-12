# appdock
## TL;DR
以下のようなWebアプリケーションの開発環境をDocker Composeを利用して構築しました

* Apache、PHP、MySQLで動作するWebアプリケーション
* Webアプリケーションのバージョンが複数ある（ここではnewバージョンとoldバージョン）
* Webアプリケーションのバージョンにより、PHP、MySQLのバージョンが異なる
* 顧客ごとにカスタマイズが施されているため、顧客ごとにソースディレクトリが異なる（ここでは株式会社abc、株式会社defなど）

## newバージョンの環境
- Apache:2.4.38
- PHP: 7.4.7
- MySQL: 5.7.21

## oldバージョンの環境
- Apache:2.4.0
- PHP: 5.6.10
- MySQL: 5.7.21

Webサーバ内の構成イメージは以下

```
/var/apps/
|-- abc # 株式会社abcさま用アプリ
|   |-- abc.php
|   |-- abc.png
|   `-- ・・・
`-- def # 株式会社defさま用アプリ
    |-- def.php
    |-- def.png
    `-- ・・・
```
 
この場合、```abc```へのURLは以下となるように構成しています。  
```http://localhost:8080/abc/abc.php```  
```http://localhost:8080/abc/abc/png```  
```http://localhost:8080/def/def.php```  
```http://localhost:8080/def/def/png```  
  
## ホストPCの構成
ホストPCへの配置イメージは以下です。  
defという環境も同様に構成しておきます。
```
/Users/namae/github/webapp
├── abc  # 株式会社abcさま用アプリ
|   |-- abc.php
|   |-- abc.png
|   `-- ・・・
└── def # 株式会社defさま用アプリ
    |-- def.php
    |-- def.png
    `-- ・・・
```

## 各コンテナの役割

### db
MySQLを含むコンテナです。  
起動時に```db-dntrypoint.sh```を呼び出していますが、とくに何もしていません。 

## web
ApacheとPHPを含むコンテナです。  
ホストPC上のWebアプリケーションのソースディレクトリをマウントしています。  
また、コンテナ起動時に```apps/blueprint```のcsvを読み取り、  
適宜データベースインスタンスを生成および初期化スクリプトを実行します。 

## apps
Webアプリケーションを含むコンテナです。  
本来はこのコンテナをdockerイメージ化する時点でWebアプリケーションの  
ソースディレクトリをいっしょにビルドするべきですが、そうするとソース改変ごとに  
再ビルドと再起動が必要になるので、開発目的としてはソースディレクトリを  
```web```コンテナがマウントするようにしています。  
また、Webアプリケーションが利用するディレクトリを生成したり、永続化ボリュームを保持します。

## クイックスタート
  
1. このリポジトリをcloneし、そのルートディレクトリに移動しておきます。
    ```
    $ cd ~/github/appdock
    ```
1. ホストPCに依存する項目は、.envファイルで指定します。.envファイルは各ホストPC上に作成してください。  
```HOST_WF_SITES_PATH```に指定するパスは、Docker Desktopがアクセスできる場所を指定してください。  
Docker Desktop の Preferences > Resources > FILE SHARING からDocker Desktopがアクセスできる場所を指定できます。
    ```shell script
    pwd
    /Users/namae/github/appdock
    cat << EOS > .env
    # appのバージョン（old/new）
    APP_VER=new
    # ホスト側のappsの絶対パス
    HOST_APPS_PATH=/Users/user/dev/apps/
    # DB ホスト名
    DB_HOST=db
    # DB ルートパスワード
    DB_ROOT_PASSWORD=password
    # blueprintディレクトリ
    BLUEPRINT_DIR_PATH=./apps/blueprint
    # blueprintファイル名
    BLUEPRINT_FILE_NAME=apps.csv
    EOS
    ```
1. ```.env```で指定した```blueprint```に各環境の情報を登録します。  
```blueprint```はCSV形式で、```環境名、DB名、DBユーザ名、DBパスワード```の順で指定してください。  
    ```shell script
    pwd
    /Users/user/github/appdock/new/apps/blueprint
    cat << EOS > apps.csv
    abc,abc-db,abc-user,abc-password
    def,def-db,def-user,def-password
    EOS
    ```
```apps.csv```で指定した情報は、docker composeのサービスが参照します。  
* ```apps```  
指定された```環境名```で各環境が利用するディレクトリを生成します。

* ```web```  
指定された```環境名、DB名、DBユーザ名、DBパスワード```でDBとユーザを作成し、初期データを生成します。  
起動時にすでに存在するDBであれば何もしません。また、Webfileのソース一式をホストPCからバインドします。
   
1. スクリプトからコンテナを起動します
    ```
    $ _/up.sh
    ```

1. コンテナが起動したら [株式会社abcさま向けWebアプリ](http://localhost:8080/abc/bc.php) からアクセスします。   
コンテナが起動したかどうかは`$ _/logs.sh`で、MySQLとApacheの起動が完了したかどうかで判断できます。

1. コンテナを停止するときは`$ _/down.sh`を実行してください。  

## ディレクトリ構成
```
.
├── README.md
├── _                           # dockerスクリプトディレクトリ
│   ├── bld_db.sh               # dbをビルド
│   ├── bld_web.sh              # webをビルド
│   ├── destroy.sh              # コンテナをdownして、不要なコンテナを削除、マウントしたディレクトリの内容を全て削除
│   ├── down.sh                 # コンテナをdownして、不要なコンテナを削除
│   ├── enter_db.sh             # upしたdbコンテナにログイン
│   ├── enter_web.sh            # upしたwebコンテナにログイン
│   ├── logs.sh                 # コンテナの起動ログを表示
│   ├── prune.sh                # 不要なコンテナとボリュームを削除
│   ├── ps.sh                   # コンテナ一覧を表示
|   ├── restart.sh              # コンテナを再起動
│   ├── up.sh                   # コンテナを起動
│   └── vols.sh                 # ボリューム一覧を表示
├── apps
│   ├── Dockerfile              # appsのDockerfile
│   ├── apps-entrypoint.sh      # appsのエントリポイント
│   └── blueprint               # blueprintディレクトリ
├── db
│   ├── old
│   │   ├── Dockerfile          # db@old系のDockerfile
│   │   ├── conf.d
│   │   │   └── app.my.cnf      # db@old系のmy.cnf
│   │   └── db-entrypoint.sh    # db@old系のエントリポイント
│   └── new
│       ├── Dockerfile          # db@new系のDockerfile
│       ├── conf.d              
│       │   └── app.my.cnf      # db@new系のmy.cnf
│       └── db-entrypoint.sh    # db@new系のエントリポイント
├── docker-compose.yml
└── web
    ├── old
    │   ├── Dockerfile          # web@3系のDockerfile
    │   ├── Dockerfile-xdebug   # web@3系のDockerfile（with Xdebug）
    │   ├── conf.d
    │   │   ├── app.httpd.conf  # web@old系http.conf
    │   │   ├── app.php.ini     # web@old系php.ini
    │   │   └── app.xdebug.ini  # web@old系xdebug.ini
    │   └── web-entrypoint.sh   # web@old系エントリポイント
    ├── new
    │   ├── Dockerfile
    │   ├── Dockerfile-xdebug
    │   ├── conf.d
    │   │   ├── app.httpd.conf  # web@new系http.conf
    │   │   ├── app.php.ini     # web@new系php.ini
    │   │   └── app.xdebug.ini  # web@new系xdebug.ini
    │   └── web-entrypoint.sh   # web@new系エントリポイント
    └── db-setup.php            # DBセットアップスクリプト

```
## xdebugについて
xdebugを利用する場合は、.env ファイルを `WEBAPP_DOCKERFILE=Dockerfile-xdebug` と変更してください。  
webapp/conf.d/app.xdebug.ini は環境に合わせて変更してください。

```conf.d/app.xdebug.ini
[xdebug]
zend_extension = /usr/local/lib/php/extensions/no-debug-non-zts-20160303/xdebug.so
xdebug.profiler_enable = 0
xdebug.remote_enable = 1
xdebug.remote_host = host.docker.internal
xdebug.remote_port = 9123 # デフォルトの9000では動かない場合があるので...
xdebug.remote_handler = dbgp
xdebug.remote_autostart = 1
xdebug.remote_mode = req
xdebug.var_display_max_children = -1
xdebug.var_display_max_data = -1
xdebug.var_display_max_depth = -1
xdbug.remote_autostart = 1
xdebug.idekey = PHPSTORM
xdebug.profiler_output_dir = /var/tmp
xdebug.trace_output_dir = /var/tmp
```
