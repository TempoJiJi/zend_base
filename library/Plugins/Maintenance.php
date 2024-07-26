<?php

class Plugins_Maintenance extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $params = $request->getParams();
        if ($params['module'] != 'api') {
            return;
        }
    }

}
