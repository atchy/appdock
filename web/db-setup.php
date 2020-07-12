<?php

/**
 * Class DbManager
 *
 *
 */
class DbManager {
    /**
     * PDO database handler
     * @var null
     */
    private $dbh = null;

    /**
     * DB host
     *
     * @var string
     */
    private $host = '';

    /**
     * root account
     *
     * @var string
     */
    private $root_account = '';

    /**
     * root password
     *
     * @var string
     */
    private $root_password = '';

    /**
     * DbManager constructor.
     * @param string $host
     * @param string $root_account
     * @param string $root_password
     * @param string $db_name
     */
    public function __construct($host, $root_account, $root_password , $db_name = '') {

        $this->host = $host;
        $this->root_account = $root_account;
        $this->root_password = $root_password;

        $dsn = "mysql:host={$this->host}";

        if ($db_name !== '') {
            $dsn .= ";dbname={$db_name}";
        }

        $this->dbh = new PDO($dsn, $this->root_account, $this->root_password);

        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * MySQLのmysqlデータベースに接続できるかどうか
     * つまりMySQLの初期化が完了しているかどうか
     *
     * @param string $host
     * @param string $root_password
     * @return bool success/failed
     */
    public static function alreadyInitialized($host, $root_password) {

        $dm = null;

        try {
            $dm = new DbManager(
                $host,
                'root',
                $root_password
            );
        } catch (Exception $e) {
            $dm = null;
            echo __LINE__ .':' . $e->getMessage() ."\n";
            return false;
        }

        $dm = null;
        return true;
    }

    /**
     * DBが存在するかどうか
     *
     * @param string $db_name
     * @return bool
     */
    public function alreadyExists($db_name) {

        $sth = $this->dbh->prepare('SHOW DATABASES;',  array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute();

        $db_already_exists = false;

        while ($res = $sth->fetch(PDO::FETCH_BOTH)) {
            $exists_db_name = $res['Database'];
            if (strcmp($exists_db_name, $db_name) == 0) {
                $db_already_exists = true;
                break;
            }
        }

        return $db_already_exists;
    }

    /**
     * 指定されたDBとユーザを作る
     *
     * @param string $db_name
     * @param string $db_user
     * @param string $db_password
     * @return bool success/failed
     */
    public function createDBandUser($db_name, $db_user, $db_password) {
        /**
         * DBを作成する
         *
         * DB名にバッククォーテーションを指定する必要がある場合、
         * PDOのbindParamではうまくバインドできないので、しかたなく変数を埋めみ・・・
         */
        $sth = $this->dbh->prepare(
            "CREATE DATABASE IF NOT EXISTS `{$db_name}`;",
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        if ($sth->execute() === false) {
            return false;
        }

        $sth = null;

        /**
         * ユーザを作成する
         *
         * MySQLではユーザ作成時に、「`user`@'localhost'」 とするとsocket経由で接続しようとする。
         * マルチコンテナ環境ではTCP/IP経由で接続するため、ここでは「@'%'」としている
         */
        $sql = <<< EOM
CREATE USER :db_user@'%' IDENTIFIED BY :db_password;
GRANT USAGE ON * . * TO :db_user@'%';
GRANT ALL PRIVILEGES ON `{$db_name}` . * TO :db_user@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOM;
        $sth = $this->dbh->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $sth->bindParam(':db_user', $db_user, PDO::PARAM_STR);
        $sth->bindParam(':db_password', $db_password, PDO::PARAM_STR);

        if ($sth->execute() === false) {
            return false;
        }

        return true;
    }

    /**
     * テーブルを作り、初期データを流し込む
     *
     * @param string $app_path appディレクトリのパス
     * @return bool success/failed
     */
    public function createTables($app_path) {

        foreach(glob("${app_path}/setup-queries/*.sql") as $file){
            if (is_file($file)) {
                $query = file_get_contents($file);
                $sth = $this->dbh->prepare($query);
                if ($sth->execute() === false) {
                    echo "failed execute query:${file}\n";
                    return false;
                }
                echo "executed query:${file}\n";
            }
        }

        return true;
    }
}

/**
 * wfが使用するDBを準備する
 *
 * @param array $host_params
 * @param array $app
 */
function buildEnvironments(array $host_params, array $app) {

    $wait_seconds = 1;
    $wait_times = 50;

    $i = 0;

    // MySQL初回起動時はInitializeに時間がかかるため、mysqlデータベースの作成完了まで（接続できるようになるまで）待つ
    for ($i = 0; $i < $wait_times; $i++) {
        if (DbManager::alreadyInitialized($host_params['db_host'], $host_params['db_root_password']) === true) {
            echo "db has already initialized.\n";
            break;
        }
        echo "{$i}:db is not initialized. wait for a little...\n";
        sleep($wait_seconds);
    }

    if ($i >= $wait_times) {
        exit("db has not initialized. something is wrong...\n");
    }

    $dm = null;

    try {
        // apps用のdbが存在しない可能性があるため、まずはdb名を指定せずに接続する
        $dm = new DbManager(
            $host_params['db_host'],
            'root',
            $host_params['db_root_password']
        );

        // DBがすでに存在するかどうか
        $db_already_exists = $dm->alreadyExists($app['db_name']);

        // DBがなければ用意する
        if ($db_already_exists) {
            echo $app['db_name'] . " is already exists.\n";
        } else {
            echo $app['db_name'] . " is not exists.\n";
            $ret = $dm->createDBandUser(
                $app['db_name'],
                $app['db_user'],
                $app['db_password']
            );
            if ($ret === false) {
                exit("db creating is failed.\n");
            }

            // apps用のdbを作成したので、db名を指定して接続しなおす
            $dm = null;
            $dm = new DbManager(
                $host_params['db_host'],
                'root',
                $host_params['db_root_password'],
                $app['db_name']
            );

            $app_path = $host_params['apps_path'] . '/' . $app['app_name'];

            if ($dm->createTables($app_path) === false) {
                exit("table creating is failed.\n");
            }

            echo $app['db_name'] . " has created in successful.\n";
        }

    } catch (PDOException $e) {
        echo __LINE__ .':' . $e->getMessage() ."\n";
    }

    $dm = null;
}

/**
 * app-db-setup.php
 *
 * @param string this.php
 * @param string DB_HOST DBのホスト名
 * @param string DB_ROOT_PASSWORD DBのルートパスワード
 * @param string APPS_PATH ホストのappsパス
 * @param string BLUEPRINT_FILE_PATH blueprintパス
 *
 * 指定されたWebfile用のDBが存在するかどうか確認し、
 * なければDB、ユーザ、テーブルを作成し、初期データを流し込む
 */
if (true) {
    if ($argc < 4) {
        exit('specified parameter is invalid.');
    }
} else {
    // デバッグ時
    $argv = array('this.php', 'localhost', '1111', '/Users/user/dev/apps', '/Users/user/github/appdock/apps/blueprint/old/apps.csv');
}

$db_host = $argv[1];
$db_root_password = $argv[2];
$apps_path = $argv[3];
$blueprint_file_path = $argv[4];

if (file_exists($blueprint_file_path) === false) {
    exit("{$blueprint_file_path}: No such file.");
}

$csv = new SplFileObject($blueprint_file_path);
$csv->setFlags(SplFileObject::READ_CSV);

$apps = array();

foreach ($csv as $key => $line) {

    $apps[] = array(
        'app_name' => $line[0],
        'db_name'  => $line[1],
        'db_user'  => $line[2],
        'db_password'   => $line[3],
    );
}

$host_params = array(
    'db_host' => $db_host,
    'db_root_password' => $db_root_password,
    'apps_path' => $apps_path
);

foreach ($apps as $key => $app) {
    buildEnvironments($host_params, $app);
}

exit(0);