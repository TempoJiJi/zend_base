<?php

class Admin_TesterController extends Zend_Controller_Action {

	public function init() {
		$this->_helper->_layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true); //no view

		$this->view->cacheDir = ZEND_TMP_PATH;
	}

	public function indexAction() {
		echo "testing";
		exit;
	}

	public function testingAction($bool = false) {

		if (APPLICATION_ENV == 'production') exit;

		if ($bool) {
			return array("For testing");
		}
		$params = $this->getRequest()->getParams();

		$model = new Model_Tester();
		$data = $model->foo($params);

		$this->_helper->common->sendResponse($data['status'], $data['code'], $data['msg'], $data['data']);
	}

}