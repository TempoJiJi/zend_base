<?php

class Plugins_DomainHandler extends Zend_Controller_Plugin_Abstract
{

	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{

		$protocol = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') || stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https' : 'http';
		if (APPLICATION_ENV != 'production') {
			//$allowedOrigin = array('localhost');
			$allowedOrigin = array();
		} else {
			//need to filled up this array with whitelist domain (for ajax call). otherwise just works fine using direct call / curl
			$allowedOrigin = array();
		}

		header("Access-Control-Allow-Origin: *");
		// if (isset($_SERVER['HTTP_ORIGIN']) && in_array(str_replace($protocol . "://", '', $_SERVER['HTTP_ORIGIN']), $allowedOrigin)) {
		// 	header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
		// }
		header("Access-Control-Allow-Headers: BasicAuthorization");
		header('Access-Control-Allow-Methods: POST');
		header('Access-Control-Max-Age: 0');
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');

		$sess = new Zend_Session_Namespace('Zend_Auth');
		$params = $request->getParams();

		new Zend_Loader_Autoloader_Resource(
			array(
				'basePath' => APPLICATION_PATH,
				'namespace' => '',
				'resourceTypes' => array(
					'model' => array(
						'path' => "modules/$params[module]/models/",
						'namespace' => 'Model_',
					),
				),
			)
		);

		// $request->setParam('version', (string)$request->getHeader('version'));
		$request->setParam('mobileType', (string)$request->getHeader('mobileType'));
		$request->setParam('created', date('Y-m-d H:i:s'));

		if ($params['module'] == 'admin') {
			// $this->require_auth();
		}

		if ($params['module'] == 'admin' && (!isset($sess->user) || empty($sess->user)) && $params['controller'] != 'auth') {
			header('Content-Type: text/html');
			$request->setControllerName('auth');
			$request->setActionName('index');
		} else if ($params['module'] == 'api') {

			$currentPath = $params['controller'] . '!' . $params['action'];

			header('Content-Type: application/json');
			$authorization = (string)$request->getHeader('BasicAuthorization');
			$authorized = array(
				'Basic d2ViYmxvb21kYXlzQCRAKjBYeUpuNExZb28oJCYx' => 'web', // weblavieflo@$@*0XyJn4LYoo($&1
				// 'Basic aW9zcGthQE1lRDBYeUpuNExZb29hTmE=' => 'ios', // iospka@MeD0XyJn4LYooaNa
			);

			if (!array_key_exists($authorization, $authorized) && $params['controller'] != 'payment') {
				echo json_encode(array('status' => false, 'msg' => 'Unauthorized Access!'));
				exit;
			}

			// Allow only post
			if (!$request->isPost() && $params['controller'] != 'payment') {
				echo json_encode(array('status' => false, 'msg' => 'Invalid Method!'));
				exit;
			}

			// Define authorized client
			if (!defined('AUTHORIZED_CLIENT')) {
				if ($params['controller'] == 'payment') {
					define('AUTHORIZED_CLIENT', 'callback!' . $params['action']);
				} else {
					define('AUTHORIZED_CLIENT', $authorized[$authorization]);
				}
			}
		}
	}

	protected function client_ip()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = false;
		}

		return $ipaddress;
	}

	protected function require_auth()
	{
		$AUTH_USER = 'Admin';
		$AUTH_PASS = 'qweqwe11';

		//header('Cache-Control: no-cache, must-revalidate, max-age=0');
		$has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
		$is_not_authenticated = (!$has_supplied_credentials ||
			$_SERVER['PHP_AUTH_USER'] != $AUTH_USER ||
			$_SERVER['PHP_AUTH_PW'] != $AUTH_PASS);
		if ($is_not_authenticated) {
			header('HTTP/1.1 401 Authorization Required');
			header('WWW-Authenticate: Basic realm="Access denied"');
			exit;
		}
	}
}
