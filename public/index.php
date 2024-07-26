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

defined('PUBLIC_PATH') || define('PUBLIC_PATH', realpath(dirname(__FILE__) . '/../public'));
// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('VENDOR_PATH') || define('VENDOR_PATH', realpath(dirname(__FILE__) . '/../vendor'));

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

/** Zend Config */
$configs = [APPLICATION_PATH . '/configs/application.ini'];
switch (APPLICATION_ENV) {
    case 'development':
        array_push($configs, APPLICATION_PATH . '/configs/appenv.development.ini');
        break;
    case 'staging':
        array_push($configs, APPLICATION_PATH . '/configs/appenv.staging.ini');
        break;
    case 'production':
        array_push($configs, APPLICATION_PATH . '/configs/appenv.production.ini');
        break;
    default:
        echo "APPLICATION_ENV Invalid";
        exit;
}

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    [
        'config' => $configs
    ]
);

$application->bootstrap()->run();
