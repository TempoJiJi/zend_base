<?php

class Model_Users extends Zend_Db_Table_Abstract
{
	public $_name = 'ag_event_users';

	// NOTE: call this function inside try catch
	public function getEventDetails($params = array())
	{
		$db = $this->getAdapter();
		$eventData = $db->query('select id, min_num, max_num, ticket_qty from ag_event_table where (now() between start_date and end_date) and status=1 limit 1')->fetch();
		if (empty($eventData) || !isset($eventData['id'])) {
			throw new Exception('Event not found', SUC_CODE);
		}

		return $eventData;
	} 

	public function login($params = array())
	{
		try {
			$db = $this->getAdapter();

			$eventData = $this->getEventDetails();

			// user checking
			$data = $db->query('select id as uid from ag_users_table where entry_code=? and status=1 limit 1', array($params['entry_code']))->fetch();
			if (empty($data) || !isset($data['uid'])) {
				throw new Exception('Entry Code not found', SUC_CODE);
			}

			// check is user ady draw for this event
			$isDraw = $db->query('select is_draw from ag_event_users where uid=? and event_id=? and status=1 limit 1', array($data['uid'], $eventData['id']))->fetch();
			$data['is_draw'] = $isDraw['is_draw'] ?? 0;

			// generate token
			$token = md5(time() . $data['uid']);
			$data['token'] = $token;
			$db->insert('ag_users_token', array(
				'uid' => $data['uid'],
				'token' => $token,
				'expired_at' => date('Y-m-d H:i:s', strtotime("+1 HOURS"))
			));

			return array(
				'status' => true,
				'data' => $data,
				'msg' => 'Success',
				'code' => SUC_CODE
			);
		} catch (Exception $e) {
			return array(
				'status' => false,
				'data' => null,
				'msg' => $e->getMessage(),
				'code' => $e->getCode()
			);
		}
	}

	public function getDrawDetails($params = array())
	{
		try {
			$db = $this->getAdapter();
			
			$data = $this->getEventDetails();

			$userData =  $db->query('select is_draw from ag_event_users where uid=? and event_id=? and status=1', array($params['uid'], $data['id']))->fetch();
			$data['is_draw'] = $userData['is_draw'] ?? 0;

			$ticketData =  $db->query('select number from ag_event_ticket where uid=? and event_id=? and status=1', array($params['uid'], $data['id']))->fetchAll();
			$data['ticket_list'] = [];
			foreach($ticketData as $d) {
				$data['ticket_list'][] = $d['number'];
			}
			
			return array(
				'status' => true,
				'data' => $data,
				'msg' => 'Success',
				'code' => SUC_CODE
			);
		} catch (Exception $e) {
			return array(
				'status' => false,
				'data' => null,
				'msg' => $e->getMessage(),
				'code' => $e->getCode()
			);
		}
	}

	public function draw($params = array())
	{
		try {
			$db = $this->getAdapter();
			$db->beginTransaction();

			// get event
			$eventData = $db->query('select id, min_num, max_num, ticket_qty from ag_event_table where (now() between start_date and end_date) and status=1 limit 1 for update')->fetch();
			if (empty($eventData) || !isset($eventData['id'])) {
				throw new Exception('Event not found', SUC_CODE);
			}

			// check user validate
			$userData =  $db->query('select id from ag_users_table where id=? and status=1', array($params['uid']))->fetch();
			if (empty($userData)) {
				throw new Exception('Entry Code not found', SUC_CODE);
			}

			// check user total draw count
			$userDrawCount = $db->query('select count(1) as count from ag_event_ticket where status=1 and uid=? limit 1', array($params['uid']))->fetch();
			if (isset($userDrawCount['count']) && $userDrawCount['count'] >= $eventData['ticket_qty']) {
				throw new Exception('You have reached maxinum draw count', SUC_CODE);
			}

			// set is_draw if draw count hit limit
			$drawCount = $userDrawCount['count'] + 1;
			if ($drawCount >= $eventData['ticket_qty']) {
				$db->insert('ag_event_users', array(
					'event_id' => $eventData['id'],
					'uid' => $params['uid'],
					'is_draw' => 1
				));	
			}

			// get existing number
			$existNumber = [];
			$drawData = $db->query('select number from ag_event_ticket where event_id=? and status=1', array($eventData['id']))->fetchAll();
			foreach ($drawData as $d) {
				$existNumber[] = $d['number'];
			}

			// random number
			while (in_array(($n = mt_rand($eventData['min_num'], $eventData['max_num'])), $existNumber));
			$db->insert('ag_event_ticket', array(
				'event_id' => $eventData['id'],
				'uid' => $params['uid'],
				'number' => $n
			));

			$db->commit();
			return array(
				'status' => true,
				'data' => array(
					'number' => $n
				),
				'msg' => 'Success',
				'code' => SUC_CODE
			);
		} catch (Exception $e) {
			$db->rollBack();

			return array(
				'status' => false,
				'data' => null,
				'msg' => $e->getMessage(),
				'code' => $e->getCode()
			);
		}
	}

	public function checkDraw($params = array())
	{
		try {
			$db = $this->getAdapter();

			$ic = str_replace('-', '', $params['ic']);
			$ic = substr($ic, -4);

			$phone = $params['phone'];
			if (preg_match('/^01/', $phone)) {
				$phone = '6' . $phone;
			}

			$data = $db->query('select is_draw from users_prize where phone=? and ic_four_digit=? and status=1 limit 1', array($phone, $ic))->fetch();
			if (empty($data) || !isset($data['is_draw'])) {
				return array(
					'status' => true,
					'data' => null,
					'msg' => "Sorry, we couldn't find the phone number and IC number you provided. Please double-check your input and try again.",
					'code' => SUC_CODE
				);
			}

			return array(
				'status' => true,
				'data' => $data,
				'msg' => 'Success',
				'code' => SUC_CODE
			);
		} catch (Exception $e) {
			return array(
				'status' => false,
				'data' => null,
				'msg' => $e->getMessage(),
				'code' => $e->getCode()
			);
		}
	}
}
