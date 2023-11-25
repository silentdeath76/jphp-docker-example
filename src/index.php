<?php

use php\http\HttpServer;
use php\http\HttpServerRequest;
use php\http\HttpServerResponse;
use php\sql\SqlConnection;
use php\sql\SqlDriverManager;
use php\sql\SqlException;

// стандратные значения если запускать вне контейнера
$_ENV["APP_PORT"] = $_ENV["APP_PORT"] ?? '80';
$_ENV["POSTGRES_DB"] = $_ENV["POSTGRES_DB"] ?? 'jppm-app';
$_ENV["POSTGRES_USER"] = $_ENV["POSTGRES_USER"] ?? 'jppm-app';
$_ENV["POSTGRES_HOST"] = $_ENV["POSTGRES_HOST"] ?? 'localhost';
$_ENV["POSTGRES_PORT"] = $_ENV["POSTGRES_PORT"] ?? '5432';

# $_ENV["POSTGRES_PASSWORD"] = $_ENV["POSTGRES_PASSWORD"] ?? '123456';

// создание подключения к postgres
try {
    SqlDriverManager::install('postgres');

    $url = 'postgresql://' . trim($_ENV["POSTGRES_HOST"]) . ':5432/' . trim($_ENV["POSTGRES_DB"]);

    echo sprintf("Try connect to: %s\n\r\n", $url);

    $connection = SqlDriverManager::getConnection($url, [
        'user' => trim($_ENV["POSTGRES_USER"]),
        'password' => trim($_ENV["POSTGRES_PASSWORD"]),
        'loginTimeout' => 10,
        'socketTimeout' => 10,
        'connectTimeout' => 10,
        'currentSchema' => "public",
        'useSsl' => false,
    ]);
} catch (Exception $ex) {
    echo "[error] " . $ex->getMessage() . "\n";
}

$lock = '.initdb';

// первичная настройка базы: создание таблиц и пользователей
if (!file_exists($lock)) {
    if ($connection instanceof SqlConnection) {
        try {
            $connection->query("CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username text not null unique,
                password char(32)
            )")->update();

            $connection->query("INSERT INTO users (username, password) values ('admin', '" . md5('123456') . "')")->update();
            $connection->query("INSERT INTO users (username, password) values ('user', '" . md5('654321') . "')")->update();

            file_put_contents($lock, null);
        } catch (SqlException $ex) {
            echo "[error] " . $ex->getMessage() . "\n";
        }
    }
}

// создание web сервера
$server = new HttpServer($_ENV["APP_PORT"]);
$server->get('/', function (HttpServerRequest $request, HttpServerResponse $response) use ($connection) {
    echo "[info] request path: " . $request->path() . "\n";

    if (!($connection instanceof SqlConnection)) {
        $response->body("not connected to db");
        return;
    }

    $temp = [];

    try {
        foreach ($connection->query("select * from users") as $fetch) {
            $temp[] = $fetch->toArray();
        }
    } catch (Exception $ex) {
        $temp = [
            "error" => $ex->getMessage(),
            "file" => $ex->getFile(),
            "line" => $ex->getLine()
        ];
    }


    $response->body("<pre>" . var_export($temp, true) . "</pre>");
});
$server->runInBackground();