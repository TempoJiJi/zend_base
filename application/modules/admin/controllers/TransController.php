<?php

class Admin_TransController extends Zend_Controller_Action
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
		// $this->view->title = 'Trans List';
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

	public function transAddAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Trans();
			$response = $model->transAdd($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function transAddSubmitAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array("amount", "trans_type", "bank_id");
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Trans();
			$response = $model->transAddSubmit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function transEditAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Trans();
			$response = $model->transEdit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function transEditSubmitAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array("amount", "trans_type", "bank_id");
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Trans();
			$response = $model->transEditSubmit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}


	public function transListAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Trans();
			$response = $model->transList($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function transTypeAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Trans();
			$response = $model->transType($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function transReportAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Trans();
			$response = $model->transReport($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function transRevokeAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Trans();
			$response = $model->transRevoke($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}


}
