<?php

/*
 * how to run from cli:
 * APPLICATION_ENV=development php test.php
 */

date_default_timezone_set('Asia/Kuala_Lumpur');
define("_CRONJOB_", true);

require_once __DIR__ . '/../../public/index.php';

class dailyTask extends Zend_Db_Table_Abstract
{

    protected $_name = 'users_fitness_log';

    public function run()
    {
        try {
            $db = $this->getAdapter();
            $db->beginTransaction();
            $db->query("truncate table ag_calendar");

            $start = '2022-01-01';
            $end = '2023-12-31';
            while ($start <= $end) {
                $db->query("insert into ag_calendar (date) values (?)", array($start));
				$start = date('Y-m-d', strtotime($start . "+1 days"));
                echo $start . "\n";
            }

            $db->commit();

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

$api = new dailyTask();
$api->run();
