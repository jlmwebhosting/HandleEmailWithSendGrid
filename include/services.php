<?php
// Define Pimple DI container

use Slim\Slim;
use Slim\Views\Twig;
use Slim\Extras\Log\DateTimeFileWriter;

$c = new Pimple();

$c['config'] = require dirname(__FILE__) . '/../config/config.php';

$c['app'] = $c->share(function ($c) {
    // Instantiate the application
    $app = new Slim(array(
        'view' => new Twig(),
        'templates.path' => $c['config']['path.templates'],
        'log.writer' => new DateTimeFileWriter(array(
            'path' => $c['config']['path.logs'],
            'name_format' => 'Y-m-d',
            'message_format' => '%label% - %date% - %message%'
        ))
    ));
    return $app;
});

$c['db.pdo'] = function ($c) {
    $config = $c['config'];
    switch (substr($config['db.dsn'], 0, 5)) {
        // MySQL database
        case 'mysql':
            $db = new \PDO(
                $config['db.dsn'],
                $config['db.username'],
                $config['db.password'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'
                )
            );
            break;

        // SQLite database
        case 'sqlit':
            $db = new PDO($config['db.dsn']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            break;

        default:
            throw new UnexpectedValueException('Unknown database');
    }
    return $db;
};

$c['db'] = function ($c) {
    return new NotORM($c['db.pdo']);
};

return $c;
