<?php

class Admin_BankController extends Zend_Controller_Action
{
	public function init()
	{
		$this->view->sess = new Zend_Session_Namespace('Zend_Auth');
		if (empty($this->view->sess->user) && !in_array($this->getRequest()->getParam('action'), array('auth/login', 'auth/logout', 'index'))) {
			$this->_redirect('/');
		}
		$this->aid = array('aid' => $this->view->sess->user['id']);
	}

	public function testerAction()
	{
		exit;
		// $this->view->title = 'Bank List';
		// if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
		// 	$params = $this->getRequest()->getParams();
		// 	// print_r($params);
		// 	$model = new Model_Index();
		// 	$data = $model->tester($params);
		// 	$this->response2(true, $data);
		// }
	}

	protected function response($status = false, $code = 1, $data = array(), $msg = null)
	{
		$this->_helper->_layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);  // no view
		$this
			->getResponse()
			->setHeader('Content-type', 'application/json')
			->setHeader('Access-Control-Allow-Origin', '*')
			->setHeader('Allow', 'POST')
			->setHttpResponseCode(200);

		$d = array(
			'status' => $status,
			'msg' => $msg,
			'code' => $code,
			'data' => $data
		);
		$this->getResponse()->setBody(json_encode($d));
	}

	protected function response_paginator($status = false, $code = 1, $draw, $data = array())
	{
		$this->_helper->_layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);  // no view
		$this
			->getResponse()
			->setHeader('Content-type', 'application/json')
			->setHeader('Access-Control-Allow-Origin', '*')
			->setHeader('Allow', 'POST')
			->setHttpResponseCode(200);

		$d = array(
			'status' => $status,
			'draw' => $draw,
			'recordsTotal' => $data['total'],
			'recordsFiltered' => $data['filterTotal'],
			'data' => ($status) ? $data['data'] : null,
			'code' => $code,
			'msg' => $data['msg']
		);
		$this->getResponse()->setBody(json_encode($d));
	}

	public function banksAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->banks($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function banksAddAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$compulsoryKey = array('bank_name');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->banksAdd($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function banksBlockAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->banksBlock($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}



	public function bankAddAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->bankAdd($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function bankAddSubmitAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array();
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->bankAddSubmit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function bankEditAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->bankEdit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankListAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->bankList($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function bankBlockAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->bankBlock($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankThresholdAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->bankList($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankThresholdBlockAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id', 'type');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->bankThresholdBlock($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankThresholdResetAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Bank();
			$response = $model->bankThresholdReset($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankReportAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->bankReport($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function bankReportAllAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Bank();
			$response = $model->bankReportAll($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

}
