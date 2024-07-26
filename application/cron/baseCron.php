<?php

// date_default_timezone_set("America/Los_Angeles");
date_default_timezone_set("Asia/Kuala_Lumpur");

function p($x, $b = false)
{
    echo '<pre>';
    print_r($x);
    echo '</pre>';
    if (!$b) {
        die();
    }
}

function dd(...$items)
{
    echo '<pre>';
    array_walk($items, function ($item) {
        var_dump($item);
        echo '<br>';
    });
    echo '</pre>';
    die;
    exit;
}

// Start Boostrap.php without init routes
define("IS_CLI", true);

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
defined('VENDOR_PATH') || define('VENDOR_PATH', realpath(dirname(__FILE__) . '/../../vendor'));

// Define application environment
if (empty(getenv('APPLICATION_ENV'))) {
    die("APPLICATION_ENV is not defined in vhost");
}

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

define('MINIFY_STATIC', APPLICATION_ENV == 'production' ? false : false);

// Composer for whole application
require_once realpath(VENDOR_PATH . '/autoload.php');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

require_once APPLICATION_PATH . '/configs/setting.php';
$application->bootstrap();

$settings = Zend_Registry::get('ini_config');
$db = Zend_Db::factory(
    'pdo_mysql',
    array(
        'host' => $settings['resources']['db']['params']['host'],
        'username' => $settings['resources']['db']['params']['username'],
        'password' => $settings['resources']['db']['params']['password'],
        'dbname' => $settings['resources']['db']['params']['dbname'],
        'charset' => $settings['resources']['db']['params']['charset']
    )
);
$db->setFetchMode(Zend_Db::FETCH_OBJ);
Zend_Registry::set('db_log', $db);
