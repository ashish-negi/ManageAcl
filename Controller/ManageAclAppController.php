<?php
class ManageAclAppController extends AppController {

        public $components = array('Auth');
	/**
	 * beforeFitler
	 */
	public function beforeFilter() {
            parent::beforeFilter();
            $this->Auth->allow();
            
            /**
             * Force prefix
             */
            $prefix = Configure::read('ManageAcl.prefix');
            $routePrefix = isset($this->request->params['prefix']) ? $this->request->params['prefix'] : false;
            if ($prefix && $prefix != $routePrefix) {
                $this->redirect($this->request->referer());
            } 
            elseif ($prefix) {
                $this->request->params['action'] = str_replace($prefix . "_", "", $this->request->params['action']);
                $this->view = str_replace($prefix . "_", "", $this->view);
            }
        }
}

