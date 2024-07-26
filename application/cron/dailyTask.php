<?php

/*
 * how to run from cli:
 * APPLICATION_ENV=development php test.php
 */

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once __DIR__ . '/baseCron.php';

class dailyTask extends Zend_Db_Table_Abstract
{

    protected $_name = 'users_prize';

    public function run()
    {
        try {
            $db = $this->getAdapter();

            print("HI\n");

            return;
        } catch (Exception $ex) {
            return logs(array('status' => false, 'msg' => $ex->getMessage()));
        }
    }

}

function logs($x = "")
{
    echo date('Y-m-d H:i:s') . " : " . json_encode($x) . "\n";
}

logs("start");
$api = new dailyTask();
$api->run();
logs("end");