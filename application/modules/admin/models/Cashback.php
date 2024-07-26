<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Cashback extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';


    public function cashbackSetting($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_group_table'], ["a.id", "a.name as group_name", "TRUNCATE(a.cashback_percentage * 100, 0) as cashback_percentage", "a.cashback_active", "a.cashback_min", "a.cashback_max"])
                ->where('a.status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_group_table'], ["count(1) as allcount"])
                ->where('a.status=1');

            $data = $common_model->tablePaginator($params, $sql1, $sql2, '');
            if ($data['status'] == false) {
                throw new Exception($data['msg'], $data['code']);
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $data
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackSettingEdit($params)
    {
        $db = $this->getAdapter();
        $common_model = new Model_Common();
        $db->beginTransaction();

        try {

            if ($params['min_amount'] < 0 || $params['max_amount'] < 0) {
                throw new Exception("Release amount can't be negative value", ADMIN_SHOW_ERR);
            }
            $percentage = $params['percentage'];
            $percentage = $common_model->setDecimal($percentage/100, 2);
            if ($percentage > 100 || $percentage < 0) {
                throw new Exception("Percentage must between 0 to 100", ADMIN_SHOW_ERR);
            }


            $db->insert("ag_cashback_setting_log", array(
                'aid' => $params['aid'],
                'group_id' => $params['item_id'],
                'percentage' => $percentage,
                'min_amount' => $params['min_amount'],
                'max_amount' => $params['max_amount'],
                'is_active' => $params['is_active'],
            ));

            $db->update("ag_user_group_table", array(
                'cashback_percentage' => $percentage,
                'cashback_max' => $params['max_amount'],
                'cashback_min' => $params['min_amount'],
                'cashback_active' => $params['is_active'],
            ), ['id=?' => $params['item_id']]);

            $db->commit();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => null
            );
        } catch (Exception $ex) {
            $db->rollBack();

            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackFile($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_file'], ["a.id", "a.file_name", "a.is_done", "a.created_at"])
                ->where('a.status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_file'], ["count(1) as allcount"])
                ->where('a.status=1');

            $data = $common_model->tablePaginator($params, $sql1, $sql2, '');
            if ($data['status'] == false) {
                throw new Exception($data['msg'], $data['code']);
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $data
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackUpload($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $db->insert("ag_cashback_file", [
                'file_name' => $_FILES['file_excel']['name']
            ]);

            $fileId = $db->lastInsertId();
            $insertArr = [];

            if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                $sheets = $xlsx->sheetNames();

                for ($x = 0; $x <= sizeof($sheets); $x++) {

                    foreach($xlsx->rows($x) as $key => $value) {

                        if ($value[0] == 'username') {
                            continue;
                        }

                        $username = $value[0];
                        $winAmount = abs(empty($value[1]) ? 0 : $value[1]);
                        $loseAmount = abs(empty($value[2]) ? 0 : $value[2]);
                        // $provider = $value[3];

                        $userData = $db->query("select id from ag_user_table where username=? limit 1", array($username))->fetch();

                        $db->insert("ag_cashback_file_detail", array(
                            'uid' => $userData['id'] ?? 0,
                            'file_id' => $fileId,
                            'username' => $username,
                            'win_amount' => $winAmount,
                            'lose_amount' => $loseAmount,
                            // 'provider' => $provider
                        ));

                        if (!isset($insertArr[$username])) {
                            $insertArr[$username] = [];
                            $insertArr[$username]['uid'] = $userData['id'] ?? 0;
                            $insertArr[$username]['username'] = $username;
                            $insertArr[$username]['win_amount'] = 0;
                            $insertArr[$username]['lose_amount'] = 0;
                        }

                        $insertArr[$username]['win_amount'] += $winAmount;
                        $insertArr[$username]['lose_amount'] += $loseAmount;
                    }
                }

            } else {
                throw new Exception("File must be xlsx", ADMIN_SHOW_ERR);
            }

            foreach($insertArr as $d) {
                $db->insert("ag_cashback_sum", array(
                    'uid' => $d['uid'],
                    'file_id' => $fileId,
                    'username' => $d['username'],
                    'win_amount' => $d['win_amount'],
                    'lose_amount' => $d['lose_amount'],
                ));
            }

            $db->commit();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array()
            );


        } catch (Exception $ex) {
            $db->rollBack();

            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackDetail($params)
    {

        $db = $this->getAdapter();

        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["a.id", "a.uid", "a.username", "a.win_amount", "a.lose_amount", "a.cashback_amount", "TRUNCATE(a.cashback_percentage*100, 0) as cashback_percentage", "a.cashback_status", "a.sys_remark", "a.is_tick"])
                ->where('a.file_id=?', $params['item_id'])
                ->where('a.status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["count(1) as allcount"])
                ->where('a.file_id=?', $params['item_id'])
                ->where('a.status=1');

            $data = $common_model->tablePaginator($params, $sql1, $sql2, '');
            if ($data['status'] == false) {
                throw new Exception($data['msg'], $data['code']);
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $data
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackCalculate($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $common_model = new Model_Common();
        $trans_model = new Model_Trans();

        $cashbackTransType = 2;

        try {

            $flag = $db->query("select is_done from ag_cashback_file where id=? limit 1 for update", array($params['item_id']))->fetch();
            if (empty($flag) || !isset($flag['is_done'])) {
                throw new Exception("cashback file not found", 0);
            }

            if ($flag['is_done'] == 1) {
                throw new Exception("cashback calculation duplicate", ADMIN_SHOW_ERR);
            }
            
            // get all user group setting
            $userGroupTable = $db->query("select * from ag_user_group_table where status=1")->fetchAll();
            $userGroupSetting = [];
            foreach($userGroupTable as $u) {
                $userGroupSetting[$u['id']] = $u;
            }

            // get all user win lose record
            $sql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["a.id", "a.username", "a.win_amount", "a.lose_amount"])
                ->joinLeft(['b' => 'ag_user_table'], 'b.username = a.username', ["coalesce(b.id, 0) as uid", "b.status as user_status"])
                ->joinLeft(['c' => 'ag_user_group'], 'c.uid = b.id and c.status=1', ["coalesce(c.group_id, 0) as group_id"])
                ->where('a.status=1')
                ->where('a.cashback_status=0')    // prevent duplicate calculation
                ->where('a.file_id=?', $params['item_id']);
            $cashbackData = $this->fetchAll($sql)->toArray();
            foreach($cashbackData as $d) {

                /**
                 * 0: pending
                 * 1: approved  // only this will insert trans_log
                 * 2: failed
                 */
                $cashbackStatus = 1;

                $groupId = $d['group_id'];
                $sysRemark = null;

                ######### Cashback Setting #########
                // get cashback percentage
                $cashbackPercentage = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['cashback_percentage'])) {
                    $cashbackPercentage = $userGroupSetting[$groupId]['cashback_percentage'];
                }

                // get cashback min release amount
                $cashbackMin = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['cashback_min'])) {
                    $cashbackMin = $userGroupSetting[$groupId]['cashback_min'];
                }

                // get cashback max release amount
                $cashbackMax = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['cashback_max'])) {
                    $cashbackMax = $userGroupSetting[$groupId]['cashback_max'];
                }
                ###############################################################
                             
                ######### cashback status checking #########
                if (!isset($d['uid']) || $d['uid'] == 0) {
                    $sysRemark = 'Member not found';
                    $cashbackStatus = 2;
                }

                else if (!isset($d['user_status']) || $d['user_status'] == 0) {
                    $sysRemark = 'Member Status Inactive';
                    $cashbackStatus = 2;
                }

                else if (!isset($d['group_id']) || $d['group_id'] == 0) {
                    $sysRemark = 'Member group not found';
                    $cashbackStatus = 2;
                }
                
                else if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['cashback_active'])) {
                    if ($userGroupSetting[$groupId]['cashback_active'] != 1) {
                        $sysRemark = 'Member group not entitled';
                        $cashbackStatus = 2;
                    }
                }
                ######################################################

                // Calculate Cashback
                // $cashbackAmount = $common_model->setDecimal(($d['win_amount'] - $d['lose_amount']) * $cashbackPercentage, 2);
                $cashbackAmount = $common_model->setDecimal(abs($d['lose_amount']) * $cashbackPercentage, 2);
                $cashbackAmountOri = $cashbackAmount;
                if ($cashbackAmount < $cashbackMin) {
                    $cashbackAmount = $cashbackMin;
                }
                if ($cashbackAmount > $cashbackMax) {
                    $cashbackAmount = $cashbackMax;
                }

                $update = array(
                    'uid' => $d['uid'],
                    'group_id' => $d['group_id'],
                    'cashback_amount' => $cashbackAmount,
                    'cashback_amount_ori' => $cashbackAmountOri,
                    'cashback_percentage' => $cashbackPercentage,
                    'cashback_status' => $cashbackStatus,
                    'sys_remark' => $sysRemark
                );
                
                // update turnover table
                $db->update("ag_cashback_sum", $update, array('id=?' => $d['id']));                

                // insert into trans log
                if ($cashbackStatus == 1 && $cashbackAmount > 0) {
                    $flag = $trans_model->transLogInsert(array(
                        'aid' => $params['aid'],
                        'uid' => $d['uid'],
                        'type' => 'credit', 
                        'amount' => $cashbackAmount,
                        'ref_id' => $d['id'],
                        'trans_type_id' => $cashbackTransType,
                        'is_credit_point' => 1,
                    ));
                    if (empty($flag) || !isset($flag['status']) || !$flag['status']) {
                        throw new Exception($flag['msg'], $flag['code']);
                    }        
                }
            }

            $db->update('ag_cashback_file', array('is_done' => 1), array('id=?' => $params['item_id']));

            $db->commit();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array()
            );

        } catch (Exception $ex) {
            $db->rollBack();

            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackReport($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["a.uid", "a.username", "sum(a.win_amount) as win_amount", "sum(a.lose_amount) as lose_amount", "sum(a.cashback_amount) as cashback_amount"])
                ->where('a.status=1')
                ->where('a.cashback_status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["count(1) as allcount"])
                ->where('a.status=1')
                ->where('a.cashback_status=1');

            if (isset($params['start_date']) && !empty($params['start_date'])) {
                $startDate = $params['start_date'];
                $sql1->where('date(a.created_at)>=?', $startDate);
                $sql2->where('date(a.created_at)>=?', $startDate);
            }
            if (isset($params['end_date']) && !empty($params['end_date'])) {
                $endDate = date('Y-m-d', strtotime($params['end_date'] . "+1 Days"));
                $sql1->where('date(a.created_at)<?', $endDate);
                $sql2->where('date(a.created_at)<?', $endDate);
            }
    
            $data = $common_model->tablePaginator($params, $sql1, $sql2, ' group by a.username ');
            if ($data['status'] == false) {
                throw new Exception($data['msg'], $data['code']);
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $data
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackReportAll($params)
    {
        try {

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_cashback_sum'], ["count(distinct(a.username)) as total_count", "sum(a.win_amount) as total_win", "sum(a.lose_amount) as total_lose", "sum(a.cashback_amount) as total_cashback"])
                ->where('a.status=1');           
                // ->where('a.cashback_status=1');

            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $sql1->where('a.file_id=?', $params['item_id']);
            } else {
                $sql1->where('a.cashback_status=1');
            }
    
            $data = $this->fetchRow($sql1)->toArray();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $data
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

    public function cashbackTick($params)
    {

        $db = $this->getAdapter();

        try {

            if (!in_array($params['is_tick'], [0,1])) {
                throw new Exception("is_tick invalid", 0);
            }

            $db->update('ag_cashback_sum', array(
                'is_tick' => $params['is_tick']
            ), ['id=?' => $params['item_id']]);

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array()
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => null
            );
        }
    }

}
