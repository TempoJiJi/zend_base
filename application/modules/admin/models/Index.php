<?php

require_once realpath(VENDOR_PATH . '/autoload.php');
// require_once realpath(APPLICATION_PATH . '/../library/Plugins/aws/aws-autoloader.php');
require_once realpath(APPLICATION_PATH . '/modules/api/models/Common.php');
use Shuchkin\SimpleXLSX;

/**
 * Normal admin using addContentFilter and contentFilter
 */
class Model_Index extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_admin_table';

    /**
     * Note that aclList, getCompanyData will used by both module sadmin and admin
     */
    public function aclList($uid = null, $user_type, $apiname = null)
    {
        if ($uid == null || $apiname == null || $user_type == null) {
            return false;
        }

        $db = $this->getAdapter();

        if ($user_type == 'admin') {
            $access = $db->query('select access from admin_table where id=? and status=1 limit 1', array($uid))->fetch();
        } elseif ($user_type == 'doctor') {
            $access = $db->query("select a.value as access from settings as a left join users_table as b ON b.uid=? where a.setting_name='doctor_admin' and a.status=1 and b.status=1 limit 1", array($uid))->fetch();
        } else {
            $access = null;
        }

        if ($access == null || empty($access)) {
            return false;
        }

        // # Get menu id
        $menuID = $db->query('select menu_id, root_only from admin_menu where apiname=? and status=1 limit 1', array($apiname))->fetch();
        if (empty($menuID) || !isset($menuID['menu_id']) || !isset($menuID['root_only'])) {
            return false;
        }

        if ($menuID['menu_id'] == 0) {
            return true;
        }

        if ($menuID['root_only'] == 1) {
            if ($uid != 1) {
                return false;
            }
        }

        $data = explode(',', $access['access']);
        if (!in_array($menuID['menu_id'], $data)) {
            return false;
        } else {
            return true;
        }
    }

    public function uploadLocal($img, $check = true)
    {
        // for add, edit no need check
        if ($check) {
            if (empty($img) || !isset($img['path']) || !isset($img['type'])) {
                throw new Exception('Missing img', 2);
            }
        }

        $extension = (explode('/', $img['type']))[1];
        $file_name = basename($img['path']) . ".$extension";
        $file_path = C_GLOBAL_UPLOAD_PATH . $file_name;

        if (move_uploaded_file($img['path'], $file_path)) {
            return C_GLOBAL_UPLOAD_PATH_URL . $file_name;
        } else {
            throw new Exception('Move uploaded failed', 3);
        }
    }

    public function getUploadImg($name = 'images')
    {
        if (isset($_FILES) && !empty($_FILES)) {
            if (isset($_FILES[$name]) && !empty($_FILES[$name]['name'])) {
                if ($_FILES[$name]['error'] > 0) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => 'image upload error'
                    );
                }
                if (!in_array($_FILES[$name]['type'], array('image/png', 'image/jpg', 'image/jpeg'))) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => "Image must be png, jpg or jpeg"
                    );
                }
                if ($_FILES[$name]['size'] > 3145728) {
                    return array(
                        'status' => false,
                        'data' => null,
                        'msg' => 'Image must be less than 3MB'
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

    public function getUploadFile($name)
    {
        if (isset($_FILES) && !empty($_FILES)) {
            if (isset($_FILES[$name]) && !empty($_FILES[$name]['name'])) {
                if ($_FILES[$name]['error'] > 0) {
                    return array(
                        'status' => false,
                        'img' => null,
                        'msg' => 'File upload error'
                    );
                }

                // NOTE: Add file type here
                if (!in_array($_FILES[$name]['type'], array('application/pdf'))) {
                    return array(
                        'status' => false,
                        'img' => null,
                        'msg' => 'File must be pdf'
                    );
                }
                $file = array(
                    'path' => $_FILES[$name]['tmp_name'],
                    'type' => $_FILES[$name]['type'],
                );
                return array(
                    'status' => true,
                    'file' => $file,
                    'msg' => 'Succss'
                );
            }
        }
    }

    /**
     * Note: $col sequence must match with $data
     */
    public function excelExport($fn = '', $col = array(), $data = array())
    {
        try {
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');  // keeps ie happy
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Encoding: UTF-8');
            header('Content-Transfer-Encoding: binary');
            ob_end_clean();
            $filename = $fn . '.csv';
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment;filename=$filename");
            $fp = fopen('php://output', 'w');
            fpassthru($fp);
            fputcsv($fp, $col);
            foreach ($data as $res) {
                fputcsv($fp, $res);
            }
            fclose($fp);
            ob_end_flush();
            exit();
        } catch (Exception $e) {
            return array(
                'status' => false,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
    }

    public function newsCountry($params)
    {
        try {
            $common_model = new Model_Common();

            // Product table
            $sql1 = 'select a.id, a.name from ag_news_country as a where a.status=1 ';
            $sql2 = 'select count(1) as allcount from ag_news_country as a where a.status=1 ';
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

    public function newsCategory($params)
    {
        try {
            $common_model = new Model_Common();
            $categoryType = $params['category_type'];

            // Product table
            $sql1 = "select a.id, a.name from ag_news_category as a where a.status=1 and a.type='$categoryType'";
            $sql2 = "select count(1) as allcount from ag_news_category as a where a.status=1 and a.type='$categoryType'";
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

    public function newsCategoryAdd($params)
    {
        try {
            $db = $this->getAdapter();

            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $db->update('ag_news_category', ['name' => $params['category_name'], 'type' => $params['category_type']], ['id = ?' => $params['item_id']]);
            } else {
                $db->insert('ag_news_category', ['name' => $params['category_name'], 'type' => $params['category_type']]);
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


    public function newsCategoryDelete($params)
    {
        $db = $this->getAdapter();

        try {
            $db->beginTransaction();

            $db->query('update ag_news_category set status=0 where status=1 and id=? limit 1', array($params['item_id']));
            // $db->query('update ag_news_table set category_id=1 where category_id=?', array($params['item_id']));

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


    public function newsList($params)
    {
        try {
            $common_model = new Model_Common();
            // $categoryType = $params['category_type'];

            // Product table
            $sql1 = "select * from ag_user_table as a where a.status=1 ";
            $sql2 = "select count(1) as allcount from ag_user_table as a where a.status=1 ";
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

    public function newsDetail($params)
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query('select * from ag_news_table where status=1 and id=? limit 1', array($params['item_id']))->fetch();

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

    public function newsDelete($params)
    {
        try {
            $db = $this->getAdapter();

            $db->query('update ag_news_table set status=0 where status=1 and id=? limit 1', array($params['item_id']));

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

    public function newsAdd($params)
    {
        try {
            $common_model = new Model_Common();
            $db = $this->getAdapter();
            $itemId = 0;
            if (isset($params['item_id']) && !empty($params['item_id'])) {
                $itemId = $params['item_id'];
            }

            if ($itemId == 0) {
                $coverImg = $this->getUploadImg('news_cover');
                if (!isset($coverImg['status']) || !$coverImg['status'] || empty($coverImg['data'])) {
                    throw new Exception($coverImg['msg'], ADMIN_SHOW_ERR);
                }

                $coverImg['data']['folder'] = 'news';
                $awsRes = $common_model->awsS3UploadPublic($coverImg['data']);
                if (!isset($awsRes['status']) || !$awsRes['status']) {
                    throw new Exception($awsRes['msg'], 0);
                }

                $db->insert("ag_news_table", array(
                    'category_id' => $params['news_category'],
                    // 'country_id' => $params['news_country'],
                    'title' => $params['news_title'],
                    'type' => $params['news_type'],
                    'content' => $params['news_content'],
                    'img' => $awsRes['data'],
                    'category_type' => $params['category_type'],
                    'video_link' => empty($params['video_link']) ? null : $params['video_link'],
                ));
            } else {

                $arr = array(
                    'category_id' => $params['news_category'],
                    // 'country_id' => $params['news_country'],
                    'title' => $params['news_title'],
                    'type' => $params['news_type'],
                    'content' => $params['news_content'],
                    'category_type' => $params['category_type'],
                    'video_link' => empty($params['video_link']) ? null : $params['video_link'],
                );

                // check if image update
                if (isset($_FILES) && !empty($_FILES)) {
                    $coverImg = $this->getUploadImg('news_cover');
                    if (!isset($coverImg['status']) || !$coverImg['status'] || empty($coverImg['data'])) {
                        throw new Exception($coverImg['msg'], ADMIN_SHOW_ERR);
                    }

                    $coverImg['data']['folder'] = 'news';
                    $awsRes = $common_model->awsS3UploadPublic($coverImg['data']);
                    if (!isset($awsRes['status']) || !$awsRes['status']) {
                        throw new Exception($awsRes['msg'], 0);
                    }

                    $arr['img'] = $awsRes['data'];
                }

                $db->update("ag_news_table", $arr, ['id = ?' => $itemId]);


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


    public function raceCardList($params)
    {
        try {
            $common_model = new Model_Common();

            // Product table
            $sql1 = 'select a.id, a.race_name, a.region_code, a.race_date from ag_horse_race_card as a where a.status=1 and a.lang="en"';
            $sql2 = 'select count(1) as allcount from ag_horse_race_card as a where a.status=1 ';
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

    public function raceCardDetail($params)
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query('select json_data from ag_horse_race_card where status=1 and id=? limit 1', array($params['item_id']))->fetch();

            $data = json_decode($data['json_data'], true);

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

    public function raceHorsePick($params)
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query('select * from ag_horse_pick where status=1 and race_card_id=? and number=? limit 1', array($params['item_id'], $params['number']))->fetch();

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

    public function raceHorsePickUpdate($params)
    {
        try {
            $db = $this->getAdapter();
            $common_model = new Model_Common();

            // remove previously one
            $db->query('update ag_horse_pick set status=0 where race_card_id=? and number=? and status=1', array($params['item_id'], $params['number']));

            // only insert if type is hot or cold pick
            if ($params['pick_type'] != 'normal') {

                //get horse detail
                $raceData = $db->query('select json_data from ag_horse_race_card where status=1 and id=? limit 1', array($params['item_id']))->fetch();
                $raceData = json_decode($raceData['json_data'], true);

                $horse = [];
                foreach($raceData['runner_lists'] as $r) {
                    if ($r['number'] == $params['number']){
                        $horse = $r;
                        break;
                    }
                }

                if (empty($horse)) {
                    throw new Exception("Horse Detail not found", 0);
                }

                $insertArr = array(
                    'race_card_id' => $params['item_id'],
                    'pick_type' => $params['pick_type'],
                    'horse_name' => $horse['name'],
                    'jockey' => $horse['jockey'],
                    'number' => $params['number'],
                    'trainer' => $horse['trainer'],
                    'desc' => $params['desc'],
                );

                // upload img to aws
                if (isset($_FILES) && !empty($_FILES)) {
                    $coverImg = $this->getUploadImg('img_cover');
                    if (!isset($coverImg['status']) || !$coverImg['status'] || empty($coverImg['data'])) {
                        throw new Exception($coverImg['msg'], ADMIN_SHOW_ERR);
                    }
        
                    $coverImg['data']['folder'] = 'horse';
                    $awsRes = $common_model->awsS3UploadPublic($coverImg['data']);
                    if (!isset($awsRes['status']) || !$awsRes['status']) {
                        throw new Exception($awsRes['msg'], 0);
                    }
                    $insertArr['img'] = $awsRes['data'];
                }

                // insert
                $db->insert("ag_horse_pick", $insertArr);    
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

    public function raceCardDelete($params)
    {
        try {
            $db = $this->getAdapter();

            $db->query('update ag_horse_race_card set status=0 where status=1 and id=? limit 1', array($params['item_id']));

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
