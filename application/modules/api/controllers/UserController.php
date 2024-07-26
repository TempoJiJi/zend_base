<?php
class Api_UserController extends Zend_Controller_Action
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

	// For home token checing
	public function indexAction()
	{
		$this->_helper->common->sendResponse(true, SUC_CODE, 'success', null);
	}


	public function loginAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array("entry_code");
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_Users();
		$data = $model->login($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

	public function drawAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array("uid");
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_Users();
		$data = $model->draw($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

	public function getDrawDetailsAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array();
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_Users();
		$data = $model->getDrawDetails($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

	public function checkDrawAction($bool = false)
	{
		$params = $this->getRequest()->getParams();

		// required params checking
		$required = array("phone", "ic");
		foreach ((array)$required as $v) {
			if (!isset($params[$v]) || empty($params[$v])) {
				return $this->_helper->common->sendResponse(false, 1, "Missing required params ($v)", null);
			}
		}

		$model = new Model_Users();
		$data = $model->checkDraw($params);
		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}


}
