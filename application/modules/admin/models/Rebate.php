<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Rebate extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';


    public function rebateSetting($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_group_table'], ["a.id", "a.name as group_name", "TRUNCATE(a.rebate_percentage * 100, 0) as rebate_percentage", "a.rebate_active", "a.rebate_min", "a.rebate_max"])
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

    public function rebateSettingEdit($params)
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

            $db->insert("ag_rebate_setting_log", array(
                'aid' => $params['aid'],
                'group_id' => $params['item_id'],
                'percentage' => $percentage,
                'min_amount' => $params['min_amount'],
                'max_amount' => $params['max_amount'],
                'is_active' => $params['is_active'],
            ));

            $db->update("ag_user_group_table", array(
                'rebate_percentage' => $percentage,
                'rebate_max' => $params['max_amount'],
                'rebate_min' => $params['min_amount'],
                'rebate_active' => $params['is_active'],
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

    public function rebateFile($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_file'], ["a.id", "a.file_name", "a.is_done", "a.created_at"])
                ->where('a.status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_file'], ["count(1) as allcount"])
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

    public function rebateUpload($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $db->insert("ag_rebate_file", [
                'file_name' => $_FILES['file_excel']['name']
            ]);

            $fileId = $db->lastInsertId();
            $insertArr = [];

            if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                $sheets = $xlsx->sheetNames();

                for ($x = 0; $x <= sizeof($sheets); $x++) {

                    foreach($xlsx->rows($x) as $key => $value) {

                        if ($value[0] == 'username' || $value[1] == 'turnover' || $value[2] == 'provider') {
                            continue;
                        }

                        $username = $value[0];
                        $turnover = abs($value[1]);
                        // $provider = $value[2];

                        $userData = $db->query("select id from ag_user_table where username=? limit 1", array($username))->fetch();

                        $db->insert("ag_rebate_file_detail", array(
                            'uid' => $userData['id'] ?? 0,
                            'file_id' => $fileId,
                            'username' => $username,
                            'turnover' => $turnover,
                            // 'provider' => $provider
                        ));

                        if (!isset($insertArr[$username])) {
                            $insertArr[$username] = [];
                            $insertArr[$username]['uid'] = $userData['id'] ?? 0;
                            $insertArr[$username]['username'] = $username;
                            $insertArr[$username]['turnover'] = 0;
                        }

                        $insertArr[$username]['turnover'] += $turnover;

                    }
                }

            } else {
                throw new Exception("File must be xlsx", ADMIN_SHOW_ERR);
            }

            foreach($insertArr as $d) {
                $db->insert("ag_rebate_sum", array(
                    'uid' => $d['uid'],
                    'file_id' => $fileId,
                    'username' => $d['username'],
                    'turnover' => $d['turnover'],
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

    public function rebateDetail($params)
    {

        $db = $this->getAdapter();
        $db->beginTransaction();

        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["a.id", "a.uid", "a.username", "a.turnover", "a.rebate_amount", "TRUNCATE(a.rebate_percentage*100,0) as rebate_percentage", "a.rebate_status", "a.sys_remark", "a.is_tick"])
                ->where('a.file_id=?', $params['item_id'])
                ->where('a.status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["count(1) as allcount"])
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

    public function rebateCalculate($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $common_model = new Model_Common();
        $trans_model = new Model_Trans();

        $rebateTransType = 1;

        try {

            $flag = $db->query("select is_done from ag_rebate_file where id=? limit 1 for update", array($params['item_id']))->fetch();
            if (empty($flag) || !isset($flag['is_done'])) {
                throw new Exception("rebate file not found", 0);
            }

            if ($flag['is_done'] == 1) {
                throw new Exception("rebate calculation duplicate", ADMIN_SHOW_ERR);
            }
            
            // get all user group setting
            $userGroupTable = $db->query("select * from ag_user_group_table where status=1")->fetchAll();
            $userGroupSetting = [];
            foreach($userGroupTable as $u) {
                $userGroupSetting[$u['id']] = $u;
            }

            // get all user turnover
            $sql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["a.id", "a.username", "a.turnover"])
                ->joinLeft(['b' => 'ag_user_table'], 'b.username = a.username and b.status=1', ["coalesce(b.id, 0) as uid", "b.status as user_status"])
                ->joinLeft(['c' => 'ag_user_group'], 'c.uid = b.id and c.status=1', ["coalesce(c.group_id, 0) as group_id"])
                ->where('a.status=1')
                ->where('a.rebate_status=0')    // prevent duplicate calculation
                ->where('a.file_id=?', $params['item_id']);
            $turnoverData = $this->fetchAll($sql)->toArray();
            
            foreach($turnoverData as $d) {

                /**
                 * 0: pending
                 * 1: approved  // only this will insert trans_log
                 * 2: failed
                 */
                $rebateStatus = 1;

                $groupId = $d['group_id'];
                $sysRemark = null;

                ######### Rebate Setting #########
                // get rebate percentage
                $rebatePercentage = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['rebate_percentage'])) {
                    $rebatePercentage = $userGroupSetting[$groupId]['rebate_percentage'];
                }

                // get rebate min release amount
                $rebateMin = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['rebate_min'])) {
                    $rebateMin = $userGroupSetting[$groupId]['rebate_min'];
                }

                // get rebate max release amount
                $rebateMax = 0;
                if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['rebate_max'])) {
                    $rebateMax = $userGroupSetting[$groupId]['rebate_max'];
                }
                ###############################################################
                             
                ######### rebate status checking #########
                if (!isset($d['uid']) || $d['uid'] == 0) {
                    $sysRemark = 'Member not found';
                    $rebateStatus = 2;
                }

                else if (!isset($d['user_status']) || $d['user_status'] == 0) {
                    $sysRemark = 'Member Status Inactive';
                    $rebateStatus = 2;
                }

                else if (!isset($d['group_id']) || $d['group_id'] == 0) {
                    $sysRemark = 'Member group not found';
                    $rebateStatus = 2;
                }
                
                else if (isset($userGroupSetting[$groupId]) && isset($userGroupSetting[$groupId]['rebate_active'])) {
                    if ($userGroupSetting[$groupId]['rebate_active'] != 1) {
                        $sysRemark = 'Member group not entitled';
                        $rebateStatus = 2;
                    }
                }
                ######################################################

                // Calculate Rebate
                $rebateAmount = $common_model->setDecimal($d['turnover'] * $rebatePercentage, 2);
                $rebateAmountOri = $rebateAmount;
                if ($rebateAmount < $rebateMin) {
                    $rebateAmount = $rebateMin;
                }
                if ($rebateAmount > $rebateMax) {
                    $rebateAmount = $rebateMax;
                }

                $update = array(
                    'uid' => $d['uid'],
                    'group_id' => $d['group_id'],
                    'rebate_amount' => $rebateAmount,
                    'rebate_amount_ori' => $rebateAmountOri,
                    'rebate_percentage' => $rebatePercentage,
                    'rebate_status' => $rebateStatus,
                    'sys_remark' => $sysRemark
                );
                
                // update turnover table
                $db->update("ag_rebate_sum", $update, array('id=?' => $d['id']));                

                // insert into trans log
                if ($rebateStatus == 1 && $rebateAmount > 0) {
                    $flag = $trans_model->transLogInsert(array(
                        'aid' => $params['aid'],
                        'uid' => $d['uid'],
                        'type' => 'credit', 
                        'amount' => $rebateAmount,
                        'ref_id' => $d['id'],
                        'trans_type_id' => $rebateTransType,
                        'is_credit_point' => 1,
                    ));
                    if (empty($flag) || !isset($flag['status']) || !$flag['status']) {
                        throw new Exception($flag['msg'], $flag['code']);
                    }        
                }
            }

            $db->update('ag_rebate_file', array('is_done' => 1), array('id=?' => $params['item_id']));

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

    public function rebateReport($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["a.uid", "a.username", "sum(a.turnover) as turnover", "sum(a.rebate_amount) as rebate_amount"])
                ->where('a.status=1')
                ->where('a.rebate_status=1');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["count(1) as allcount"])
                ->where('a.status=1')
                ->where('a.rebate_status=1');

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

    public function rebateReportAll($params)
    {
        try {

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_rebate_sum'], ["count(distinct(a.username)) as total_count", "sum(a.turnover) as total_turnover", "sum(a.rebate_amount) as total_rebate"])
                ->where('a.status=1');           
                // ->where('a.rebate_status=1');

            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $sql1->where('a.file_id=?', $params['item_id']);
            } else {
                $sql1->where('a.rebate_status=1');
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

    public function rebateTick($params)
    {

        $db = $this->getAdapter();

        try {

            if (!in_array($params['is_tick'], [0,1])) {
                throw new Exception("is_tick invalid", 0);
            }

            $db->update('ag_rebate_sum', array(
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
