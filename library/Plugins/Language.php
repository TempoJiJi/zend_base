<?php

class Plugins_Language extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {

        $availableLang = ['en', 'ch'];
        $tempLanguage = (string)$request->getHeader('language');

        $lang = 'en';   // default
        if (in_array($tempLanguage, $availableLang)) {
            $lang = $tempLanguage;
        }

        $translate = require_once APPLICATION_PATH . "/../lang/$lang.php";
        Zend_Registry::set('translate', $translate);
    }
}