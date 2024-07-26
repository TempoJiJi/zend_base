<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Promo extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';

    public function promoList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_promo_table'], ["a.id", "a.aid", "a.uid", "a.amount", "coalesce(a.remark, '') as remark", "a.created_at", "a.status"])
                ->joinLeft(['b' => 'ag_user_table'], "b.id=a.uid", ['b.username', 'b.full_name'])
                ->joinLeft(['c' => 'ag_admin_table'], "c.id=a.aid", ['c.username as admin_username'])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_promo_table'], ["count(1) as allcount"])
                ->joinLeft(['b' => 'ag_user_table'], "b.id=a.uid", [])
                ->joinLeft(['c' => 'ag_admin_table'], "c.id=a.aid", [])
                ->where('a.status in (0,1)');

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

    public function promoReport($params)
    {
        try {

            $db = $this->getAdapter();

            $data = $db->query("select count(1) as total_count, coalesce(sum(amount), 0) as total_amount from ag_promo_table where status=1")->fetch();

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


    public function promoAdd($params)
    {
        try {

            $db = $this->getAdapter();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_promo_table'], ["*"])
                ->joinLeft(['b' => 'ag_user_table'], "b.id=a.uid", ['b.username', 'b.full_name'])
                ->where('a.status in (0,1)')
                ->order('a.id desc')
                ->limit(10);

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

    public function promoAddSubmit($params)
    {
        $common_model = new Model_Common();
        $trans_model = new Model_Trans();
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $amount = $common_model->setDecimal($params['amount'], 2);

            if ($amount <= 0) {
                throw new Exception("Invalid Amount", ADMIN_SHOW_ERR);
            }

            ############ Params Prepare ############
            $arr = [
                'aid' => $params['aid'],
                'uid' => $params['uid'],
                'amount' => $amount,
                'remark' => $params['remark'] ?? null,
            ];

            $db->insert("ag_promo_table", $arr);
            $refId = $db->lastInsertId();
            ################################################

            ############ insert user transaction log ############
            $promoArr = [
                'uid' => $params['uid'],
                'amount' => $amount,
                'type' => 'credit',
                'trans_type_id' => BANK_TRANS_TYPE['promotion'],
                'ref_id' => $refId,
                'is_credit_point' => 1
            ];
            $flag = $trans_model->transLogInsert($promoArr);
            if (empty($flag) || !isset($flag['status']) || !$flag['status']) {
                throw new Exception($flag['msg'], $flag['code']);
            }
            ################################################

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


    public function promoBlock($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $db->query("update ag_promo_table set status=abs(status-1) where id=? limit 1", array($params['item_id']));
            $db->query("update ag_trans_log set status=abs(status-1) where ref_id=? and trans_type=? limit 1", array($params['item_id'], BANK_TRANS_TYPE['promotion']));

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

}
