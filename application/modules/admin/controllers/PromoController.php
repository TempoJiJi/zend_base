<?php

class Admin_PromoController extends Zend_Controller_Action
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
		// $this->view->title = 'Promo List';
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

	public function promoAddAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Promo();
			$response = $model->promoAdd($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function promoAddSubmitAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array("amount", "uid");
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Promo();
			$response = $model->promoAddSubmit($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function promoListAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Promo();
			$response = $model->promoList($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

    public function promoBlockAction()
	{
		if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

            $compulsoryKey = array('item_id');
            foreach($compulsoryKey as $key) {
                if (!isset($params[$key]) || empty($params[$key])) {
                    return $this->response(false, 0, null, "Missing $key");
                }    
            }

			$model = new Model_Promo();
			$response = $model->promoBlock($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

	public function promoReportAction()
	{
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
			$params = $this->getRequest()->getParams();

			$model = new Model_Promo();
			$response = $model->promoReport($params);
			$this->response($response['status'], $response['code'], $response['data'], $response['msg']);
		}
	}

}
