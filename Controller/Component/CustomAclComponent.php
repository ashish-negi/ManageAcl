<?php
App::uses('Component', 'Controller');

Class CustomAclComponent extends Component {

    public function initialize(Controller $controller) {
        $this->controller = $controller;
    }
    
    /**
     * @description common function to generate/update Aros
     * @param type $command by default create, other option update
     * @param type $truncate by default false, flag to truncate Aro table then make entry
     * @param array $data array which contains ids to update, model name, datasource.  Ex. array('datasource' => 'default', 'model' => 'User', 'ids' => array('user_id' => 'group_id'))
     * @return boolean
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    public function generateAros($command = 'create', $truncate = false, array $data = array()){
        App::uses('Aro', 'ManageAcl.Model');
        $aroObject = new Aro();
        $this->__setDatasource($aroObject, $data);
        $truncate ? $aroObject->query('TRUNCATE TABLE aros;') : '';
        if ($command == 'create') {
            App::uses('Group', 'Model');
            $groupObject = new Group();
            $this->__setDatasource($groupObject, $data);
            App::uses('User', 'Model');
            $userObject = new User();
            $this->__setDatasource($userObject, $data);
            $groupIds = $groupObject->find('list', array('order' => 'id ASC'));
            $userIds = $userObject->find('list', array('fields' => array('id', 'group_id'), 'order' => 'id ASC'));
            $this->__saveAros($groupIds, $aroObject, 'Group');
            $this->__saveAros($userIds, $aroObject, 'User');
        } elseif ($command == 'update') {
            $this->__saveAros($data['ids'], $aroObject, $data['model']);
        }
        return true;
    }
    
    /**
     * @description this function save Aros
     * @param array $dataArray User / Group Model ids
     * @param Object $aroModelObj
     * @param type $modelName
     * @return type
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __saveAros(Array $dataArray, Object $aroModelObj, $modelName){
        $groupData = array();
        foreach ($dataArray as $foreignKey => $val) {
            $aroData = $this->__checkExistingData($aroModelObj, $modelName, $foreignKey, $val);
            $groupData[$foreignKey]['id'] = $aroData['id']; 
            $groupData[$foreignKey]['parent_id'] = $aroData['parent_id']; 
            $groupData[$foreignKey]['model'] = $modelName; 
            $groupData[$foreignKey]['foreign_key'] = $foreignKey; 
            $groupData[$foreignKey]['alias'] = NULL; 
        }
        return $aroModelObj->saveAll($groupData);
    }
    
    /**
     * @description this function checks data exists in Aro table or not and returns Aro table id and parent_id
     * @param Object $aroModelObj
     * @param type $modelName User / Group
     * @param type $foreignKey foreign key for aro table(group/user id)
     * @param type $groupId group id for User model
     * @return type
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __checkExistingData(Object $aroModelObj, $modelName, $foreignKey, $groupId){
        $resultArray = array('id' => NULL, 'parent_id' => NULL);
        $conditions = array('Aro.foreign_key' => $foreignKey, 'Aro.model' => $modelName);
        if ($modelName == 'User') {
            $conditions = array_merge($conditions, array('Aro.parent_id IS NOT NULL'));
            $parentConditions = array('Aro.foreign_key' => $groupId, 'Aro.parent_id IS NULL', 'Aro.model' => 'Group');
            $aroParentData = $aroModelObj->find('first', array('conditions' => $parentConditions));
            $resultArray['parent_id'] = !empty($aroParentData) ? $aroParentData['Aro']['id'] : $resultArray['parent_id'];
        } elseif ($modelName == 'group') {
            $conditions = array_merge($conditions, array('Aro.parent_id IS NULL'));
        }
        $aroData = $aroModelObj->find('first', array('conditions' => $conditions));
        if (!empty($aroData)) {
            $resultArray['id'] = $aroData['Aro']['id'];
        }
        return $resultArray;
    }
    
    /**
     * @description this function changes datasource of given model
     * @param Object $modelObj
     * @param type $data array contains datasource
     * @return boolean
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __setDatasource(Object $modelObj, $data = array()){
        if (!empty($data['datasource'])) {
            $modelObj->setDataSource($data['datasource']);
        }
        return true;
    }
    
    /**
     * @description this function generates acos
     * @return boolean
     * @param array $data array which contains datasource.  Ex. array('datasource' => 'default')
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    public function generateAcos(array $data = array()){
        $controllerViewArray = $this->__getControllerViewList();
        App::uses('Aco', 'ManageAcl.Model');
        $acoObject = new Aco();
        $this->__setDatasource($acoObject, $data);
        // save or update controller array
        $this->__recursiveFunctionToGenerateAcos($acoObject, $controllerViewArray, 'controllers', 1);
        return true;
    }
    
    /**
     * @description recursive function to generate acos
     * @param type $acoObject Aco model object
     * @param type $dataArray Controller / view name array
     * @param type $type action / controller
     * @param type $parentNode 1 if controller othervise parent controller name
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 26-12-2014
     */
    private function __recursiveFunctionToGenerateAcos($acoObject, $dataArray, $type, $parentNode){
        if (!empty($dataArray)) {
            foreach ($dataArray as $key => $value) {
                if ($parentNode == 1) { // for controller
                    $this->__saveAcos($acoObject, $type, $key, 'controllers');
                    $this->__recursiveFunctionToGenerateAcos($acoObject, $value, 'action', $key);
                } else { // for view
                    $this->__saveAcos($acoObject, $type, $value, $parentNode);
                }
            }
        }
    }
    
    /**
     * @description this function generates acos
     * @return $controllers array of controller and their views
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __getControllerViewList() {

        $controllerClasses = App::objects('controller');
        foreach ($controllerClasses as $controller) {
            if ($controller != 'AppController') {
                // Load the controller
                App::import('Controller', str_replace('Controller', '', $controller));
                // Load its methods / actions
                $controllerAllMethods = get_class_methods($controller);
                $controllerMethods = $this->__removePrivateMethods($controllerAllMethods);
                // Load the ApplicationController (if there is one)
                App::import('Controller', 'AppController');
                $controller = trim(str_replace('Controller', '', $controller));
                $controllers['controllers'] = NULL;
                $parentActions = get_class_methods('AppController');
                $controllers[$controller] = array_diff($controllerMethods, $parentActions);
            }
        }
        return $controllers;
    }
    
     /**
     * @description this function removes private methods from method list
     * @param $controllerAllMethods controller methords array
     * @return $controllerAllMethods array of controller views
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __removePrivateMethods($controllerAllMethods) {
        foreach ($controllerAllMethods as $idx => $method) {
            if ($method{0} == '_') {
                unset($controllerAllMethods[$idx]);
            }
        }
        return $controllerAllMethods;
    }
    
    /**
     * @description function  for saving aco into database
     * @param Object $acoModelObj aco model object
     * @param type $type controller / action
     * @param type $alias aco alias name
     * @param type $parentNode parent controller name
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 25-12-2014
     */
    private function __saveAcos(Object $acoModelObj, $type, $alias, $parentNode = NULL){
        $data['Aco'] = array('id' => NULL, 'parent_id' => NULL);
        $conditions = array('Aco.alias' => $alias);
        if ($alias != 'controllers' || $type != 'controllers') {
            $checkParentNode = $acoModelObj->find('first', array('conditions' => array('Aco.alias' => $parentNode)));
            if (!empty($checkParentNode)) {
                $data['Aco']['parent_id'] = $checkParentNode['Aco']['id'];
                $conditions = array_merge($conditions, array('Aco.parent_id' => $checkParentNode['Aco']['id']));
            }
        }
        $existAco = $acoModelObj->find('first', array('conditions' => $conditions));
        if (empty($existAco)) {
            $data['Aco']['alias'] = $alias;
            $data['Aco']['model'] = NULL;
            $data['Aco']['foreign_key'] = 0;
            $acoModelObj->create();
            $acoModelObj->save($data);
        }
    }
    
    /**
     * @description This function generate aro aco list and save it in to table
     * @param array $data datasource array
     * @return boolean
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 26-12-2014
     */
    public function generateArosAcos(array $data = array()){
        App::uses('ArosAco', 'ManageAcl.Model');
        $aroAcoObject = new ArosAco();
        $this->__setDatasource($aroAcoObject, $data);
        $aroData = $this->__getAcoAroList('Aro', $data);
        $acoData = $this->__getAcoAroList('Aco', $data);
        $saveData = $this->__generateAroAcoData($aroAcoObject, $aroData, $acoData);
        $aroAcoObject->saveAll($saveData);
        return true;
    }
    
    /**
     * @description Function returns aro/aco ids
     * @param type $modelName model for which list is needed
     * @param array $data datasource array
     * @return type array of aco/aro ids
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 26-12-2014
     */
    private function __getAcoAroList($modelName, array $data){
        App::uses($modelName, 'ManageAcl.Model');
        $modelObj = new $modelName();
        $this->__setDatasource($modelObj, $data);
        $conditions = array();
        if (!empty($data['ids']) && $modelName == 'Aro') {
            $foreignKey = key($data['ids']);
            $conditions = array_merge($conditions, array("$modelName.foreign_key" => $foreignKey, "$modelName.parent_id NOT" => NULL));
        }
        $modelData = $modelObj->find('list', array('conditions' => $conditions));
        unset($modelObj);
        return $modelData;
    }
    
    /**
     * @description return formatted aroaco data to save
     * @param Object $aroAcoObject
     * @param type $aroData
     * @param type $acoData
     * @return array
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 26-12-2014
     */
    private function __generateAroAcoData(Object $aroAcoObject, $aroData, $acoData){
        $i = 0;
        $saveData = array();
        $permissionArray = Configure::read('ManageAcl.actionPermissions');
        if(empty($aroData) || empty($acoData)) {
            return $saveData;
        }
        foreach($aroData as $aroId){
            foreach($acoData as $acoId){
                $saveData[$i]['ArosAco'] = $permissionArray;
                $saveData[$i]['ArosAco']['aro_id'] = $aroId;
                $saveData[$i]['ArosAco']['aco_id'] = $acoId;
                $checkExixtingAroAco = $aroAcoObject->find('first', array('conditions' => array('ArosAco.aro_id' => $aroId, 'ArosAco.aco_id' => $acoId)));
                if(!empty($checkExixtingAroAco)){
                    $saveData[$i]['ArosAco']['id'] = $checkExixtingAroAco['ArosAco']['id'];
                }
                $i++;        
            }
        }
        return $saveData;
    }
    
    /**
    * Pass-thru function for ACL check instance. Check methods
    * are used to check whether or not an ARO can access an ACO
    *
    * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
    * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
    * @param string $action Action (defaults to *)
    * @return bool Success
    */
    public function check($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'check');
    }

    /**
     * Pass-thru function for ACL allow instance. Allow methods
     * are used to grant an ARO access to an ACO.
     *
     * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function allow($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'allow');
    }

    /**
     * Pass-thru function for ACL deny instance. Deny methods
     * are used to remove permission from an ARO to access an ACO.
     *
     * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function deny($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'deny');
    }

    /**
     * Pass-thru function for ACL inherit instance. Inherit methods
     * modify the permission for an ARO to be that of its parent object.
     *
     * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function inherit($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'inherit');
    }

    /**
     * Pass-thru function for ACL grant instance. An alias for AclComponent::allow()
     *
     * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     * @deprecated 3.0.0 Will be removed in 3.0.
     */
    public function grant($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'allow');
    }

    /**
     * Pass-thru function for ACL grant instance. An alias for AclComponent::deny()
     *
     * @param array|string|Model $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param array|string|Model $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     * @deprecated 3.0.0 Will be removed in 3.0.
     */
    public function revoke($aro, $aco, $action = "*") {
        return $this->_checkAclAction($aro, $aco, $action, 'deny');
    }
    
    /**
     * @description return true / false according acl check
     * @param type $aro
     * @param type $aco
     * @param type $action
     * @param type $executeAction what action to call
     * @return type
     * @author Ashish Negi <ashish.negi@ucodesoft.com>
     * @created 30-12-2014
     */
    private function _checkAclAction($aro, $aco, $action, $executeAction){
        app::uses('ArosAco', 'ManageAcl.Model');
        $acosArosObj = new ArosAco();
        return $acosArosObj->$executeAction($aro, $aco, $action); 
    }
}
