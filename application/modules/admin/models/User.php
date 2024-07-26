<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_User extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';


    public function userList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_table'], ["a.id", "a.username", "a.full_name", "a.phone", "a.email", "a.dob", "a.status"])
                ->joinLeft(['b' => 'ag_user_group'], 'a.id = b.uid and b.status=1', [])
                ->joinLeft(['c' => 'ag_user_group_table'], 'b.group_id = c.id', ["c.name as group_name"])
                // ->joinLeft(['d' => 'ag_user_bank'], 'a.id = d.uid and d.status=1', ["d.bank_name", "d.bank_acc"])
                // ->joinLeft(['e' => 'ag_trans_log'], 'e.uid = a.id and e.status=1', ["coalesce(sum(credit-debit), 0) as balance"])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_table'], ["count(1) as allcount"])
                ->joinLeft(['b' => 'ag_user_group'], 'a.id = b.uid and b.status=1', [])
                ->joinLeft(['c' => 'ag_user_group_table'], 'b.group_id = c.id', [])
                // ->joinLeft(['d' => 'ag_user_bank'], 'a.id = d.uid and d.status=1', ["d.bank_name", "d.bank_acc"])
                // ->joinLeft(['e' => 'ag_trans_log'], 'e.uid = a.id and e.status=1', ["coalesce(sum(credit-debit), 0) as balance"])
                ->where('a.status in (0,1)');

            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {

                if (isset($params['search_uid_name'])) {
                    $searchName = $params['search_uid_name'];

                    $sql1->where("a.id=? or a.username like '%$searchName%'", $searchName);
                }
                if (isset($params['search_username_fullname'])) {
                    $searchName = $params['search_username_fullname'];

                    $sql1->where("a.username like '%$searchName%' or a.full_name like '%$searchName%' ");
                }

                $data = $this->fetchAll($sql1)->toArray();
            } else {
                $data = $common_model->tablePaginator($params, $sql1, $sql2, '');
                if ($data['status'] == false) {
                    throw new Exception($data['msg'], $data['code']);
                }
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

    public function userAdd($params)
    {
        try {

            $bank_model = new Model_Bank();

            $userGroup = $this->userGroup($params);
            $bankData = $bank_model->banks(array('not_pagin' => 1));

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array(
                    'group_data' => $userGroup['data'] ?? array(),
                    'banks_data' => $bankData['data'] ?? array()
                )
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

    public function userEdit($params)
    {
        try {
            $db = $this->getAdapter();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_table'], ["a.id", "a.username", "a.full_name", "a.phone", "a.email", "a.dob", "a.referral_uid"])
                ->joinLeft(['b' => 'ag_user_group'], 'a.id = b.uid and b.status=1', [])
                ->joinLeft(['c' => 'ag_user_group_table'], 'b.group_id = c.id', ["c.id as group_id", "c.name as group_name"])
                ->joinLeft(['d' => 'ag_user_table'], 'd.id = a.referral_uid', ["d.username as referral_username"])
                ->where('a.status in (0,1)')
                ->where('a.id=?', $params['item_id']);

            $data = $this->fetchRow($sql1)->toArray();

            $bankData = $db->query("select id, bank_name, bank_acc, id as value, concat(bank_name, ' (', bank_acc, ')') as text from ag_user_bank where uid=? and status=1 order by id asc", array($params['item_id']))->fetchAll();
            $data['bank_data'] = $bankData;

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

    public function userAddSubmit($params)
    {
        try {

            $db = $this->getAdapter();
            $db->beginTransaction();

            if (isset($params['item_id']) && !empty($params['item_id'])) {

                $uid = $params['item_id'];

                $flag = $db->query("select id from ag_user_table where username=? and status in (0,1) and id!=? limit 1", array($params['username'], $params['item_id']))->fetch();
                if (!empty($flag) || isset($flag['id'])) {
                    throw new Exception("Username Existed", ADMIN_SHOW_ERR);
                }
                $flag = $db->query("select id from ag_user_table where full_name=? and status in (0,1) and id!=? limit 1", array($params['full_name'], $params['item_id']))->fetch();
                if (!empty($flag) || isset($flag['id'])) {
                    throw new Exception("Full Name Existed", ADMIN_SHOW_ERR);
                }

                $db->update("ag_user_table", array(
                    'username' => $params['username'],
                    'full_name' => $params['full_name'],
                    'phone' => $params['phone'],
                    'email' => empty($params['email']) ? null : $params['email'],
                    'dob' => empty($params['dob']) ? null : $params['dob'],
                    'referral_uid' => ($params['referral_uid'] == 'null') ? 0 : $params['referral_uid'],
                ), ['id=?' => $params['item_id']]);

                $db->query("update ag_user_group set status=0 where uid=? and status=1", array($uid));

                /**
                 * Reset previous bank info
                 *  如果做duplicate checking，但是只改其中一個info會很麻煩
                 *   所以直接把之前的bank info都reset重新insert once edit
                 *    withdrawal那邊需要注意，show info的時候不需要check status
                 */
                $db->query("update ag_user_bank set status=0 where uid=? and status=1", array($uid));

            } else {

                $flag = $db->query("select id from ag_user_table where username=? and status in (0,1) limit 1", array($params['username']))->fetch();
                if (!empty($flag) && isset($flag['id'])) {
                    throw new Exception("Username Existed", ADMIN_SHOW_ERR);
                }
                $flag = $db->query("select id from ag_user_table where full_name=? and status in (0,1) limit 1", array($params['full_name']))->fetch();
                if (!empty($flag) && isset($flag['id'])) {
                    throw new Exception("Full Name Existed", ADMIN_SHOW_ERR);
                }

                $db->insert("ag_user_table", array(
                    'username' => $params['username'],
                    'full_name' => $params['full_name'],
                    'phone' => $params['phone'],
                    'email' => empty($params['email']) ? null : $params['email'],
                    'dob' => empty($params['dob']) ? null : $params['dob'],
                    'referral_uid' => ($params['referral_uid'] == 'null') ? 0 : $params['referral_uid'],
                ));

                $uid = $db->lastInsertId();
            }

            // user bank
            $bankName = explode(',', $params['bank_name']);
            $bankAcc = explode(',', $params['bank_acc']);
            for($i = 0; $i < sizeof($bankName);$i++) {

                if (empty($bankName[$i]) || empty($bankAcc[$i])) {
                    throw new Exception("Missing Bank Info", ADMIN_SHOW_ERR);
                }

                $db->insert("ag_user_bank", array(
                    'uid' => $uid,
                    'bank_name' => $bankName[$i],
                    'bank_acc' => $bankAcc[$i],
                ));
            }

            $db->insert("ag_user_group", array(
                'uid' => $uid,
                'group_id' => $params['group_id'],
            ));

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

    public function userBlock($params)
    {
        try {

            $db = $this->getAdapter();

            $db->query("update ag_user_table set status=abs(status-1) where id=? limit 1", array($params['item_id']));

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => null
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



    public function userGroup($params)
    {
        try {

            $db = $this->getAdapter();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_group_table'], ["*"])
                ->where('a.status=1');

            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $sql1->where('a.id=?', $params['item_id'])->limit(1);
            }

            $data = $this->fetchAll($sql1)->toArray();

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


    public function userGroupList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_group_table'], ["*"])
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

    public function userGroupAdd($params)
    {
        try {

            $db = $this->getAdapter();
            $db->beginTransaction();

            $flag = $db->query("select id from ag_user_group_table where name=? and id!=? limit 1", array($params['name'], $params['item_id']))->fetch();
            if (!empty($flag) && isset($flag['id'])) {
                throw new Exception("Group Name duplicate", ADMIN_SHOW_ERR);
            }

            // insert and update array
            $updateArr = array('name' => $params['name']);

            // get all setting type
            $settingType = $db->query("select name from ag_user_group_setting_type where status=1")->fetchAll();

            if (isset($params['item_id']) && !empty($params['item_id'])) {

                $groupData = $db->query("select * from ag_user_group_table where id=? limit 1", array($params['item_id']))->fetch();

                foreach($settingType as $d) {

                    // setting type key
                    $type = $d['name'];

                    // check is current value equal to old value
                    if (isset($params[$type]) && isset($groupData[$type]) && $params[$type] != $groupData[$type]) {
                        $updateArr[$type] = $params[$type];

                        // log the new value
                        $db->insert('ag_user_group_setting_log', array(
                            'aid' => $params['aid'],
                            'group_id' => $params['item_id'],
                            'type' => $type,
                            'val' => $params[$type]
                        ));

                        $updateArr[$type] = $params[$type];
                    }
                }

                $db->update('ag_user_group_table', $updateArr, ['id=?' => $params['item_id']]);
            } else {

                foreach($settingType as $d) {
                    // setting type key
                    $type = $d['name'];

                    if (isset($params[$type])) {
                        $updateArr[$type] = $params[$type];
                    }
                }

                $db->insert('ag_user_group_table', $updateArr);
            }

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



}
