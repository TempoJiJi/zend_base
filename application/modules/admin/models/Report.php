<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Report extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';


    public function reportDashboard($params)
    {
        try {
            $common_model = new Model_Common();
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+1 Days"));
            if (isset($params['start_date']) && !empty($params['start_date'])) {
                $startDate = $params['start_date'];
            }
            if (isset($params['end_date']) && !empty($params['end_date'])) {
                $endDate = date('Y-m-d', strtotime($params['end_date'] . "+1 DAYS"));
            }

            $userTotalSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_table'], ["count(a.id) as total_register"])
                ->where('a.status in (0,1)');

            $userFilterSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_user_table'], ["count(a.id) as total_register"])
                ->where('a.status in (0,1)')
                ->where('date(a.registered_at)>=?', $startDate)
                ->where('date(a.registered_at)<?', $endDate);

            $depositSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["count(a.id) as total_count", "coalesce(sum(a.credit-a.debit),0) as total_amount"])
                ->where('a.status=1')
                ->where('a.trans_type=?', BANK_TRANS_TYPE['deposit'])
                ->where('date(a.created_at)>=?', $startDate)
                ->where('date(a.created_at)<?', $endDate);

            $withdrawalSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["count(a.id) as total_count", "coalesce(sum(abs(a.credit-a.debit)),0) as total_amount"])
                ->where('a.status=1')
                ->where('a.trans_type=?', BANK_TRANS_TYPE['withdrawal'])
                ->where('date(a.created_at)>=?', $startDate)
                ->where('date(a.created_at)<?', $endDate);

            $rebateSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["count(a.id) as total_count", "coalesce(sum(a.credit-a.debit),0) as total_amount"])
                ->where('a.status=1')
                ->where('a.trans_type=?', BANK_TRANS_TYPE['rebate'])
                ->where('date(a.created_at)>=?', $startDate)
                ->where('date(a.created_at)<?', $endDate);

            $cashbackSql = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["count(a.id) as total_count", "coalesce(sum(a.credit-a.debit),0) as total_amount"])
                ->where('a.status=1')
                ->where('a.trans_type=?', BANK_TRANS_TYPE['cashback'])
                ->where('date(a.created_at)>=?', $startDate)
                ->where('date(a.created_at)<?', $endDate);

            $userTotal = $this->fetchRow($userTotalSql)->toArray();
            $userFilter = $this->fetchRow($userFilterSql)->toArray();
            $deposit = $this->fetchRow($depositSql)->toArray();
            $withdrawal = $this->fetchRow($withdrawalSql)->toArray();
            $rebate = $this->fetchRow($rebateSql)->toArray();
            $cashback = $this->fetchRow($cashbackSql)->toArray();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array(
                    'total_user' => $userTotal,
                    'total_user_filter' => $userFilter,
                    'deposit' => $deposit,
                    'withdrawal' => $withdrawal,
                    'rebate' => $rebate,
                    'cashback' => $cashback,
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

    public function reportReport($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_report_company'], ["*"])
                ->joinLeft(['b' => 'ag_report_trans_log'], 'b.report_id = a.id and b.status=1 and b.is_reset=0 and b.trans_type=' . BANK_TRANS_TYPE['deposit'], ["coalesce(sum(b.amount), 0) as deposit_total_amount", "coalesce(count(distinct(b.id)), 0) as deposit_total_count"])
                ->joinLeft(['c' => 'ag_report_trans_log'], 'c.report_id = a.id and c.status=1 and c.is_reset=0 and c.trans_type=' . BANK_TRANS_TYPE['withdrawal'], ["coalesce(sum(c.amount), 0) as withdrawal_total_amount", "coalesce(count(distinct(c.id)), 0) as withdrawal_total_count"])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_report_company'], ["count(1) as allcount"])
                ->joinLeft(['b' => 'ag_report_trans_log'], 'b.report_id = a.id and b.status=1 and b.is_reset=0 and b.trans_type=' . BANK_TRANS_TYPE['deposit'], ["coalesce(sum(b.amount), 0) as deposit_total_amount", "coalesce(count(distinct(b.id)), 0) as deposit_total_count"])
                ->joinLeft(['c' => 'ag_report_trans_log'], 'c.report_id = a.id and c.status=1 and c.is_reset=0 and c.trans_type=' . BANK_TRANS_TYPE['withdrawal'], ["coalesce(sum(c.amount), 0) as withdrawal_total_amount", "coalesce(count(distinct(c.id)), 0) as withdrawal_total_count"])
                ->where('a.status in (0,1)');

            $data = $common_model->tablePaginator($params, $sql1, $sql2, ' group by a.id ');
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


    public function reportAdd($params)
    {
        try {

            $db = $this->getAdapter();

            $data = $db->query("select id, name from ag_report_table where status=1")->fetchAll();

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

    public function reportAddSubmit($params)
    {
        try {
            $common_model = new Model_Common();
            $db = $this->getAdapter();

            $itemId = 0;
            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $itemId = $params['item_id'];
            }

            $arr = [
                'report_name' => $params['report_name'],
                'report_acc_name' => $params['report_acc_name'],
                'report_acc_no' => $params['report_acc_no'],
                'min_deposit' => $params['min_deposit'],
                'max_deposit' => $params['max_deposit'],
                'deposit_limit_status' => $params['deposit_limit_status'],
                'deposit_limit_amount' => $params['deposit_limit_amount'],
                'deposit_limit_count' => $params['deposit_limit_count'],
                'withdrawal_limit_status' => $params['withdrawal_limit_status'],
                'withdrawal_limit_amount' => $params['withdrawal_limit_amount'],
                'withdrawal_limit_count' => $params['withdrawal_limit_count'],
            ];

            // image is optional
            if (isset($_FILES) && !empty($_FILES)) {
                $awsRes = $common_model->awsS3UploadPublic('report_img', 'report_qr_code');
                if (!isset($awsRes['status']) || !$awsRes['status']) {
                    throw new Exception($awsRes['msg'], 0);
                }
                $arr['report_img'] = $awsRes['data'];
            }

            if ($itemId == 0) {
                $db->insert("ag_report_company", $arr);
            } else {
                $db->update("ag_report_company", $arr, ['id = ?' => $itemId]);
            }

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


    public function reportEdit($params)
    {
        try {

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_report_company'], ["*"])
                ->where('a.status in (0,1)')
                ->where('a.id=?', $params['item_id']);

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

    public function reportBlock($params)
    {
        try {

            $db = $this->getAdapter();

            $db->query("update ag_report_company set status=abs(status-1) where id=? limit 1", array($params['item_id']));

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

    public function reportThresholdBlock($params)
    {
        try {

            $db = $this->getAdapter();

            if ($params['type'] == 'deposit') {
                $db->query("update ag_report_company set deposit_limit_status=abs(deposit_limit_status-1) where id=? limit 1", array($params['item_id']));
            }
            if ($params['type'] == 'withdrawal') {
                $db->query("update ag_report_company set withdrawal_limit_status=abs(withdrawal_limit_status-1) where id=? limit 1", array($params['item_id']));
            }


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



}
