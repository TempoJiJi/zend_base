<?php

class Api_IndexController extends Zend_Controller_Action {

	public function init() {
		$this->_helper->_layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true); //no view

		$this->view->cacheDir = ZEND_TMP_PATH;
	}

	public function indexAction() {
		exit;
	}

}
