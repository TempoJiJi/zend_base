<?php

class Web_IndexController extends Zend_Controller_Action
{

	public function init()
	{
		// $sess = new Zend_Session_Namespace('Zend_Auth');
		// $beforeLoginAction = array(
		// 	'login',
		// 	'forgot-password',
		// 	'register',
		// 	'index',
		// 	'category',
		// 	'product',
		// 	'cart'
		// );
		// if ($sess->user && in_array($this->getRequest()->getParam('action'), $beforeLoginAction)) {
		// 	$this->redirect('/home');
		// }
		// if (empty($sess->user) && !in_array($this->getRequest()->getParam('action'), $beforeLoginAction) && $this->getRequest()->getParam('action') != 'order-complete') {
		// 	$this->redirect('/');
		// }

		$this->_helper->_layout->setLayout('layout_web');
		$this->view->params = $this->getRequest()->getParams();
		if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
			$this->_helper->_layout()->disableLayout();
			$this->_helper->viewRenderer->setNoRender(true);
			$this->getResponse()
				->setHeader('Content-type', 'application/json')
				->setHeader('Access-Control-Allow-Origin', '*')
				->setHeader('Allow', 'POST')
				->setHttpResponseCode(200);
		}
	}

	public function changeLangAction()
	{
		if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
			$params = $this->getRequest()->getParams();
			$params = $this->getParams($params);

			$sessLang = new Zend_Session_Namespace('Zend_lang');
			$lang = $params['lang'] ?? 'en';
			$sessLang->lang = $lang;

			$this->response(array('status' => true));
		}
	}

	/**
	 * render only
	 */
	public function indexAction(){}


	/**
	 * check auth and redirect
	 */
	public function loginPageAction()
	{
		$sess = new Zend_Session_Namespace('Zend_Auth');
		if (isset($sess->user)) {
			$this->redirect('/');
		}
	}

	public function testerAction()
	{
		exit;
		// $this->view->title = 'User List';
		// if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
		// 	$params = $this->getRequest()->getParams();
		// 	// print_r($params);
		// 	$model = new Model_Index();
		// 	$data = $model->tester($params);
		// 	$this->response2(true, $data);
		// }
	}

	/**
	 * Non Async
	 */
	public function indexListAction()
	{
		if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
			$params = $this->getRequest()->getParams();
			$data = $this->doPost('/index/index-list', $this->getParams($params));
			$this->response($data);
		}
	}

	/**
	 * Async
	 */
	public function indexListAsyncAction()
	{
		$sess = new Zend_Session_Namespace('Zend_Auth');
		Zend_Session::writeClose();

		if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
			$params = $this->getRequest()->getParams();
			$data = $this->doPost('/index/index-list', $this->getParams($params));
			$this->response($data);
		}
	}

	public function uploadAvatarAction()
	{
		try {

			$this->_helper->_layout()->disableLayout();
			$this->_helper->viewRenderer->setNoRender(true);
			$this->getResponse()
				->setHeader('Content-type', 'application/json')
				->setHeader('Access-Control-Allow-Origin', '*')
				->setHeader('Allow', 'POST')
				->setHttpResponseCode(200);

			$sess = new Zend_Session_Namespace('Zend_Auth');
			if (!isset($sess->user) || !isset($sess->user['uid']) || $sess->user['uid'] == null) {
				throw new Exception("Something went wrong, please try again later", 0);
			}

			$imgData = $this->getUploadAvatar();
			if (!$imgData['status'] || !isset($imgData['img'])) {
				throw new Exception("Something went wrong, please try again later", 0);
			}
			$imgPath = $this->uploadLocal($imgData['img']);

			$data = $this->doPost('/user/update-avatar', array('img' => $imgPath));
			$sess->user['image'] = $imgPath;
			$this->response($data);
		} catch (Exception $ex) {
			$this->response(array(
				'status' => false,
				'msg' => 'Something went wrong, please try again later',
				'data' => null
			));
		}
	}

	protected function getParams($params)
	{
		$pData = array();
		foreach ($params as $k => $v) {
			$pData[$k] = $v;
		}
		return $pData;
	}

	public function uploadLocal($img)
	{
		if (empty($img) || !isset($img['path']) || !isset($img['type'])) {
			throw new Exception("Missing img", 2);
		}

		$extension = (explode('/', $img["type"]))[1];
		$file_name = basename($img['path']) . ".$extension";
		$file_path = C_GLOBAL_UPLOAD_AVATAR . $file_name;

		if (move_uploaded_file($img['path'], $file_path)) {
			return C_GLOBAL_UPLOAD_AVATAR_URL . $file_name;
		} else {
			throw new Exception('Move uploaded failed', 3);
		}
	}

	public function getUploadAvatar()
	{
		if (isset($_FILES) && !empty($_FILES)) {
			if (isset($_FILES["images"]) && !empty($_FILES["images"]['name'])) {
				if ($_FILES["images"]['error'] > 0) {
					return array(
						'status' => false,
						'img' => null,
						'msg' => 'image upload error'
					);
				}
				if (!in_array($_FILES["images"]['type'], array('image/png', 'image/jpg', 'image/jpeg'))) {
					return array(
						'status' => false,
						'img' => null,
						'msg' => "Image must be .png or .jpg"
					);
				}
				if ($_FILES["images"]['size'] > 15000) {
					return array(
						'status' => false,
						'img' => null,
						'msg' => "Image must be less than 15KB"
					);
				}
				$img = array(
					'path' => $_FILES["images"]['tmp_name'],
					'type' => $_FILES["images"]["type"],
				);
				return array(
					'status' => true,
					'img' => $img,
					'msg' => "Succss"
				);
			}
		}
	}

	protected function doPost($apiPath = '', $postData = array(), $_urlencode = false)
	{
		$sess = new Zend_Session_Namespace('Zend_Auth');
		$sessLang = new Zend_Session_Namespace('Zend_lang');

		$lang = 'en';
		if (!empty($sessLang) && isset($sessLang->lang)) {
			$lang = $sessLang->lang;
		}

		if (empty($sess->user)) {
			$postData = array_merge($postData, array(
				'language' => $lang
			));
		} else {
			$postData = array_merge($postData, array(
				// 'token' => isset($sess->user) ? $sess->user['token'] : "",
				'uid' => isset($sess->user) ? $sess->user['uid'] : "guest_1",
				'language' => $lang
			));
		}
		$ip = array(
			'HTTP_CLIENT_IP' => isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '',
			'HTTP_FORWARDED' => isset($_SERVER['HTTP_FORWARDED']) ? $_SERVER['HTTP_FORWARDED'] : '',
			'HTTP_X_FORWARDED' => isset($_SERVER['HTTP_X_FORWARDED']) ? $_SERVER['HTTP_X_FORWARDED'] : '',
			'HTTP_FORWARDED_FOR' => isset($_SERVER['HTTP_FORWARDED_FOR']) ? $_SERVER['HTTP_FORWARDED_FOR'] : '',
			'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
			'REMOTE_ADDR' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		);

		/**
		 * For datatable only
		 * 	Must prevent string like '&, =, ?'
		 */
		if ($_urlencode) {
			$postData = urldecode(http_build_query($postData));
		}
		// p($postData);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, STATIC_API . $apiPath);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'BasicAuthorization:' . ' Basic d2ViYmxvb21kYXlzQCRAKjBYeUpuNExZb28oJCYx',
			'mobileType: web',
			'ip-web:' . json_encode($ip)
		));

		$response = curl_exec($curl);
		// p($response);
		// For debug
		// if ($apiPath == '/user/update-avatar') {
		// 	p($response);
		// }

		if (empty($response) || preg_match("~An error occurred~i", $response) || preg_match("~Stack trace~i", $response) || preg_match("~Uncaught Error~i", $response) || preg_match("~Undefined index~i", $response)) {
			$response = "";
			$response = array(
				'status' => false,
				'code' => "",
				'msg' => 'Something went wrong, please try again later...',
			);
			$data = $response;
			return $data;
		}

		$data = json_decode($response, true);

		if (isset($data['msg']) && (preg_match("~SQLSTATE~i", $data['msg']))) {
			$response = "";
			$response = array(
				'status' => false,
				'code' => "",
				'msg' => 'Something went wrong on data, please try again later.',
			);
			$data = $response;
		}

		return $data;
	}

	protected function response($data = array())
	{
		$this->getResponse()->setBody(json_encode($data));
	}
}
