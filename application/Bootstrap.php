<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initRoutes()
    {
        $ini_config = $this->getOptions();
        Zend_Registry::set('ini_config', $ini_config);

        if (!defined('IS_CLI')) {
            // Application Constant
            include APPLICATION_PATH . "/configs/constant.php";

            //register routes
            $router = Zend_Controller_Front::getInstance()->getRouter();
            include APPLICATION_PATH . "/configs/routes.php";
        }
    }

    public function _initLoader()
    {
        //register helper
        Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH . '/helpers', 'Helper_');
    }

    protected function _initDbAdaptersToRegistry()
    {

        /**
         * For logging, use this db to prevent rollback for log table
         */

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
    }

    // protected function _initTranslate()
    // {
        // $translate = new Zend_Translate(
        //     [
        //         'adapter' => 'array',
        //         'content' => APPLICATION_PATH . '/../lang/en.php',
        //         'locale'  => 'en'
        //     ]
        // );
        // $translate->addTranslation(
        //     [
        //         'adapter' => 'array',
        //         'content' => APPLICATION_PATH . '/../lang/ch.php',
        //         'locale'  => 'cn'
        //     ]
        // );

        // Zend_Registry::set('translate', $translate);
    // }
}
