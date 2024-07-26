<?php

class Admin_AuthController extends Zend_Controller_Action {

    public function init() {
        $this->view->sess = new Zend_Session_Namespace('Zend_Auth');
    }

    public function indexAction() {
    }

    public function loginAction() {
        if ($this->getRequest()->isPost() || $this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getParams();
            try {
                if (strpos($params['email'], 'script') !== false) {
                    throw new Exception("Login failed!!!");
                }
                
                if (strpos($params['email'], 'sql') !== false) {
                    throw new Exception("Login failed!!!");
                }
                
                if (strpos($params['password'], 'script') !== false) {
                    throw new Exception("Login failed!!!");
                }
                
                if (strpos($params['password'], 'sql') !== false) {
                    throw new Exception("Login failed!!!");
                }
                
                if (!isset($params['email']) || empty($params['email'])) {
                    throw new Exception("email are required");
                }
                if (!isset($params['password']) || empty($params['password'])) {
                    throw new Exception("password are required");
                }
                $model = new Model_Auth();
                $data = $model->login($params);
                
                if (!$data['status']) {
                    throw new Exception($data['msg']);
                }  

                $this->_helper->json(array(
                    'status' => true,
                    'msg' => "Login success",
                    'data' => $data['data']
                ));

            } catch (Exception $e) {
                $this->_helper->json(array(
                    'status' => false,
                    'data' => null,
                    'msg' => $e->getMessage()
                ));
            }
            exit;
        }
    }

    public function logoutAction() {
        unset($this->view->sess->user);
        unset($this->view->sess->user_type);
        unset($this->view->sess->login);
        $this->redirect("/");
    }

}