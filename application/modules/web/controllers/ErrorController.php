<?php

class Web_ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page!';
            if (APPLICATION_ENV == 'production') {
                p($this->view->message);
            }
            return;
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // check category for meta link
                // $uri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
                // $uri = str_replace("/", "", $uri);
                // $db = Zend_Db_Table::getDefaultAdapter();
                // $category = $db->query("select id from ag_product_category_table where meta_link=? and status=1 limit 1", array($uri))->fetch();
                // if (!empty($category) && isset($category['id']) && $category['id'] > 0) {

                //     // redirect to relateed category
                //     $this->redirect("/category?categoryId=$category[id]");

                // } else {

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->redirect("/");
                die("Invalid api routes!");

                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                // }
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }

        $this->view->request = $errors->request;
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }
}
