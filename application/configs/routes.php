<?php

$front = Zend_Controller_Front::getInstance();
$settings = Zend_Registry::get('ini_config');
$appUrl = $settings['app']['url'];

$router = $front->getRouter();
$serverName = $_SERVER['SERVER_NAME'];
$serverPort = $_SERVER['SERVER_PORT'];

switch ($serverName) {
    case $appUrl['api']:
        define('MODULE', 'api');
        break;

    case $appUrl['admin']:
        define('MODULE', 'admin');
        $route = new Zend_Controller_Router_Route(':action', array('controller' => 'index', 'module' => 'admin'));
        $router->addRoute('allindex', $route);
        break;

    case $appUrl['web']:
        define('MODULE', 'web');
        $route = new Zend_Controller_Router_Route(':action', array('controller' => 'index', 'module' => 'web'));
        $router->addRoute('allindex', $route);
        break;

    default:
        echo "Undefined Route!";
        exit;
}

$front->setDefaultModule(MODULE);