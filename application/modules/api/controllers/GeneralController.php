<?php

class Api_GeneralController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_helper->_layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true); //no view
        $this->view->cacheDir = ZEND_TMP_PATH;
    }

    protected function paginator($params = array())
    {
        $paginator = Zend_Paginator::factory($params['data']);
        $paginator->setCurrentPageNumber(isset($params['page']) ? $params['page'] : 1);
        $paginator->setItemCountPerPage(isset($params['rows']) ? $params['rows'] : 10);
        return $paginator;
    }

    public function indexAction()
    {
        exit;
    }

    public function fontChangeLangAction($bool = false)
    {

        $params = $this->getRequest()->getParams();

        $data = array(
            'time' => date('H:i:m'),
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'timestamp' => (string) strtotime(date('Y-m-d H:i:s'))
        );

        $this->_helper->common->sendResponse(true, 100000, 'success', $data);
    }

    public function getServerTimeAction($bool = false)
    {

        $params = $this->getRequest()->getParams();

        $data = array(
            'time' => date('H:i:m'),
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'timestamp' => (string) strtotime(date('Y-m-d H:i:s'))
        );

        $this->_helper->common->sendResponse(true, 100000, 'success', $data);
    }

    public function versionControlAction($bool = false)
    {

        $params = $this->getRequest()->getParams();

        $model = new Model_General();
        $data = $model->versionControl($params);

		$this->getResponse()->setHttpResponseCode(200);
		$responseToBeSend = array(
			'status' => $data['status'],
			'code' => $data['code'],
			'data' => ($data['data'] === NULL) ? [] : $data['data'],
            'server_status' => (!isset($data['server_status'])) ? 'production' : $data['server_status'],
			'msg' => $data['msg'],
		);

		echo json_encode($responseToBeSend, JSON_UNESCAPED_SLASHES);

        // $this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
    }

    public function countryListAction($bool = false)
    {
        $params = $this->getRequest()->getParams();
        $model = new Model_General();
        $data = $model->countryList($params);
        $this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
    }

    public function getDashboardAction($bool = false)
    {
        $params = $this->getRequest()->getParams();
        $model = new Model_General();
        $data = $model->getDashboard($params);
        $this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
    }

    public function getContentAction($bool = false)
    {
        $params = $this->getRequest()->getParams();
        $model = new Model_General();
        $data = $model->getContent($params);
        $this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
    }


    public function getDepartmentAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array();
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_General();
		$data = $model->getDepartment($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

    public function getCommissionAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array();
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_General();
		$data = $model->getCommission($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

    public function getNotificationLogAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array();
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_General();
		$data = $model->getNotificationLog($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

}
