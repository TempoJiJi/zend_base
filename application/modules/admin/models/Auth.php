<?php

class Model_Auth extends Zend_Db_Table_Abstract
{

    public $_name = 'ag_admin_table';

    public function login($params = array())
    {
        $db = $this->getAdapter();
        try {
            $data = $db->query("select a.id, a.username, a.email from ag_admin_table as a where a.username=? and a.password=md5(?) and a.status=1 limit 1", array($params['email'], $params['password']))->fetch();

            if (empty($data)) {
                throw new Exception("Invalid credential");
            }

            $db->query("update ag_admin_table set last_login=now() where id=? limit 1", array($data['id']));

            $sess = new Zend_Session_Namespace('Zend_Auth');
            $sess->user = $data;
            $sess->setExpirationSeconds(864000);

            return array(
                'status' => true,
                'data' => $data["id"],
                'msg' => "Submit Successful",
            );
        } catch (Exception $e) {
            return array(
                'status' => false,
                'data' => null,
                'msg' => $e->getMessage(),
            );
        }
    }
}
