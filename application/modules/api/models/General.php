<?php

class Model_General extends Zend_Db_Table_Abstract
{

    public $_name = 'ag_notification_log';

    public function versionControl($params)
    {

        $url = '';    // production url
        $server_status = 'production';

        try {

            $db = $this->getAdapter();

            if (isset($params['mobile_type']) && isset($params['version'])) {
                // $mobile = "production_" . $params['mobile_type'];
                $data = $db->query("select server_type from ag_version_control where value=? and mobile_type=? and status=1 limit 1", array($params['version'], $params['mobile_type']))->fetch();
                if (empty($data) || !isset($data['server_type'])) {
                    $server_status = 'update';
                    // $url = "";
                }

                if (isset($data['server_type']) && $data['server_type'] == 'staging') {
                    $server_status = 'staging';
                    $url = '';    // staging url
                }
            } else {
                return array(
                    'status' => false,
                    'code' => 100000,
                    'msg' => 'Missing mobile type and version',
                    'data' => '',
                    'server_status' => 'update'
                );
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $url,
                'server_status' => $server_status
            );
        } catch (Exception $ex) {
            return array(
                'status' => false,
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage(),
                'data' => $url
            );
        }
    }

    public function getDepartment($params)
    {

        try {

            $db = $this->getAdapter();
            $data = $db->query("select id, name from ag_department_table where status=1")->fetchAll();

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

    public function getCommission($params)
    {

        try {

            $db = $this->getAdapter();
            $data = $db->query("select id, max, min, comm from ag_commission_setting where status=1")->fetchAll();

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

    public function getNotificationLog($params)
    {

        try {

            $db = $this->getAdapter();
            $data = [];

            $select = $this->select()->setIntegrityCheck(false)
                ->from(array('a' => 'ag_notification_log'), array('a.type', 'a.body'))
                ->where('a.success=1')
                ->where('a.to_uid=?', $params['uid'])
                ->order('id desc');

			$data = $this->fetchAll($select)->toArray();
            foreach($data as &$d) {
                $d['body'] = json_decode($d['body']);
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



}
