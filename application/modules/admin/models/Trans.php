<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/AwsS3.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Trans extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';

    public function transList($params)
    {
        try {
            $common_model = new Model_Common();

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["a.id", "a.uid", "a.credit", "a.debit", "a.trans_type", "a.is_credit_point", "a.transaction_date", "a.status", "coalesce(a.remark, '') as remark", "a.ref_id"])
                ->joinLeft(['b' => 'ag_trans_type'], 'b.id = a.trans_type', ["b.display as trans_type_name"])
                ->joinLeft(['c' => 'ag_user_table'], 'c.id = a.uid', ["coalesce(c.username, '-') as username", "coalesce(c.full_name, '-') as full_name"])
                ->joinLeft(['d' => 'ag_user_bank'], 'd.id = a.user_bank_id', ["coalesce(concat(d.bank_name, ' ', d.bank_acc), '-') as bank_acc"])
                ->joinLeft(['e' => 'ag_bank_company'], 'e.id = a.company_bank_id', ["coalesce(concat(e.bank_name, ' (', JSON_UNQUOTE(JSON_EXTRACT(e.details, '$.paynow_name')), ') ', e.bank_acc_no), '-') as company_bank_acc"])
                ->joinLeft(['f' => 'ag_admin_table'], 'f.id = a.aid', ["f.username as admin_username"])
                ->where('a.status in (0,1)');

            $sql2 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["count(1) as allcount"])
                ->joinLeft(['b' => 'ag_trans_type'], 'b.id = a.trans_type', [])
                ->joinLeft(['c' => 'ag_user_table'], 'c.id = a.uid', [])
                ->joinLeft(['d' => 'ag_user_bank'], 'd.id = a.user_bank_id', [])
                ->joinLeft(['e' => 'ag_bank_company'], 'e.id = a.company_bank_id', [])
                ->joinLeft(['f' => 'ag_admin_table'], 'f.id = a.aid', [])
                ->where('a.status in (0,1)');

            if (isset($params['start_date']) && !empty($params['start_date'])) {
                $startDate = $params['start_date'];
                $sql1->where('date(a.transaction_date)>=?', $startDate);
                $sql2->where('date(a.transaction_date)>=?', $startDate);
            }
            if (isset($params['end_date']) && !empty($params['end_date'])) {
                $endDate = date('Y-m-d', strtotime($params['end_date'] . "+1 Days"));
                $sql1->where('date(a.transaction_date)<?', $endDate);
                $sql2->where('date(a.transaction_date)<?', $endDate);
            }

            if (isset($params['bank_id']) && !empty($params['bank_id'])) {
                $sql1->where("a.company_bank_id=?", $params['bank_id']);
                $sql2->where("a.company_bank_id=?", $params['bank_id']);
            }

            if (isset($params['trans_only']) && !empty($params['trans_only'])) {
                $sql1->where("b.name in ('deposit', 'withdrawal', 'interbank_transfer')");
                $sql2->where("b.name in ('deposit', 'withdrawal', 'interbank_transfer')");
            }

            if (isset($params['not_pagin']) && $params['not_pagin'] == 1) {

                if (isset($params['limit']) && !empty($params['limit'])) {
                    $sql1->order('a.id desc');
                    $sql1->limit($params['limit']);
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

    public function transType($params)
    {
        try {

            $db = $this->getAdapter();

            $data = $db->query("select id, name, display from ag_trans_type where status=1")->fetchAll();

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

    public function transReport($params)
    {
        try {

            $db = $this->getAdapter();

            $data = $db->query("select count(1) as total_count, coalesce(sum(a.credit), 0) as total_credit, coalesce(sum(a.debit), 0) as total_debit from ag_trans_log as a where a.status=1 limit 1")->fetch();

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


    public function transAdd($params)
    {
        try {

            $bank_model = new Model_Bank();
            $db = $this->getAdapter();

            $bankData = $bank_model->bankList(array('not_pagin' => 1));
            $transData = $this->transList(array(
                'not_pagin' => 1,
                'limit' => 10,
                'trans_only' => 1
            ));

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => array(
                    'bank_data' => $bankData['data'] ?? array(),
                    'trans_data' => $transData['data'] ?? array()
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

    public function transEdit($params)
    {
        try {

            $sql1 = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_trans_log'], ["*", "(case when a.trans_type=" . BANK_TRANS_TYPE['deposit'] . " then a.credit else a.debit end) as amount"])
                ->joinLeft(['c' => 'ag_user_table'], 'c.id = a.uid', ["c.username", "c.full_name"])
                ->joinLeft(['d' => 'ag_user_bank'], 'd.id = a.user_bank_id', ["concat(d.bank_name, ' (', d.bank_acc, ')') as member_bank_text"])
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


    /**
     * Add steps
     * 1. lock
     * 2. check amount and transaction type
     * 3. user group validation, check min max deposit and daily withdrawal count
     * 4. threshold validation and bank min max deposit amount (deposit allow to skip threshold validation)
     * 5. update bank threshold with new amount and count
     * 6. update bank active status with new record
     * 7. insert into trans_log, will check bank balance (not user)
     */
    public function transAddSubmit($params)
    {
        $common_model = new Model_Common();
        $bank_model = new Model_Bank();
        $db = $this->getAdapter();
        $db->beginTransaction();

        try {

            // lock
            $bankData = $db->query("select * from ag_bank_company where id=? and status=1 limit 1 for update", array($params['bank_id']))->fetch();
            if (empty($bankData)) {
                throw new Exception("Bank Detail not found", ADMIN_SHOW_ERR);
            }

            // set decimal for amount
            $amount = $common_model->setDecimal($params['amount'], 2);
            if ($amount <= 0) {
                throw new Exception("Amount Invalid", ADMIN_SHOW_ERR);
            }

            // get transaction type
            if ($params['trans_type'] == BANK_TRANS_TYPE['deposit']){
                $transType = 'deposit';
            } else if ($params['trans_type'] == BANK_TRANS_TYPE['withdrawal']){
                $transType = 'withdrawal';
            } else if ($params['trans_type'] == BANK_TRANS_TYPE['interbank_transfer']){

                if (!isset($params['to_company_bank_id']) || empty($params['to_company_bank_id']) || $params['to_company_bank_id'] == 'null') {
                    throw new Exception("Please select one company bank to withdraw", ADMIN_SHOW_ERR);
                }

                if ($params['bank_id'] == $params['to_company_bank_id']) {
                    throw new Exception("Unable to do interbank transfer between two same bank", ADMIN_SHOW_ERR);
                }

                $transType = 'interbank_transfer';
            } else {
                throw new Exception("Transaction Type Invalid", ADMIN_SHOW_ERR);
            }


            ############ User Group validation ############

            // for user only, skip interbank transfer
            if ($transType != 'interbank_transfer') {

                $userGroupSql = $this->select()->setIntegrityCheck(false)
                    ->from(['a' => 'ag_user_group'], ["a.group_id"])
                    ->joinLeft(['b' => 'ag_user_group_table'], 'b.id=a.group_id', ['b.min_deposit', 'b.max_deposit', 'b.daily_max_withdrawal_count'])
                    ->where('a.status=1')
                    ->limit(1)->order('a.id desc');

                $userGroup = $this->fetchRow($userGroupSql)->toArray();
                if (empty($userGroup)) {
                    throw new Exception("User group not found", ADMIN_SHOW_ERR);
                }
                $groupMinDeposit = $userGroup['min_deposit'] ?? 0;
                $groupMaxDeposit = $userGroup['max_deposit'] ?? 0;
                $groupDailyWithdrawalCount = $userGroup['daily_max_withdrawal_count'] ?? 0;

                // min max deposit amount
                if ($transType == 'deposit') {
                    if ($amount < $groupMinDeposit) {
                        throw new Exception("User group minimum deposit $groupMinDeposit", ADMIN_SHOW_ERR);
                    }
                    if ($amount > $groupMaxDeposit) {
                        throw new Exception("User group maximum deposit $groupMaxDeposit", ADMIN_SHOW_ERR);
                    }
                }

                // daily withdrawal count
                if ($transType == 'withdrawal') {
                    $withdrawalCount = $db->query("select count(1) as total from ag_trans_log where uid=? and trans_type=? and date(transaction_date)=? limit 1", array($params['uid'], BANK_TRANS_TYPE['withdrawal'], date('Y-m-d')))->fetch();
                    $withdrawalCount = $withdrawalCount['total'] ?? 0;

                    if ($withdrawalCount >= $groupDailyWithdrawalCount) {
                        throw new Exception("Exceeded user group maximum daily withdrawal count", ADMIN_SHOW_ERR);
                    }
                }

            }

            ########################################################################



            ############ threshold validation ############

            /**
             * withdrawal and interbank transfer need to check source bank active status
             */
            if ($transType != 'deposit') {
                $bankValidate = $bank_model->bankActiveValidate($params);
                if (empty($bankValidate) || !isset($bankValidate['status'])) {
                    throw new Exception("Bank Validation failed", ADMIN_SHOW_ERR);
                }    
                if (!$bankValidate['status']) {
                    throw new Exception($bankValidate['msg'], $bankValidate['code']);
                }
            }

            // bank min and max deposit
            if ($transType == 'deposit') {
                $minDeposit = $bankData["min_" . $transType];
                $maxDeposit = $bankData["max_" . $transType];
                if ($amount < $minDeposit) {
                    throw new Exception("Minimum deposit amount $minDeposit", ADMIN_SHOW_ERR);
                }
                if ($amount > $maxDeposit) {
                    throw new Exception("Maximum deposit amount $maxDeposit", ADMIN_SHOW_ERR);
                }
            }
            ########################################################################



            ############ Update Bank Threshold ############
            // update bank threshold
            $bankThresholdUpdate = $bank_model->bankThresholdUpdate($params);
            if (empty($bankThresholdUpdate) || !isset($bankThresholdUpdate['status']) || !$bankThresholdUpdate['status']) {
                throw new Exception($bankThresholdUpdate['msg'], $bankThresholdUpdate['code']);
            }

            // update bank is_active by validate function
            $bankValidateUpdate = $bank_model->bankActiveValidate($params, true);
            if (empty($bankValidateUpdate) || !isset($bankValidateUpdate['status']) || !$bankValidateUpdate['status']) {
                throw new Exception($bankValidateUpdate['msg'], $bankValidateUpdate['code']);
            }

            // interbank_transfer, update to_company_bank_id as well
            if ($transType == 'interbank_transfer') {
                $tmp = $params;
                $tmp['bank_id'] = $params['to_company_bank_id'];

                $bankThresholdUpdate = $bank_model->bankThresholdUpdate($tmp);
                if (empty($bankThresholdUpdate) || !isset($bankThresholdUpdate['status']) || !$bankThresholdUpdate['status']) {
                    throw new Exception($bankThresholdUpdate['msg'], $bankThresholdUpdate['code']);
                }

                // update bank is_active by validate function
                $bankValidateUpdate = $bank_model->bankActiveValidate($tmp, true);
                if (empty($bankValidateUpdate) || !isset($bankValidateUpdate['status']) || !$bankValidateUpdate['status']) {
                    throw new Exception($bankValidateUpdate['msg'], $bankValidateUpdate['code']);
                }    
            }
            ############################################################




            ######################## Insert transaction log ########################

            ####### Deposit #######
            if ($transType == 'deposit') {

                if (!isset($params['uid']) || empty($params['uid']) || $params['uid'] == 'null') {
                    throw new Exception("Please select one member bank", ADMIN_SHOW_ERR);
                }

                $flag = $this->transLogInsert(array(
                    'aid' => $params['aid'],
                    'uid' => $params['uid'] ?? 0,
                    'type' => 'credit', 
                    'amount' => $amount,
                    'ref_id' => 0,
                    'trans_type_id' => BANK_TRANS_TYPE[$transType],
                    'company_bank_id' => $params['bank_id'],
                    'remark' => $params['remark'] ?? null    
                ));
                if (empty($flag) || !isset($flag['status']) || !$flag['status']) {
                    throw new Exception($flag['msg'], $flag['code']);
                }
    
            }

            ####### Withdrawal #######
            if ($transType == 'withdrawal') {

                if (!isset($params['uid']) || empty($params['uid']) || $params['uid'] == 'null') {
                    throw new Exception("Please select one member bank", ADMIN_SHOW_ERR);
                }
                if (!isset($params['user_bank_id']) || empty($params['user_bank_id']) || $params['user_bank_id'] == 'null') {
                    throw new Exception("Please select one member bank to withdraw", ADMIN_SHOW_ERR);
                }

                // check member bank status
                $userBank = $db->query("select id from ag_user_bank where uid=? and id=? and status=1 limit 1", array($params['uid'], $params['user_bank_id']))->fetch();
                if (empty($userBank) || !isset($userBank['id'])) {
                    throw new Exception("User withdrawal bank invalid", ADMIN_SHOW_ERR);
                }

                $flag = $this->transLogInsert(array(
                    'aid' => $params['aid'],
                    'uid' => $params['uid'],
                    'type' => 'debit', 
                    'amount' => $amount,
                    'ref_id' => 0,
                    'trans_type_id' => BANK_TRANS_TYPE[$transType],
                    'company_bank_id' => $params['bank_id'],
                    'user_bank_id' => $params['user_bank_id'],
                    'remark' => $params['remark'] ?? null    
                ));
                if (empty($flag) || !isset($flag['status']) || !$flag['status']) {
                    throw new Exception($flag['msg'], $flag['code']);
                }    
            }

            ####### Interbank Transfer #######
            if ($transType == 'interbank_transfer') {

                /**
                 * Aware of to_company_bank_id and company_bank_id
                 */

                // debit source bank first
                $debitRes = $this->transLogInsert(array(
                    'aid' => $params['aid'],
                    'uid' => 0,
                    'type' => 'debit', 
                    'amount' => $amount,
                    'ref_id' => 0,
                    'trans_type_id' => BANK_TRANS_TYPE[$transType],
                    'company_bank_id' => $params['bank_id'],
                    'from_company_bank_id' => $params['bank_id'],
                    'to_company_bank_id' => $params['to_company_bank_id'],
                    'remark' => $params['remark'] ?? null    
                ));
                if (empty($debitRes) || !isset($debitRes['status']) || !$debitRes['status']) {
                    throw new Exception($debitRes['msg'], $debitRes['code']);
                }
                $debitTransId = $debitRes['data'];

                // credit to destinated bank, ref_id to previous debit transaction id
                $creditRes = $this->transLogInsert(array(
                    'aid' => $params['aid'],
                    'uid' => 0,
                    'type' => 'credit', 
                    'amount' => $amount,
                    'ref_id' => $debitTransId,
                    'trans_type_id' => BANK_TRANS_TYPE[$transType],
                    'company_bank_id' => $params['to_company_bank_id'],
                    'from_company_bank_id' => $params['bank_id'],
                    'to_company_bank_id' => $params['to_company_bank_id'],
                    'remark' => $params['remark'] ?? null    
                ));
                if (empty($creditRes) || !isset($creditRes['status']) || !$creditRes['status']) {
                    throw new Exception($creditRes['msg'], $creditRes['code']);
                }

                // update debit transaction ref_id
                $db->update('ag_trans_log', ['ref_id' => $creditRes['data']], ['id=?' => $debitTransId]);
            }

            ################################################################################################

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

    /**
     * Edit steps
     * 1. lock
     * 2. if edit same day record, then go to step 3, else go to 7 
     * 3. get transaction amount and type, then update company bank threshold (ex: minus the total amount and count)
     * 4. after update company bank, then check bank validation, see after update still active or not
     * 5. if all good then update new amount and count to threshold
     * 6. update active status with new amount and count
     * 7. update transaction log
     */
    public function transEditSubmit($params)
    {

        $db = $this->getAdapter();
        $common_model = new Model_Common();
        $bank_model = new Model_Bank();
        $db->beginTransaction();
        $today = date('Y-m-d');

        try {

            // lock
            $forLockOnly = $db->query("select bank_name, bank_acc_no from ag_bank_company where id=? and status=1 limit 1 for update", array($params['bank_id']))->fetch();
            if (empty($forLockOnly)) {
                throw new Exception("Bank Detail not found", ADMIN_SHOW_ERR);
            }
            
            // get transaction type
            if ($params['trans_type'] == BANK_TRANS_TYPE['deposit']){
                $transType = 'deposit';
            } else if ($params['trans_type'] == BANK_TRANS_TYPE['withdrawal']){
                $transType = 'withdrawal';
            } else if ($params['trans_type'] == BANK_TRANS_TYPE['interbank_transfer']){

                if (!isset($params['to_company_bank_id']) || empty($params['to_company_bank_id']) || $params['to_company_bank_id'] == 'null') {
                    throw new Exception("Please select one company bank to withdraw", ADMIN_SHOW_ERR);
                }

                if ($params['bank_id'] == $params['to_company_bank_id']) {
                    throw new Exception("Unable to do interbank transfer between two same bank", ADMIN_SHOW_ERR);
                }

                $transType = 'interbank_transfer';

            } else {
                throw new Exception("Transaction Type Invalid", ADMIN_SHOW_ERR);
            }
            
            // set decimal for amount
            $amount = $common_model->setDecimal($params['amount'], 2);
            if ($amount <= 0) {
                throw new Exception("Amount Invalid", ADMIN_SHOW_ERR);
            }

            // get transaction date
            $transData = $db->query("select date(transaction_date) as transaction_date from ag_trans_log where id=? limit 1", array($params['item_id']))->fetch();
            if (empty($transData) || !isset($transData['transaction_date'])) {
                throw new Exception("Edited Transaction Date not found", ADMIN_SHOW_ERR);
            }
            $transactionDate = $transData['transaction_date'];
            
            ############ Threshold Part ############
            // only update threshold for same day transaction
            if ($transactionDate == $today) {
                // delete and update transaction amount and count
                $bankThresholdDelete = $bank_model->bankThresholdDelete(array('trans_id' => $params['item_id']));
                if (empty($bankThresholdDelete) || !isset($bankThresholdDelete['status']) || !$bankThresholdDelete['status']) {
                    throw new Exception($bankThresholdDelete['msg'], $bankThresholdDelete['code']);
                }

                // validate new transaction amount and count, deposit can skip
                if ($transType != 'deposit') {
                    $bankValidate = $bank_model->bankActiveValidate($params);
                    if (empty($bankValidate) || !isset($bankValidate['status']) || !$bankValidate['status']) {
                        throw new Exception($bankValidate['msg'], $bankValidate['code']);
                    }
                }

                // update bank threshold
                $bankThresholdUpdate = $bank_model->bankThresholdUpdate($params);
                if (empty($bankThresholdUpdate) || !isset($bankThresholdUpdate['status']) || !$bankThresholdUpdate['status']) {
                    throw new Exception($bankThresholdUpdate['msg'], $bankThresholdUpdate['code']);
                }

                // update bank is_active by validate function
                $bankValidateUpdate = $bank_model->bankActiveValidate($params, true);
                if (empty($bankValidateUpdate) || !isset($bankValidateUpdate['status']) || !$bankValidateUpdate['status']) {
                    throw new Exception($bankValidateUpdate['msg'], $bankValidateUpdate['code']);
                }

                // interbank_transfer, update to_company_bank_id as well
                if ($transType == 'interbank_transfer') {
                    $tmp = $params;
                    $tmp['bank_id'] = $params['to_company_bank_id'];

                    $bankThresholdUpdate = $bank_model->bankThresholdUpdate($tmp);
                    if (empty($bankThresholdUpdate) || !isset($bankThresholdUpdate['status']) || !$bankThresholdUpdate['status']) {
                        throw new Exception($bankThresholdUpdate['msg'], $bankThresholdUpdate['code']);
                    }

                    // update bank is_active by validate function
                    $bankValidateUpdate = $bank_model->bankActiveValidate($tmp, true);
                    if (empty($bankValidateUpdate) || !isset($bankValidateUpdate['status']) || !$bankValidateUpdate['status']) {
                        throw new Exception($bankValidateUpdate['msg'], $bankValidateUpdate['code']);
                    }    
                }
            }
            ############################################################

            // all good, update transaction log

            if ($transType == 'interbank_transfer') {

                // edit debit transaction first
                $transArr = [
                    'company_bank_id' => $params['bank_id'],
                    'uid' => 0,
                    'debit' => $amount,
                    'trans_type' => $params['trans_type'],
                    'remark' => $params['remark'] ?? null,
                    'from_company_bank_id' => $params['bank_id'],
                    'to_company_bank_id' => $params['to_company_bank_id'],
                    'status' => 1   // must, since maybe kena edited
                ];
                $db->update('ag_trans_log', $transArr, ['id=?' => $params['item_id']]);

                // edit credit transaction
                $transArr = [
                    'company_bank_id' => $params['to_company_bank_id'],
                    'uid' => 0,
                    'credit' => $amount,
                    'trans_type' => $params['trans_type'],
                    'remark' => $params['remark'] ?? null,
                    'from_company_bank_id' => $params['bank_id'],
                    'to_company_bank_id' => $params['to_company_bank_id'],
                    'status' => 1   // must, since maybe kena edited
                ];
                $db->update('ag_trans_log', $transArr, ['ref_id=?' => $params['item_id'], 'trans_type' => $params['trans_type']]);

            } else {

                $transArr = [
                    'company_bank_id' => $params['bank_id'],
                    'uid' => $params['uid'],
                    'trans_type' => $params['trans_type'],
                    'remark' => $params['remark'] ?? null,
                    'user_bank_id' => $params['user_bank_id'],
                    'from_company_bank_id' => 0,    // must, since maybe kena edited
                    'to_company_bank_id' => 0   // must, since maybe kena edited
                ];
                if ($transType == 'deposit') {
                    $transArr['credit'] = $amount;
                    $transArr['debit'] = 0;
                    $transArr['user_bank_id'] = 0;
                }
                if ($transType == 'withdrawal') {
                    $transArr['debit'] = $amount;
                    $transArr['credit'] = 0;
                }
    
                $db->update('ag_trans_log', $transArr, ['id=?' => $params['item_id']]);   
                 
                // transaction type changed, update status for the credit transaction
                $db->update('ag_trans_log', ['status' => 0], ['ref_id=?' => $params['item_id'], 'trans_type' => $params['trans_type']]);
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

    /**
     * Revoke steps
     * 1. get detail
     * 2. check transaction type, only allow deposit and withdrawal
     * 3. if edit same day record, then go to step 4, else go to step 5
     * 4. lock bank, then delete company bank threshold (ex: minus the total amount and count)
     * 5. update transaction log
     */
    public function transRevoke($params)
    {

        $db = $this->getAdapter();
        $common_model = new Model_Common();
        $bank_model = new Model_Bank();
        $db->beginTransaction();
        $today = date('Y-m-d');

        try {

            // get transaction date
            $transData = $db->query("select date(transaction_date) as transaction_date, trans_type, company_bank_id, ref_id from ag_trans_log where id=? limit 1", array($params['item_id']))->fetch();
            if (empty($transData) || !isset($transData['transaction_date'])) {
                throw new Exception("Edited Transaction Date not found", ADMIN_SHOW_ERR);
            }

            if (!in_array($transData['trans_type'], array(BANK_TRANS_TYPE['deposit'], BANK_TRANS_TYPE['withdrawal'], BANK_TRANS_TYPE['interbank_transfer']))) {
                throw new Exception("Only allow to revoke transaction Deposit, Withdrawal and Interbank Transfer", ADMIN_SHOW_ERR);
            }
            
            ############ Threshold Part ############
            $transactionDate = $transData['transaction_date'];

            // only update threshold for same day transaction
            if ($transactionDate == $today) {

                // lock
                $forLockOnly = $db->query("select bank_name, bank_acc_no from ag_bank_company where id=? and status=1 limit 1 for update", array($transData['company_bank_id']))->fetch();
                if (empty($forLockOnly)) {
                    throw new Exception("Bank Detail not found", ADMIN_SHOW_ERR);
                }

                // delete and update transaction amount and count
                $bankThresholdDelete = $bank_model->bankThresholdDelete(array('trans_id' => $params['item_id']));
                if (empty($bankThresholdDelete) || !isset($bankThresholdDelete['status']) || !$bankThresholdDelete['status']) {
                    throw new Exception($bankThresholdDelete['msg'], $bankThresholdDelete['code']);
                }

                if ($transData['trans_type'] == BANK_TRANS_TYPE['interbank_transfer']) {
                    $bankThresholdDelete = $bank_model->bankThresholdDelete(array('trans_id' => $transData['ref_id']));
                    if (empty($bankThresholdDelete) || !isset($bankThresholdDelete['status']) || !$bankThresholdDelete['status']) {
                        throw new Exception($bankThresholdDelete['msg'], $bankThresholdDelete['code']);
                    }    
                }
            }
            ############################################################

            // all good, update transaction log

            $db->update('ag_trans_log', array(
                'revoke_by' => $params['aid'],
                'status' => 0
            ), ['id=?' => $params['item_id']]);

            if ($transData['trans_type'] == BANK_TRANS_TYPE['interbank_transfer']) {
                $db->update('ag_trans_log', array(
                    'revoke_by' => $params['aid'],
                    'status' => 0
                ), ['id=?' => $transData['ref_id']]);    
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



    /**
     * without beginTransaction
     * @param uid
     * @param amount
     * @param type // credit or debit
     * @param trans_type_id // transaction type id
     * @param ref_id
     * @param is_credit_point // optional, default 0
     * @param company_bank_id // optional, default 0
     */
    public function transLogInsert($params, $_lock = false)
    {
        $common_model = new Model_Common();
        $db = $this->getAdapter();

        try {

            ####### compulsory params #######
            $compulsoryKey = array('uid', 'amount', 'type', 'trans_type_id', 'ref_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key])) {
                    throw new Exception("transLogInsert Missing $key", 0);
                }
            }

            $amount = $common_model->setDecimal($params['amount'], 2);
            if ($amount <= 0) {
                throw new Exception("Transaction log insert amount invalid $amount", 2);
            }

            ####### check transaction type #######
            if (!in_array($params['type'], ['credit', 'debit'])) {
                throw new Exception("transLogInsert param type error", 1);
            }            

            ####### check optional param #######
            $isCredit = 0;
            if (isset($params['is_credit_point']) && $params['is_credit_point'] == 1) {
                $isCredit = 1;
            }
            $companyBankId = 0;
            if (isset($params['company_bank_id'])) {
                $companyBankId = $params['company_bank_id'];
            }


            $arr = array(
                'company_bank_id' => $companyBankId,
                'aid' => $params['aid'] ?? 0,
                'uid' => $params['uid'],
                'is_credit_point' => $isCredit,
                'trans_type' => $params['trans_type_id'],
                'ref_id' => $params['ref_id'],
                'user_bank_id' => $params['user_bank_id'] ?? 0,
                'to_company_bank_id' => $params['to_company_bank_id'] ?? 0,
                'from_company_bank_id' => $params['from_company_bank_id'] ?? 0,
                'remark' => $params['remark'] ?? null
            );

            ####### check balance for debit #######
            if ($params['type'] == 'debit') {
                $forUpdate = '';    // lock
                if ($_lock) {
                    $forUpdate = 'for update';
                }
                // $userBalance = $db->query("select coalesce(sum(credit-debit),0) as balance from ag_trans_log where uid=? and status=1 limit 1 $forUpdate", array($params['uid']))->fetch();
                $userBalance = $db->query("select coalesce(sum(credit-debit),0) as balance from ag_trans_log where company_bank_id=? and status=1 limit 1 $forUpdate", array($companyBankId))->fetch();

                // just in case
                if (empty($userBalance) || !isset($userBalance['balance'])) {
                    throw new Exception("transLogInsert User balance not found", 2);
                }

                if ($amount > $userBalance['balance']) {
                    throw new Exception("Insufficient balance to withdraw", ADMIN_SHOW_ERR);
                }

                $arr['debit'] = $amount;
            } else {
                $arr['credit'] = $amount;
            }

            $db->insert("ag_trans_log", $arr);

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => $db->lastInsertId()
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
