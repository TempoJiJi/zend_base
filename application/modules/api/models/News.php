<?php

class Model_News extends Zend_Db_Table_Abstract
{
    public $_name = 'ag_news_table';

    public function getCountry($params = array())
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query("select id, name from ag_news_country where status=1")->fetchAll();

            return array(
                'status' => true,
                'data' => $data,
                'msg' => 'Success',
                'code' => SUC_CODE
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


    public function getCategory($params = array())
    {
        try {
            $db = $this->getAdapter();

            $data = $db->query("select id, name from ag_news_category where status=1")->fetchAll();

            return array(
                'status' => true,
                'data' => $data,
                'msg' => 'Success',
                'code' => SUC_CODE
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

    public function getNews($params = array())
    {
        try {
            $db = $this->getAdapter();
            $common_model = new Model_Common();

            $select = $this->select()->setIntegrityCheck(false)
                ->from(['a' => 'ag_news_table'], ["id", "title", "content", "img", "DATE_FORMAT(a.created_at, '%d %b %Y') as created_at"])
                ->joinLeft(['b' => 'ag_news_category'], 'a.category_id = b.id', ["b.name as category_name"])
                ->where('a.status = 1')
                ->order('a.id desc');

            if(isset($params['news_id']) && !empty($params['news_id'])) {
                $select->where("a.id = ?", $params['news_id']);
            }

            if(isset($params['category_id']) && !empty($params['category_id'])) {
                $select->where("a.category_id = ?", $params['category_id']);
            }

            if(isset($params['country_id']) && !empty($params['country_id'])) {
                $select->where("a.country_id = ?", $params['country_id']);
            }

            if(isset($params['search_month']) && !empty($params['search_month'])) {
                $select->where("month(a.created_at) = ?", $params['search_month']);
                $select->where("year(a.created_at) = ?", date('Y'));
            }

            if(isset($params['search_title']) && !empty($params['search_title']) && $params['search_title'] != null) {
                $select->where("a.title like ?", "%".$params['search_title']. "%");
            }

            $result = $common_model->paginator(array(
                'data' => $select,
                'page' => $params['page'] ?? 1,
                'rows' => $params['rows'] ?? 10,
            ));

            $items = $result->getCurrentItems()->toArray();
            $data = array(
                'item' => $items,
                'pagination' => $result->getPages(),
            );

            return array(
                'status' => true,
                'data' => $data,
                'msg' => 'Success',
                'code' => SUC_CODE
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


}
