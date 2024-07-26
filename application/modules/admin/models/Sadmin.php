<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Sadmin extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';


    public function sadminList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_admin_table'], ["a.id", "a.fullname", "a.username", "a.last_login", "a.status"])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_admin_table'], ["count(1) as allcount"])
                ->where('a.status in (0,1)');

            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {

                if (isset($params['admin_id']) && !empty($params['admin_id'])) {
                    $sql1->where('a.id=?', $params['admin_id']);
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

    public function sadminMenu($params)
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query("select id, display_name from ag_admin_menu where status=1")->fetchAll();

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


    public function sadminAdd($params)
    {
        try {

            $db = $this->getAdapter();

            $adminMenu = $this->sadminMenu($params);

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $adminMenu['data'] ?? array()
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

    public function sadminAddSubmit($params)
    {
        $common_model = new Model_Common();
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $itemId = 0;
            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $itemId = $params['item_id'];
            }

            $flag = $db->query("select id from ag_admin_table where username=? and id!=? limit 1", array($params['username'], $itemId))->fetch();
            if (!empty($flag) && isset($flag['id'])) {
                throw new Exception("Username Exists", ADMIN_SHOW_ERR);
            }

            $arr = [
                'username' => $params['username'],
                'fullname' => $params['fullname'],
            ];

            if (isset($params['password']) && !empty($params['password'])) {
                $arr['password'] = md5($params['password']);
            } 

            if ($itemId == 0) {
                $db->insert("ag_admin_table", $arr);
                $itemId = $db->lastInsertId();

            } else {
                $db->update("ag_admin_table", $arr, ['id = ?' => $itemId]);

                // reset acl
                $db->update("ag_admin_access", ['status' => 0], ['aid = ?' => $itemId]);
            }

            $accessList = explode(',', $params['access_list']);
            foreach($accessList as $acl) {
                $db->insert('ag_admin_access', array(
                    'aid' => $itemId,
                    'acl' => $acl
                ));
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


    public function sadminEdit($params)
    {
        try {
            $db = $this->getAdapter();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_admin_table'], ["id", "username", 'fullname'])
                ->where('a.status in (0,1)')
                ->where('a.id=?', $params['item_id']);

            $data = $this->fetchRow($sql1)->toArray();

            $accessList = $db->query("select acl from ag_admin_access where aid=? and status=1", $params['item_id'])->fetchAll();
            $acl = [];
            foreach($accessList as $a) {
                $acl[] = $a['acl'];
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array(
                    'admin_data' => $data,
                    'acl' => $acl
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

    public function sadminBlock($params)
    {
        try {

            $db = $this->getAdapter();

            $db->query("update ag_admin_table set status=abs(status-1) where id=? limit 1", array($params['item_id']));

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

    public function sadminGetAcl($params)
    {
        try {

            $sql = $this->select()->setIntegrityCheck(false)
            ->from(['a' => 'ag_admin_access'], ["a.acl"])
            ->joinLeft(['b' => 'ag_admin_menu'], 'b.id = a.acl and b.status=1', ["b.controller", "b.model"])
            ->where('a.status=1')
            ->where('a.aid=?', $params['aid']);

            if (isset($params['c_controller'])) {
                $sql->where('b.controller=?', $params['c_controller']);
            }
            if (isset($params['c_model'])) {
                $sql->where('b.model=?', $params['c_model']);
            }

            $acl = $this->fetchAll($sql)->toArray();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $acl
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
