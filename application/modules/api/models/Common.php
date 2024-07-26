<?php
require_once realpath(VENDOR_PATH . '/autoload.php');
require_once realpath(APPLICATION_PATH . '/../library/Plugins/phpqrcode.php');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class Model_Common extends Zend_Db_Table_Abstract
{

    public $_name = 'ag_users_table';

    /**
     * Example
     * @param sql: select username from users_table where 1
     * @param sql: select a.username, b.token from users_table as a left join users_token as b on b.uid=a.uid where 1
     * @param sql2 (For count): select count(1) from users_table where 1
     */
    public function tablePaginator($params = array(), $sql = '*', $sql2 = '*', $group = '*')
    {
        try {

            if ($sql == '*' || $sql2 == '*') {
                throw new Exception('missing sql', 0);
            }

            $db = $this->getAdapter();

            // TODO: Check and filter params
            $row = isset($params['start']) ? $params['start'] : 0;
            $rowperpage = isset($params['length']) ? $params['length'] : 10;
            $columnData = isset($params['columns']) ? $params['columns'] : array();
            $columnIndex = isset($params['order'][0]['column']) ? $params['order'][0]['column'] : 0; // Column index
            $columnName = isset($params['columns'][$columnIndex]['data']) ? $params['columns'][$columnIndex]['data'] : ''; // Column name
            $columnSortOrder = isset($params['order'][0]['dir']) ? $params['order'][0]['dir'] : 'asc'; // asc or desc
            $searchQuery = " ";
            $first = true;

            ## Search
            foreach ($columnData as $d) {
                // Skip if searchable is fasle
                if ($d['searchable'] == 'false' || !$d['searchable']) {
                    continue;
                }

                if (isset($d['search']) && isset($d['search']['value']) && ($d['search']['value'] != "")) {
                    $searchValue = strtolower($d['search']['value']);
                    $key = $d['data'];
                    if ($first) {
                        $searchQuery = $searchQuery . " AND ($key LIKE '%$searchValue%' ";
                        $first = false;
                    } else {
                        $searchQuery = $searchQuery . " AND $key LIKE '%$searchValue%' ";
                    }
                }
            }

            if (isset($params['search']) && isset($params['search']['value']) && ($params['search']['value'] != "")) {
                $searchValue = strtolower($params['search']['value']);
                foreach ($columnData as $d) {
                    // Skip if searchable is fasle
                    if ($d['searchable'] == 'false' || !$d['searchable']) {
                        continue;
                    }

                    $key = $d['data'];
                    if ($first) {
                        $searchQuery = $searchQuery . " AND ($key LIKE '%$searchValue%' ";
                        $first = false;
                    } else {
                        $searchQuery = $searchQuery . " OR $key LIKE '%$searchValue%' ";
                    }
                }
            }
            if (!$first) {
                $searchQuery = $searchQuery . ") ";
            }

            ## Total number of records without filtering
            $data = $db->query($sql2)->fetch();
            $totalRecords = $data['allcount'];

            ## Total number of records with filtering
            $data = $db->query($sql2 . $searchQuery)->fetch();
            $totalRecordwithFilter = $data['allcount'];

            ## Append search query
            if ($group != '*') {
                $sql = $sql . $searchQuery . " $group ";
            } else {
                $sql = $sql . $searchQuery;
            }

            ## Append order
            if (isset($params['columns'][$columnIndex]['data']) && !empty($params['columns'][$columnIndex]['data']) && isset($params['order'][0]['dir']) && !empty($params['order'][0]['dir'])) {
                $sql = $sql . " ORDER BY " . $columnName . " " . $columnSortOrder;
            }

            ## Fetch data
            $sql = $sql . " LIMIT $row,$rowperpage";
            // throw new Exception($sql, 0);    // For debugging

            $data = $db->query($sql)->fetchAll();

            return array(
                'status' => true,
                'draw' => isset($params['draw']) ? $params['draw'] : 0,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecordwithFilter,
                'data' => $data,
                'code' => 200,
                'msg' => 'Success'
            );
        } catch (Exception $e) {
            return array(
                'status' => false,
                'total' => 0,
                'filterTotal' => 0,
                'data' => null,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
    }

    public function removeBracketStr($str) {
        return trim(substr($str,0,strpos($str,'(')));
    }

    public function paginator($params = array())
    {
        $paginator = Zend_Paginator::factory($params['data']);
        $paginator->setCurrentPageNumber(isset($params['page']) ? $params['page'] : 1);
        $paginator->setItemCountPerPage(isset($params['rows']) ? $params['rows'] : 10);
        return $paginator;
    }

    public function generateJWT($uid)
    {
        try {
            $secret_key = "secret_key@!#$";
            $issuer_claim = ""; // this can be the servername
            $audience_claim = "THE_AUDIENCE";
            $issuedat_claim = time(); // issued at
            $token = array(
                "iss" => $issuer_claim,
                "aud" => $audience_claim,
                "iat" => $issuedat_claim,
                "data" => array(
                    "uid" => $uid
                )
            );

            $jwt = JWT::encode($token, $secret_key);

            return array(
                'status' => true,
                'data' => $jwt,
                'msg' => 'success',
                'code' => 100000
            );
        } catch (Exception $e) {
            return array(
                'status' => false,
                'data' => null,
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            );
        }
    }

    public function validateJWT($jwt)
    {
        try {
            $secret_key = "secret_key@!#$";
            $decoded = JWT::decode($jwt, $secret_key, array('HS256'));

            return array(
                'status' => true,
                'data' => $decoded,
                'msg' => 'success',
                'code' => 100000
            );
        } catch (Exception $e) {
            return array(
                'status' => false,
                'data' => null,
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            );
        }
    }

    public function validateUser($uid)
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query("select status from ag_users_table where uid=? limit 1", array($uid))->fetch();
            if (empty($data) || !isset($data['status'])) {
                return array(
                    'status' => false,
                    'data' => null
                );
            } else {
                if ($data['status'] != 1) {
                    return array(
                        'status' => false,
                        'data' => null
                    );
                }
            }
            return array(
                'status' => true,
                'data' => $data['status']
            );
        } catch (Exception $e) {
            return array(
                'status' => false,
                'data' => null
            );
        }
    }

    public function checkTokenValidity($params = array())
    {
        $db = $this->getAdapter();

        try {

            $data = $db->query("SELECT a.id,a.uid,a.expired_at from ag_users_token as a where a.token=? and a.uid=? and a.status=1 limit 1", array($params['token'], $params['uid']))->fetch();

            // Expired date checking
            // if (empty($data) || !isset($data['expired_at']) || date('Y-m-d H:i:s') > $data['expired_at']) {
            //     return array(
            //         'status' => false,
            //         'uid' => 0
            //     );
            // }
            // $sql = "update ag_users_token set expired_at=DATE_ADD(NOW(), INTERVAL " . TOKEN_EXPIRY . ") where id=? limit 1";
            // $db->query($sql, array($data['id']));

            // check status only
            if (empty($data) || !isset($data['uid'])) {
                return array(
                    'status' => false,
                    'uid' => 0
                );
            }

            return array(
                'status' => true,
                'uid' => $data['uid'],
            );
        } catch (Exception $ex) {

            return array(
                'status' => false,
                'uid' => 0
            );
        }
    }

    public function setDecimal($amount, $decimal)
    {

        // $floor = pow(10, $decimal);

        // $amount = floor(round(($amount * $floor), $decimal)) / $floor;

        $floor = pow(10, $decimal); // floor for extra decimal

        $amount = number_format((floor(strval($amount * $floor)) / $floor), $decimal, '.', '');

        return $amount;
    }

    // @param date format: YYYY-MM-DD
    public function dateValidate($date)
    {
        $orderdate = explode('-', $date);
        if (count($orderdate) != 3) {
            return false;
        }
        return checkdate($orderdate[1], $orderdate[2], $orderdate[0]);
    }

    public function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateObj = DateTime::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) == $date;
    }

    /*
    NOTE:
        Please call below function inside a try catch
        Please call below function inside a try catch
        Please call below function inside a try catch
    */
    public function getUid($params = array())
    {
        $message = messageCode($params['language']);
        $db = $this->getAdapter();

        if (empty($params) || !isset($params['username'])) {
            throw new Exception($message['100003'], 100003);
        }

        $userdata = $db->query('select uid from ag_users_table where username=? limit 1', array($params['username']))->fetch();

        if (empty($userdata) || !isset($userdata['uid'])) {
            throw new Exception($message['100003'], 100003);
        } else {
            return $userdata;
        }
    }

    public function getUidByFid($params = array())
    {
        $db = $this->getAdapter();

        if (empty($params) || !isset($params['fid'])) {
            throw new Exception("Missing params fid", 1);
        }

        $userdata = $db->query('select uid from ag_users_table where fid=? limit 1', array($params['fid']))->fetch();

        if (empty($userdata) || !isset($userdata['uid'])) {
            throw new Exception("Firebase uid not found: " . $params['fid'], 2);
        } else {
            return $userdata;
        }
    }

    public function uploadLocal($img)
    {
        // Allow in staging or development only
        // if (APPLICATION_ENV == 'production') {
        //     throw new Exception("Invalid upload method", 1);
        // }

        if (empty($img) || !isset($img['path']) || !isset($img['type'])) {
            throw new Exception("Missing img", 2);
        }

        $extension = (explode('/', $img["type"]))[1];
        $file_name = basename($img['path']) . ".$extension";
        $file_path = $_SERVER['DOCUMENT_ROOT'] . "/asset/img/upload/" . $file_name;

        if (move_uploaded_file($img['path'], $file_path)) {
            return C_GLOBAL_IMG . "upload/" . $file_name;
        } else {
            throw new Exception('Move uploaded failed', 3);
        }
    }

    public function getUploadLocal($img)
    {
        // Allow in staging or development only
        // if (APPLICATION_ENV == 'production') {
        //     throw new Exception("Invalid upload method", 1);
        // }

        if (empty($img) || !isset($img['path']) || !isset($img['type'])) {
            throw new Exception("Missing img", 2);
        }

        $extension = $img["type"] == "image/png" ? ".png" : ".jpg";

        $file_name = basename($img['path']) . $extension;
        $file_path = C_GLOBAL_UPLOAD . $file_name;

        if (move_uploaded_file($img['path'], $file_path)) {
            return C_GLOBAL_IMG . "upload/" . $file_name;
        } else {
            throw new Exception('Move uploaded failed', 3);
        }
    }

    public function qrcodeGenerator($data, $file_path)
    {
        try {
            if (empty($data) || empty($file_path)) {
                throw new Exception("qrcode param missing", 1);
            }

            QRcode::png($data, $file_path);

            return array(
                'status' => true,
                'msg' => 'success'
            );
        } catch (Exception $ex) {
            return array(
                'status' => false,
                'msg' => $ex->getMessage()
            );
        }
    }

    public function notify($type = "", $body = array(), $company_id = 0, $all = false, $item_id = 0)
    {
        try {

            $factory = (new Factory)->withServiceAccount(APPLICATION_PATH . '/modules/admin/json/snowice-firebase.json');
            $database = $factory->createDatabase();

            $db = $this->getAdapter();

            if (!$all) {
                if (empty($company_id) || $company_id == 0) {
                    throw new Exception("company id not found: " . $company_id, 0);
                }
                $data = $db->query("select fid, uid from ag_users_table where company_id=? and status=1", array($company_id))->fetchAll();
            } else {
                $data = $db->query("select fid, uid from ag_users_table where status=1")->fetchAll();
            }

            foreach ($data as $d) {
                $to = $d['fid'];
                $uid = $d['uid'];

                $reference = $database->getReference("notiToken/$to");
                $value = $reference->getValue();

                if (!isset($value['badge']) || !isset($value['id']) || $value['id'] == "") {
                    continue;
                }

                $badge = $value['badge'];
                $notiToken = $value['id'];

                $res = $this->post_notify($body, $notiToken);

                if ($res['success'] == 1 && $res['failure'] == 0) {
                    $database->getReference("notiToken/$to/badge")->set($badge + 1);
                } else {
                    $db->query("insert into notification_log (type, to_uid, body, success, failure, response) values (?,?,?,?,?,?)", array($type, $uid, json_encode($body), 0, 1, json_encode($res)));

                    continue;
                }

                $db->query("insert into notification_log (type, to_uid, body, success, failure, response, to_token, item_id) values (?,?,?,?,?,?,?,?)", array($type, $uid, json_encode($body), $res['success'], $res['failure'], json_encode($res), $notiToken, $item_id));
            }

            return array(
                'status' => true,
                'msg' => 'notify success',
                'code' => 100000
            );
        } catch (Exception $ex) {
            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
        }
    }

    public function notifyPersonal($type = "", $body = array(), $uid, $item_id = 0)
    {
        $db = $this->getAdapter();

        $db->query("insert into ag_notification_log (type, to_uid, body, item_id) values (?,?,?,?)", array($type, $uid, json_encode($body), $item_id));
        $logId = $db->lastInsertId();

        try {

            $factory = (new Factory)->withServiceAccount(APPLICATION_PATH . '/modules/admin/json/snowice-firebase.json');
            $database = $factory->createDatabase();
            $data = $db->query("select fid from ag_users_table where uid=? and status=1 limit 1", array($uid))->fetch();

            $to = $data['fid'];

            $reference = $database->getReference("notiToken/$to");
            $value = $reference->getValue();
            if (!isset($value['badge']) || !isset($value['id']) || $value['id'] == "") {
                throw new Exception("Value not found", 0);
            }

            $badge = $value['badge'];
            $notiToken = $value['id'];

            $res = $this->post_notify($body, $notiToken);

            if ($res['success'] == 1 && $res['failure'] == 0) {
                $database->getReference("notiToken/$to/badge")->set($badge + 1);
            }

            $db->query("update ag_notification_log set success=?, failure=?, response=? where id=? limit 1", array($res['success'] ?? 0, $res['failure'] ?? 1, json_encode($res), $logId));

            return array(
                'status' => true,
                'msg' => 'notify success',
                'code' => 100000
            );
        } catch (Exception $ex) {
            print($ex->getMessage());
            $db->query("update ag_notification_log set success=0, failure=1, response=? where id=? limit 1", array($ex->getMessage(), $logId));

            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
        }
    }

    public function notifyTopic($type = "", $body = array(), $topic, $item_id = 0, $to_uid = 0)
    {
        $db = $this->getAdapter();

        $db->query("insert into ag_notification_log (type, to_topic, to_uid, body, item_id) values (?,?,?,?,?)", array($type, $topic, $to_uid, json_encode($body), $item_id));
        $logId = $db->lastInsertId();

        try {

            if (empty($topic) || $topic == null) {
                throw new Exception("Invalid topic", 0);
            }
    
            $factory = (new Factory)->withServiceAccount(APPLICATION_PATH . '/modules/admin/json/snowice-firebase.json');
            $messaging = $factory->createMessaging();

            $message = CloudMessage::fromArray([
                'topic' => $topic,
                'notification' => $body
            ]);
            
            $res = $messaging->send($message);

            $db->query("update ag_notification_log set success=?, failure=?, response=? where id=? limit 1", array(1, 0, json_encode($res), $logId));

            return array(
                'status' => true,
                'msg' => 'notify success',
                'code' => 100000
            );
        } catch (Exception $ex) {

            $db->query("update ag_notification_log set success=0, failure=1, response=? where id=? limit 1", array($ex->getMessage(), $logId));

            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
        }
    }

    public function send_email($toEmail, $subject, $htmlContent, $type = 'order-confirm')
    {

        try {
            $db = $this->getAdapter();

            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->addAddress($toEmail);
            $mail->setFrom('', 'System');
            $mail->Username = '';
            $mail->Password = '';
            $mail->Host = '';
            $mail->Subject = " - " . $subject;
            $mail->Body = $htmlContent;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->isHTML(true);
            // $mail->SMTPDebug  = 2;

            $result = $mail->send();

            $db->query("insert into ag_email_log (to_email, content, result, type) values (?,?,?,?)", [$toEmail, $htmlContent, json_encode($result), $type]);
            
            return array(
                'status' => $result,
                'code' => 100000,
                'msg' => 'success',
                'data' => null
            );
        } catch (Exception $ex) {
            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode(),
                'data' => null
            );
        }
    }

    public function send_whatsapp($toMobile, $body, $refId = null)
    {

        try {
            $db = $this->getAdapter();

            $db->query("insert into ag_whatsapp_log (from_mobile, to_mobile, content, ref_id) values (?,?,?,?)", [TWILIO_FROM, $toMobile, $body, $refId]);
            $logId = $db->lastInsertId();

            $from = TWILIO_FROM;
            $sid = TWILIO_SID;
            $token = TWILIO_TOKEN;
            $twilio = new Client($sid, $token);

            $message = $twilio->messages
                ->create(
                    "whatsapp:+$toMobile", // to
                    [
                        "from" => "whatsapp:+$from",
                        "body" => $body
                    ]
                );

            $db->query("update ag_whatsapp_log set result=?, send_status=1 where id=? limit 1", [$message, $logId]);

            return array(
                'status' => true,
                'code' => 100000,
                'msg' => 'success',
                'data' => null
            );
            
        } catch (Exception $ex) {

            $db->query("update ag_whatsapp_log set result=?, send_status=0 where id=? limit 1", [$ex->getMessage(), $logId]);

            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode(),
                'data' => null
            );
        }
    }

    public function mobile_reg($mobileno)
    {
        try {

            // remove + first if exists
            $mobileno = str_replace('+', '', $mobileno);

            // first two character is 01
            if (preg_match('/^01/', $mobileno)) {
                $mobileno = "6" . $mobileno;
            }
            
            // first two character is country code
            elseif (preg_match('/^60/', $mobileno)) {
                $mobileno = $mobileno;
            }
            
            // first two character not even 60 and 01
            else {
                $mobileno = "60" . $mobileno;
            }

            return $mobileno;

        } catch (Exception $ex) {
            return '0';
        }
    }

    public function curl_post($url, $input = array(), $auth = array(), $header = "")
    {
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => false,
        ]);

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $input);
        curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        if (!empty($auth)) {
            curl_setopt($this->curl, CURLOPT_USERPWD, "$auth[username]:$auth[password]");
        }
        if (!empty($header)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        }

        $response = curl_exec($this->curl);
        $this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->contentType = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
        curl_close($this->curl);
        return json_decode($response, true);
    }

    public function curl_get($url, $input = array(), $auth = array(), $header = "")
    {

        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => false,
        ]);

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        if (!empty($auth)) {
            curl_setopt($this->curl, CURLOPT_USERPWD, "$auth[username]:$auth[password]");
        }
        if (!empty($header)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($input)) {
            $url = "$url?" . http_build_query($input);
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);

        $response = curl_exec($this->curl);
        $this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->contentType = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
        curl_close($this->curl);
        return json_decode($response, true);
    }

    public function post_notify($body = array(), $notiToken)
    {
        try {

            $url = "https://fcm.googleapis.com/fcm/send";
            $header = array(
                "Content-Type: application/json",
                "Authorization: key=AAAAfK9dxqA:APA91bG7Kv2OgypsUr10qxNRBvMkWbPkaYBoCG3xtQBYFPFf_DqgU3h-HiFLml_NcYnv-li0wSyjHpbrsgsbEmY8mS-kZkpGkdNczIj3Tp88nHkOfzDY72iQlpta8d-rNNccuFhHqflv"
            );
            $input = array(
                "to" => $notiToken,
                "notification" => $body
            );
            $this->curl = curl_init();
            curl_setopt_array($this->curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => false,
            ]);
            // if (APPLICATION_ENV !== 'production') {
            //     curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            //     curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
            // }
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($input));
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);

            $response = curl_exec($this->curl);
            $this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $this->contentType = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
            curl_close($this->curl);

            return json_decode($response, true);
        } catch (Exception $ex) {

            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode()
            );
        }
    }

    /**
     * @param filename
     * @param ext: extension
     * @param folder: aws folder
     */
    public function awsS3UploadPublic($name = 'images', $folder = '')
    {
        try {

            $fileData = $this->getUploadFile($name);
            if (!isset($fileData['status']) || !$fileData['status'] || empty($fileData['data'])) {
                throw new Exception($fileData['msg'], ADMIN_SHOW_ERR);
            }
            $fileData = $fileData['data'];

            if (!isset($fileData['filename']) || empty($fileData['filename'])) {
                throw new Exception("AWS upload missing filename");
            }
            if (!isset($fileData['ext']) || empty($fileData['ext'])) {
                throw new Exception("AWS upload missing ext");
            }

            $credentials = new Aws\Credentials\Credentials(AWS_KEY, AWS_SECRET);
            $s3 = new Aws\S3\S3Client([
                'region'      => AWS_REGION,
                'credentials' => $credentials
            ]);

            $key = AWS_BUCKET_FOLDER . $folder . basename($fileData['filename']) . "." . $fileData['ext'];

            $result = $s3->putObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $key,
                'SourceFile' => $fileData['filename'],
            ]);

            return array(
                'status' => true,
                'msg' => 'success',
                'code' => SUC_CODE,
                'data' => AWS_PATH . $key
            );

        } catch (Exception $ex) {
            return array(
                'status' => false,
                'msg' => $ex->getMessage(),
                'code' => $ex->getCode(),
                'data' => null
            );
        }
    }

    public function getUploadFile($name = 'images')
    {
        if (isset($_FILES) && !empty($_FILES)) {
            if (isset($_FILES[$name]) && !empty($_FILES[$name]['name'])) {
                if ($_FILES[$name]['error'] > 0) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => 'file upload error'
                    );
                }
                if (!in_array($_FILES[$name]['type'], array('image/png', 'image/jpg', 'image/jpeg'))) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => "File Type must be png, jpg or jpeg"
                    );
                }
                if ($_FILES[$name]['size'] > 3145728) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => 'File size must less than 3MB'
                    );
                }

                $extension = (explode('/', $_FILES[$name]["type"]))[1];

                $img = array(
                    'filename' => $_FILES[$name]['tmp_name'],
                    'type' => $_FILES[$name]['type'],
                    'ext' => $extension
                );
                return array(
                    'status' => true,
                    'data' => $img,
                    'msg' => 'Succss'
                );
            }
        } else {
            return array(
                'status' => false,
                'data' => null,
                'msg' => "Missing upload file $name"
            );
        }
    }
}
