<?php

class Model_Tester extends Zend_Db_Table_Abstract
{

    private $translate;
    public function __construct()
    {
        $this->translate = Zend_Registry::get('translate');
    }

    public function testTranslate()
    {
        try {

            return [
                'status' => true,
                'data' => [],
                'msg' => $this->translate['success'],
                'code' => SUCCESS_CODE
            ];
            
        } catch (Exception $ex) {
            return [
                'status' => false,
                'data' => [],
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode(),
            ];
        }
    }
}
