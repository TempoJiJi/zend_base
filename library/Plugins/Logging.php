<?php
// use function GuzzleHttp\json_encode;

class Plugins_Logging extends Zend_Controller_Plugin_Abstract
{
	// api - checking token,uid validity
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		// strip tags and normalize user params
		$params = $request->getParams();

		// Convert json to array
		if ($params['module'] == 'api') {
			$jsonArray = json_decode($request->getRawBody(), true);
			if (!empty($jsonArray)) {
				foreach ($jsonArray as $key => $value) {
					$params[$key] = $value;
				}
			}
		}

		if ($params['module'] == 'api') {
			foreach ((array) $params as $k => $v) {
				$k = strip_tags($k);
				if (!is_string($v) && !is_array($v)) {
					$v = '';
				}
				if ($k == 'uid') {
					$v = intval($v);
				}

				if (is_string($v)) {
					if ($k !== 'columns') {
						// other than ticket
						if (preg_match('~select|delete|truncate|JSON_KEYS|table_name|etc\|information_schema|schema_name|mysql|performance_schema|TABLE_SCHEMA~is', $v)) {
							$v = '';
						}
					}
					$params[$k] = strip_tags($v);
					$request->setParam($k, strip_tags($v));
				}
			}
		}

		// spam filternig using zend cache
		// $ip = array('127.0.0.1');
		// if (APPLICATION_ENV == 'production') {
		// 	if (!in_array($_SERVER['HTTP_CF_CONNECTING_IP'], $ip) || !in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $ip)) {
		// 		$this->spam_filter($params, 'HTTP_X_FORWARDED_FOR');
		// 		$this->spam_filter($params, 'REMOTE_ADDR');
		// 		$this->spam_filter($params, 'HTTP_FORWARDED_FOR');
		// 		$this->spam_filter($params, 'HTTP_X_FORWARDED');
		// 		$this->spam_filter($params, 'HTTP_FORWARDED');
		// 		$this->spam_filter($params, 'HTTP_CLIENT_IP');
		// 	}
		// }

		if ($params['module'] == 'api') {
			$currentPath = $params['controller'] . '!' . $params['action'];

			// list no need token,uid
			$excludeCheckToken = array(
				'user!register', 'user!login', 'general!version-control', 'payment!payment-confirmation-yoyo', 'tester!foo', 'tester!testing',
				'product!get-product-list', 'product!get-product-details', 'order!cart',
				'user!get-draw-details'
			);

			// if (!in_array($currentPath, $excludeCheckToken)) {
			// 	if (!isset($params['token']) || empty($params['token'])) {
			// 		echo json_encode(array(
			// 			'status' => false,
			// 			'data' => null,
			// 			'msg' => 'missing compulsary parameter (token)',
			// 		));
			// 		exit;
			// 	} else if (!isset($params['uid']) || empty($params['uid'])) {
			// 		echo json_encode(array(
			// 			'status' => false,
			// 			'data' => null,
			// 			'msg' => 'missing compulsary parameter (uid)',
			// 		));
			// 		exit;
			// 	}

			// 	// check user token
			// 	$dataToken = $this->validateToken($params['uid'], $params['token']);
			// 	if (!$dataToken['status']) {
			// 		$db = Zend_Db_Table::getDefaultAdapter();
			// 		$sql = 'insert into ag_api_log (uid, apiname, user_input, server_output) values(?,?,?,?)';
			// 		$db->query($sql, array($params['uid'], $currentPath, (string) $request->getHeader('Authorization'), 'Token expired'));
			// 		echo json_encode(array(
			// 			'status' => false,
			// 			'data' => null,
			// 			'code' => 100002,
			// 			'msg' => 'Token expired',
			// 		));
			// 		exit;
			// 	}
			// }
		} else if ($params['module'] == 'admin') {
			$sess = new Zend_Session_Namespace('Zend_Auth');
			$model = new Model_Index();
			$currentPath = $params['controller'] . '!' . $params['action'];

			if (isset($sess->user) && !empty($sess->user) && ($currentPath != 'index!index') && ($currentPath != 'auth!login') && ($currentPath != 'auth!logout')) {
				$request->setParam('aid', $sess->user['id']);
			}

			// acl
			$aclExclude = array('sadmin!sadmin-get-acl');
			if (!in_array($params['controller'], array('index', 'auth')) && !in_array($currentPath, $aclExclude)) {
				$sadmin_model = new Model_Sadmin();
				$aclFlag = $sadmin_model->sadminGetAcl(array(
					'c_controller' => $params['controller'],
					'aid' => $sess->user['id']
				));
				if (empty($aclFlag) || !isset($aclFlag['status']) || !$aclFlag['status'] || empty($aclFlag['data'])) {
					$this->getResponse()->setRedirect('/')->sendResponse();				
					echo json_encode(array(
						'status' => false,
						'code' => ACL_NO_ACCESS,
						'acl' => "You don't have access, please login again",
					));
					// $sess->unsetAll();
					exit;
				}	
			}
		}
	}

	// save log input output
	public function postDispatch(Zend_Controller_Request_Abstract $request)
	{
		$params = $request->getParams();

		$created = $params['created'];
		$mobileType = $params['mobileType'];
		unset($params['created']);
		unset($params['mobileType']);
		$params['Bearer'] = (string) $request->getHeader('Authorization');
		$jsonParams = json_encode($params);
		$currentPath = $params['controller'] . '!' . $params['action'];
		$body = $this->getResponse()->getBody();

		if ($params['module'] == 'api') {
			if ($this->is_valid_json($body)) {
				$db = Zend_Db_Table::getDefaultAdapter();

				if ($mobileType == 'web') {
					$ipWeb = $request->getHeader('ip-web');
				}

				$ip = array(
					'HTTP_CLIENT_IP' => isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '',
					'HTTP_FORWARDED' => isset($_SERVER['HTTP_FORWARDED']) ? $_SERVER['HTTP_FORWARDED'] : '',
					'HTTP_X_FORWARDED' => isset($_SERVER['HTTP_X_FORWARDED']) ? $_SERVER['HTTP_X_FORWARDED'] : '',
					'HTTP_FORWARDED_FOR' => isset($_SERVER['HTTP_FORWARDED_FOR']) ? $_SERVER['HTTP_FORWARDED_FOR'] : '',
					'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
					'REMOTE_ADDR' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				);

				$uid = 0;
				$response = json_decode($body, true);
				if (isset($params['uid'])) {
					$uid = $params['uid'];
				}
				if (isset($response['data']['uid'])) {
					$uid = $response['data']['uid'];
				}

				$sql = 'insert into ag_api_log (uid, ip, auth_key, apiname, user_input, server_output, created_at) values(?,?,?,?,?,?,?)';
				$db->query($sql, array($uid, ($mobileType == 'web' && $ipWeb != '' ? $ipWeb : json_encode($ip)), 'api', $currentPath, $jsonParams, $body, $created));

				// Modify sql error msg
				if (APPLICATION_ENV != 'development') {
					$translate = Zend_Registry::get('translate');
					if (!$response['status'] && !in_array($response['code'], CODE_DISPLAY_TO_USER)) {
						$response['msg'] = $translate['something_went_wrong'];
						$response['code'] = ERROR_CODE;
					}
					if (strpos($response['msg'], 'SQLSTATE')) {
						$response['msg'] = $translate['something_went_wrong'];
						$response['code'] = ERROR_CODE;
					}
				}

				// unset($response["code"]);
				$this->getResponse()->setBody(json_encode($response));
			}
		} else if ($params['module'] == 'admin') {
			$sess = new Zend_Session_Namespace('Zend_Auth');
			if ($this->is_valid_json($body)) {
				$db = Zend_Db_Table::getDefaultAdapter();
				$ip = array(
					'HTTP_CLIENT_IP' => isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '',
					'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
					'REMOTE_ADDR' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				);
				$uid = $sess->user['id'];
				$server_output = $body;

				// Log
				$sql = 'insert into ag_api_log (uid, ip, auth_key, apiname, user_input, server_output, created_at) values(?,?,?,?,?,?,?)';
				$db->query($sql, array($uid, json_encode($ip), 'admin', $currentPath, $jsonParams, $server_output, $created));	

				$response = json_decode($body, true);
				if (APPLICATION_ENV != 'development') {
					$translate = Zend_Registry::get('translate');
					if (!$response['status'] && !in_array($response['code'], CODE_DISPLAY_TO_USER)) {
						$response['msg'] = $translate['something_went_wrong'];
						$response['code'] = ERROR_CODE;
					}
					if (strpos($response['msg'], 'SQLSTATE')) {
						$response['msg'] = $translate['something_went_wrong'];
						$response['code'] = ERROR_CODE;
					}
				}

				// $sess->setExpirationSeconds(1800);	// renew session expire second
				$this->getResponse()->setBody(json_encode($response));
			}
		}
	}

	protected function is_valid_json($raw_json)
	{
		return (json_decode($raw_json, true) == NULL) ? false : true;
	}

	protected function validateToken($uid, $token)
	{
		$modelToken = new Model_Common();
		$res = $modelToken->checkTokenValidity(array(
			'uid' => $uid,
			'token' => $token,
		));
		return $res;
	}

	protected function getBearerToken($authorization)
	{
		if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
			return $matches[1];
		}
		return null;
	}

	protected function spam_filter($params = array(), $ipCase = '')
	{
		$params['uid'] = intval(isset($params['uid']) ? $params['uid'] : 0);
		$bannedFile = ZEND_TMP_PATH . 'bannedList';
		$bannedFileDetail = ZEND_TMP_PATH . 'bannedListDetail';
		// create banned file if not exists
		if (!file_exists($bannedFile)) {
			file_put_contents($bannedFile, '');
		}
		if (!file_exists($bannedFileDetail)) {
			file_put_contents($bannedFileDetail, '');
		}
		// check if ip in banned list
		$bannedData = explode(',', file_get_contents($bannedFile));
		if (isset($_SERVER[$ipCase]) && in_array($_SERVER[$ipCase] . '!' . $params['uid'], $bannedData)) {
			die ('Access denied! IP Blocked!');
		}

		if (isset($_SERVER[$ipCase]) && !empty($_SERVER[$ipCase])) {
			$limitTrial = 20;
			$ip = str_replace('.', '_', $_SERVER[$ipCase]);
			$cache = Zend_Cache::factory('core', 'File', array('lifetime' => APPLICATION_ENV != 'production' ? 1 : 1, 'automatic_serialization' => true), array('cache_dir' => ZEND_TMP_PATH));
			Zend_Registry::set('cache', $cache);

			$cacheName = implode('_', array('counter', $params['module'], str_replace('-', '', $params['controller']), str_replace('-', '', $params['action']), str_replace('.', '', str_replace(':', '', $ip)), $params['uid']));
			$cache = Zend_Registry::get('cache');
			$caching = $cache->load($cacheName);
			if ($caching) {
				$counter = $caching['count'] + 1;
				if ($counter >= $limitTrial) {
					file_put_contents($bannedFile, $_SERVER[$ipCase] . '!' . $params['uid'] . ',', FILE_APPEND);
					file_put_contents($bannedFileDetail, json_encode(array(
						'ip' => $_SERVER[$ipCase],
						'ipCase' => $ipCase,
						'counter' => $counter,
						'datetime' => date('Y-m-d H:i:s'),
						'params' => $params,
					)) . "\n", FILE_APPEND);
					die ('Access denied! IP Blocked');
				}
				$cache->save(array('count' => $counter), $cacheName);
			} else {
				$cache->save(array('count' => 1), $cacheName);
			}
		}
	}
}
