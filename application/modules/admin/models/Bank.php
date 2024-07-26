<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Bank extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';

    public function banks($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_table'], ["*", "a.name as value", "a.name as text"])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_table'], ["count(1) as allcount"])
                ->where('a.status in (0,1)');

            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {
                $data = $this->fetchAll($sql1)->toArray();
            } else {
                $data = $common_model->tablePaginator($params, $sql1, $sql2, ' group by a.id ');
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

    public function banksAdd($params)
    {
        try {

            $db = $this->getAdapter();

            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $db->query("update ag_bank_table set name=? where id=? limit 1", array($params['bank_name'], $params['item_id']));
            } else {
                $db->insert("ag_bank_table", array('name' => $params['bank_name']));
            }

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

    public function banksBlock($params)
    {
        try {

            $db = $this->getAdapter();

            $db->query("update ag_bank_table set status=abs(status-1) where id=? limit 1", array($params['item_id']));

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


    public function bankList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_company'], ["*",
                    "(case when (a.withdrawal_total_amount>=a.withdrawal_limit_amount) then 0 else 1 end) as is_active_withdraw", 
                    "a.id as value", "coalesce(concat(a.bank_name, ' (', JSON_UNQUOTE(JSON_EXTRACT(a.details, '$.paynow_name')), ') ', a.bank_acc_no), '-') as text"])
                ->joinLeft(['b' => 'ag_trans_log'], 'b.company_bank_id = a.id and b.status=1', ["coalesce(sum(b.credit-b.debit), 0) as bank_balance"]);

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_company'], ["count(1) as allcount"]);

            if (isset($params['show_all']) && $params['show_all'] == 1) {
                $sql1->where('a.status in (0,1)');
                $sql2->where('a.status in (0,1)');
            } else {
                $sql1->where('a.status in (1)');
                $sql2->where('a.status in (1)');
            }

            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {

                // get only status 1 bank
                $sql1->where('a.status=1');

                if (isset($params['bank_id']) && !empty($params['bank_id'])) {
                    $sql1->where('a.id=?', $params['bank_id']);
                }
                if (isset($params['is_active']) && !empty($params['is_active'])) {
                    $sql1->where('a.is_active=?', $params['is_active']);
                }

                $sql1->group('a.id');
                $sql1->order('a.is_active desc');   // make active bank infront

                $data = $this->fetchAll($sql1)->toArray();
            } else {
                $data = $common_model->tablePaginator($params, $sql1, $sql2, ' group by a.id ');
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

    public function bankReport($params)
    {
        try {
            $common_model = new Model_Common();

            $filterStart = $filterStart2 = $filterStart4 = '';

            /**
             * default opening balance date, if without filter then make it amount to zero
             *  so the opening balance will be bank init balance
             */
            $filterStart3 = "and f.transaction_date<'2000-01-01'";

            $filterEnd = $filterEnd2 = $filterEnd3 = $filterEnd4 = '';

            if (isset($params['start_date']) && !empty($params['start_date'])) {
                $startDate = $params['start_date'];
                $filterStart4 = "and t.transaction_date>='$startDate'";  // total transaction count
                $filterStart = "and b.transaction_date>='$startDate'";  // total deposit amount
                $filterStart2 = "and c.transaction_date>='$startDate'"; // total withdrawal amount
                $filterStart3 = "and f.transaction_date<'$startDate'";  // opening balance
            }
            if (isset($params['end_date']) && !empty($params['end_date'])) {
                $endDate = date('Y-m-d', strtotime($params['end_date'] . "+1 Days"));

                $filterEnd4 = "and t.transaction_date<'$endDate'";
                $filterEnd = "and b.transaction_date<'$endDate'";
                $filterEnd2 = "and c.transaction_date<'$endDate'";
                $filterEnd3 = "and e.transaction_date<'$endDate'";
            }

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_company'], [
                    "*",

                    // total transaction count
                    "(select coalesce(count(1), 0) from ag_trans_log as t where t.company_bank_id = a.id and t.status=1 $filterStart4 $filterEnd4 limit 1) as total_transaction_count",

                    // total deposit amount
                    "(select coalesce(sum((b.credit)), 0) from ag_trans_log as b where b.company_bank_id = a.id and b.status=1 $filterStart $filterEnd and b.trans_type in (" . BANK_TRANS_TYPE['deposit'] . ", ". BANK_TRANS_TYPE['interbank_transfer'] .") limit 1) as trans_deposit_total_amount",

                    // total withdrawal amount
                    "(select coalesce(sum(abs(c.debit)), 0) from ag_trans_log as c where c.company_bank_id = a.id and c.status=1 $filterStart2 $filterEnd2 and c.trans_type in (" . BANK_TRANS_TYPE['withdrawal'] . ", " . BANK_TRANS_TYPE['interbank_transfer'] . ") limit 1) as trans_withdrawal_total_amount",

                    // bank init balance
                    "(select coalesce(sum((d.credit-d.debit)), 0) from ag_trans_log as d where d.company_bank_id = a.id and d.status=1 and d.trans_type=" . BANK_TRANS_TYPE['opening_balance'] . " limit 1) as bank_opening_balance",

                    // closing balance
                    "(select coalesce(sum((e.credit-e.debit)), 0) from ag_trans_log as e where e.company_bank_id = a.id and e.status=1 $filterEnd3 limit 1) as closing_balance",

                    // opening balance without bank init balance
                    "(select coalesce(sum((f.credit-f.debit)), 0) from ag_trans_log as f where f.company_bank_id = a.id and f.status=1 $filterStart3 and f.trans_type!=" . BANK_TRANS_TYPE['opening_balance'] . " limit 1) as opening_balance",
                ])
                ->joinLeft(['br' => 'ag_bank_role'], "br.id=a.card_role", "br.display as card_role_display")
                // ->joinLeft(['b' => 'ag_trans_log'], "b.company_bank_id = a.id and b.status=1 $filterStart $filterEnd and b.trans_type=" . BANK_TRANS_TYPE['deposit'], ["coalesce(sum(b.credit-b.debit), 0) as trans_deposit_total_amount"])
                // ->joinLeft(['c' => 'ag_trans_log'], "c.company_bank_id = a.id and c.status=1 $filterStart2 $filterEnd2 and c.trans_type=" . BANK_TRANS_TYPE['withdrawal'], ["coalesce(sum(abs(c.credit-c.debit)), 0) as trans_withdrawal_total_amount"])
                // ->joinLeft(['d' => 'ag_trans_log'], "d.company_bank_id = a.id and d.status=1 $filterEnd3", ["coalesce(sum(abs(d.credit-d.debit)), 0) as closing_balance"])
                ->where('a.status in (1)');

            // pagination no need join
            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_company'], ["count(1) as allcount"])
                ->where('a.status in (1)');

            
            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {
                $data = $this->fetchAll($sql1)->toArray();
            } else {
                $data = $common_model->tablePaginator($params, $sql1, $sql2, ' group by a.id ');
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

    public function bankReportAll($params)
    {
        try {

            $db = $this->getAdapter();

            $report = $this->bankReport(array('not_pagin' => 1));
            $report = $report['data'];
            $ret = [
                'total_count' => 0,
                'total_transaction_count' => 0,
                'trans_deposit_total_amount' => 0,
                'trans_withdrawal_total_amount' => 0,
                'opening_balance' => 0,
                'closing_balance' => 0,
            ];

            foreach($report as $r) {
                $ret['total_count']++;
                $ret['total_transaction_count'] += $r['total_transaction_count'];
                $ret['trans_deposit_total_amount'] += $r['trans_deposit_total_amount'];
                $ret['trans_withdrawal_total_amount'] += $r['trans_withdrawal_total_amount'];
                $ret['opening_balance'] += ($r['bank_opening_balance'] + $r['opening_balance']);
                $ret['closing_balance'] += $r['closing_balance'];
            }

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $ret
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

    public function bankAdd($params)
    {
        try {

            $db = $this->getAdapter();

            $bankData = $this->banks(array('not_pagin' => 1));
            $cardRole = $db->query("select * from ag_bank_role where status=1")->fetchAll();

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array(
                    'bank_data' => $bankData['data'] ?? array(),
                    'card_role' => $cardRole
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

    public function bankAddSubmit($params)
    {
        try {
            $common_model = new Model_Common();
            $db = $this->getAdapter();

            $itemId = 0;
            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $itemId = $params['item_id'];
            }

            $details = [];
            $bankDetails = $db->query("select name from ag_bank_details where status=1")->fetchAll();
            foreach($bankDetails as $d) {
                $detailName = $d['name'];
                if (isset($params[$detailName])) {
                    $details[$detailName] = $params[$detailName];
                }
            }

            $arr = [
                'bank_name' => $params['bank_name'],
                'bank_acc_name' => $params['bank_acc_name'],
                'bank_acc_no' => $params['bank_acc_no'],
                'min_deposit' => $params['min_deposit'],
                'max_deposit' => $params['max_deposit'],
                'transaction_limit_count' => $params['transaction_limit_count'],
                'transaction_limit_amount' => $params['transaction_limit_amount'],
                'withdrawal_limit_amount' => $params['withdrawal_limit_amount'],
                'card_role' => $params['card_role'],
                'details' => json_encode($details)
            ];

            if ($itemId == 0) {
                $db->insert("ag_bank_company", $arr);
                $bankId = $db->lastInsertId();

                // insert opening balance
                if (isset($params['opening_balance']) && is_numeric($params['opening_balance']) && $params['opening_balance'] >= 0) {
                    $db->insert('ag_trans_log', array(
                        'aid' => $params['aid'],
                        'company_bank_id' => $bankId,
                        'uid' => 0,
                        'credit' => $params['opening_balance'],
                        'trans_type' => BANK_TRANS_TYPE['opening_balance']
                    ));
                }

            } else {
                $db->update("ag_bank_company", $arr, ['id = ?' => $itemId]);
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


    public function bankEdit($params)
    {
        try {

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_bank_company'], ["*"])
                ->joinLeft(['b' => 'ag_bank_role'], "b.id=a.card_role", "b.display as card_role_display")
                ->where('a.status in (0,1)')
                ->where('a.id=?', $params['item_id']);

            $data = $this->fetchRow($sql1)->toArray();
            $data['details'] = json_decode($data['details']);

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

    public function bankBlock($params)
    {
        try {

            $db = $this->getAdapter();

            $db->query("update ag_bank_company set status=abs(status-1) where id=? limit 1", array($params['item_id']));

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

    public function bankThresholdBlock($params)
    {
        try {

            $db = $this->getAdapter();

            if ($params['type'] == 'deposit') {
                $db->query("update ag_bank_company set deposit_limit_status=abs(deposit_limit_status-1) where id=? limit 1", array($params['item_id']));
            }
            if ($params['type'] == 'withdrawal') {
                $db->query("update ag_bank_company set withdrawal_limit_status=abs(withdrawal_limit_status-1) where id=? limit 1", array($params['item_id']));
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

    public function bankThresholdReset($params)
    {
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            $now = date('Y-m-d H:i:s');

            $db->update('ag_bank_company', array(
                'is_active' => 1,
                'transaction_total_count' => 0,
                'transaction_total_amount' => 0,
                'withdrawal_total_amount' => 0,
            ), array(
                'id=?' => $params['item_id'],
            ));

            // reset log
            $db->insert('ag_bank_reset_log', array(
                'aid' => $params['aid'],
                'company_bank_id' => $params['item_id'],
                'reset_at' => $now
            ));

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

    /**
     * @param bank_id
     * @param amount
     * @param trans_type
     * NOTE: Without beginTransaction
     */
    public function bankThresholdUpdate($params)
    {
        $db = $this->getAdapter();
        $common_model = new Model_Common();

        try {

            if (!isset($params['bank_id']) && empty($params['bank_id'])) {
                throw new Exception("bankThresholdUpdate bank_id not found", 0);
            }
            if (!isset($params['amount']) && empty($params['amount'])) {
                throw new Exception("bankThresholdUpdate amount not found", 0);
            }

            $amount = $common_model->setDecimal($params['amount'], 2);
            if ($amount < 0) {
                throw new Exception("bankThresholdUpdate amount invalid", 0);
            }

            // get bank data
            $bankData = $db->query("select * from ag_bank_company where id=? and status=1 limit 1", array($params['bank_id']))->fetch();
            if (empty($bankData) || !isset($bankData['is_active'])) {
                throw new Exception("Bank detail not found", ADMIN_SHOW_ERR);
            }

            $updateBankArr = array(
                'transaction_total_count' => $bankData['transaction_total_count'] + 1,
                'transaction_total_amount' => $bankData['transaction_total_amount'] + $amount
            );
            if ($params['trans_type'] == BANK_TRANS_TYPE['withdrawal']) {
                $updateBankArr['withdrawal_total_amount'] = $bankData['withdrawal_total_amount'] + $amount;
            }

            $db->update('ag_bank_company', $updateBankArr, ['id=?' => $params['bank_id']]);

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

    /**
     * @param trans_id
     * without beginTransaction
     */
    public function bankThresholdDelete($params)
    {

        $db = $this->getAdapter();
        $common_model = new Model_Common();

        try {

            if (!isset($params['trans_id']) && empty($params['trans_id'])) {
                throw new Exception("bankThresholdDelete trans_id not found", 0);
            }

            // get transaction data
            $transData = $db->query("select (case when credit!=0 then credit else debit end) as amount, trans_type, company_bank_id from ag_trans_log where id=? and status=1 limit 1", array($params['trans_id']))->fetch();
            if (empty($transData) || !isset($transData['amount']) || !isset($transData['trans_type']) || !isset($transData['company_bank_id'])) {
                throw new Exception("Transaction data not found", ADMIN_SHOW_ERR);
            }

            // only allow deposit and withdrawal
            if (!in_array($transData['trans_type'], array(BANK_TRANS_TYPE['deposit'], BANK_TRANS_TYPE['withdrawal'], BANK_TRANS_TYPE['interbank_transfer']))) {
                throw new Exception("Transaction type invalid", ADMIN_SHOW_ERR);
            }

            // get bank data
            $bankData = $db->query("select * from ag_bank_company where id=? limit 1", array($transData['company_bank_id']))->fetch();
            if (empty($bankData)) {
                throw new Exception("Transaction Company Bank data not found", ADMIN_SHOW_ERR);
            }

            ################ calc new transaction amount and count ################
            $newTransactionAmount = max($bankData['transaction_total_amount'] - $transData['amount'], 0);
            $newTransactionCount = max($bankData['transaction_total_count'] - 1, 0);
            $newWithdrawAmount = $bankData['withdrawal_total_amount'];
            if ($transData['trans_type'] == BANK_TRANS_TYPE['withdrawal']) {
                $newWithdrawAmount = max($bankData['withdrawal_total_amount'] - $transData['amount'], 0);
            }
            ################################################################


            ################ check new is_active ################
            $isActive = $bankData['is_active'];
            if ($newTransactionAmount < $bankData['transaction_total_amount'] && $newTransactionCount < $bankData['transaction_total_count']) {
                $isActive = 1;
            }
            ################################################################


            // all good, update

            // update new amount and limit
            $db->update("ag_bank_company", array(
                'transaction_total_amount' => $newTransactionAmount,
                'transaction_total_count' => $newTransactionCount,
                'withdrawal_total_amount' => $newWithdrawAmount,
                'is_active' => $isActive
            ), array("id=?" => $transData['company_bank_id']));

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

    /**
     * @param: trans_type
     * @param: bank_id
     * @param: amount
     */
    public function bankActiveValidate($params, $is_update = false)
    {
        $db = $this->getAdapter();
        $common_model = new Model_Common();

        try {

            if (!isset($params['trans_type'])) {
                throw new Exception("bankActiveValidate trans_type not found", 0);
            }

            $amount = $common_model->setDecimal($params['amount'], 2);

            // get bank data
            $bankData = $db->query("select * from ag_bank_company where id=? and status=1 limit 1", array($params['bank_id']))->fetch();
            if (empty($bankData) || !isset($bankData['is_active'])) {
                throw new Exception("Bank detail not found", ADMIN_SHOW_ERR);
            }


            ########## threshold checking ##########

            // check active
            if ($bankData['is_active'] != 1) {
                if ($is_update) {
                } else {
                    throw new Exception("Bank inactive - $params[bank_id]", ADMIN_SHOW_ERR);
                }
            }

            // transaction total count, checking greater than and equal to
            if ($bankData['transaction_total_count'] >= $bankData['transaction_limit_count']) {
                if ($is_update) {
                    $db->query("update ag_bank_company set is_active=0 where id=? limit 1", array($params['bank_id']));
                } else {
                    throw new Exception("Transaction Limit Count Exceeded - $params[bank_id]", ADMIN_SHOW_ERR);
                }
            }

            // transaction total amount, checking greater than
            $transactionTotalAmount = $bankData['transaction_total_amount'] + $amount;
            if ($transactionTotalAmount > $bankData['transaction_limit_amount']) {
                if ($is_update) {
                    $db->query("update ag_bank_company set is_active=0 where id=? limit 1", array($params['bank_id']));
                } else {
                    $maxTransactionAmount = $bankData['transaction_limit_amount'] - $bankData['transaction_total_amount'];
                    throw new Exception("Transaction Limit Amount Exceeded, maximum transaction amount: $maxTransactionAmount - $params[bank_id]", ADMIN_SHOW_ERR);
                }
            }

            // withdrawal total amount, checking greater than
            if ($params['trans_type'] == BANK_TRANS_TYPE['withdrawal']) {
                $withdrawalTotalAmount = $bankData['withdrawal_total_amount'] + $amount;
                if ($withdrawalTotalAmount > $bankData['withdrawal_limit_amount']) {
                    if ($is_update) {
                        // do nothing, still can deposit
                    } else {
                        $maxWithdrawAmount = $bankData['withdrawal_limit_amount'] - $bankData['withdrawal_total_amount'];
                        throw new Exception("Withdrawal Limit Amount Exceeded, maximum withdraw amount: $maxWithdrawAmount - $params[bank_id]", ADMIN_SHOW_ERR);
                    }
                }
            }
            ############################################################

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $bankData
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
