<?php
class AclController extends ManageAclAppController {

    public $paginate = array();
    protected $acos = array();
    public $components = array('ManageAcl.CustomAcl');
    public $uses = array('ManageAcl.Aco', 'ManageAcl.Aro', 'ManageAcl.ArosAco', 'User', 'Group');
    
    /**
    * beforeFitler
    */
   public function beforeFilter() {
           parent::beforeFilter();
           $this->layout = 'ManageAcl.default';
           /**
            * Pagination
            */
           $aros = Configure::read('ManageAcl.aros');
           foreach ($aros as $aro) {
                   $limit = Configure::read("ManageAcl.{$aro}.limit");
                   $limit = empty($limit) ? 4 : $limit;
                   $this->paginate[$this->{$aro}->alias] = array(
                           'recursive' => -1,
                           'limit' => $limit
                   );
           }
   }

    /**
     * Delete everything
     */
    public function drop() {
        $this->Aco->deleteAll(array("1 = 1"));
        $this->Aro->deleteAll(array("1 = 1"));
        $this->Session->setFlash(__("Both ACOs and AROs have been dropped"));
        $this->redirect(array("action" => "index"));
    }

    /**
     * Delete all permissions
     */
    public function drop_perms() {
        if ($this->ArosAco->deleteAll(array("1 = 1"))) {
                $this->Session->setFlash(__("Permissions dropped"));
        } else {
                $this->Session->setFlash(__("Error while trying to drop permissions"));
        }
        $this->redirect(array("action" => "index"));
    }

    /**
     * Update ACOs
     * Sets the missing actions in the database
     */
    public function update_acos() {
        $this->CustomAcl->generateAcos();
        $this->Session->setFlash(sprintf(__("ACOs have been created/updated"), $count));
        $this->redirect($this->request->referer());
    }

    /**
     * Update AROs
     * Sets the missing AROs in the database
     */
    public function update_aros() {
        $this->CustomAcl->generateAros();
        $this->Session->setFlash(sprintf(__("AROs have been created/updated"), $count));
        $this->redirect($this->request->referer());
    }

    /**
     * generate permissions
     * Sets the missing actions in the database
     */
    public function update_permissions() {
        $this->CustomAcl->generateArosAcos();
        $this->Session->setFlash(sprintf(__("Permissions have been created/updated"), $count));
        $this->redirect($this->request->referer());
    }

    /**
     * Index action
     */
    public function index() {
        $this->set('titleForLayout', 'ICARUS | Manage Permissions');
    }

    /**
     * Manage Permissions
     */
    public function permissions() {
        $this->set('titleForLayout', 'ICARUS | Manage Permissions');
        // Saving permissions
        if ($this->request->is('post') || $this->request->is('put')) {
                $perms =  isset($this->request->data['Perms']) ? $this->request->data['Perms'] : array();
                foreach ($perms as $aco => $aros) {
                        $action = str_replace(":", "/", $aco);
                        foreach ($aros as $node => $perm) {
                            list($model, $id) = explode(':', $node);
                            $node = array('model' => $model, 'foreign_key' => $id);
                            if ($perm == 'allow') {
                                    $this->CustomAcl->allow($node, $action);
                            }
                            elseif ($perm == 'inherit') {
                                    $this->CustomAcl->inherit($node, $action);
                            }
                            elseif ($perm == 'deny') {
                                    $this->CustomAcl->deny($node, $action);
                            }
                        }
                } 
        }

        $model = isset($this->request->params['named']['aro']) ? $this->request->params['named']['aro'] : null;
        if (!$model || !in_array($model, Configure::read('ManageAcl.aros'))) {
                $model = Configure::read('ManageAcl.aros');
                $model = $model[0];
        }

        $Aro = $this->{$model};
        $aros = $this->paginate($Aro->alias);
        $permKeys = $this->_getKeys();

        /**
         * Build permissions info
         */
        $this->acos = $acos = $this->Aco->find('all', array('order' => 'Aco.lft ASC', 'recursive' => 1));
        $perms = array();
        $parents = array();
        foreach ($acos as $key => $data) {
                $aco =& $acos[$key];
                $aco = array('Aco' => $data['Aco'], 'Aro' => $data['Aro'], 'Action' => array());
                $id = $aco['Aco']['id'];

                // Generate path
                if ($aco['Aco']['parent_id'] && isset($parents[$aco['Aco']['parent_id']])) {
                        $parents[$id] = $parents[$aco['Aco']['parent_id']] . '/' . $aco['Aco']['alias'];
                } else {
                        $parents[$id] = $aco['Aco']['alias'];
                }
                $aco['Action'] = $parents[$id];

                // Fetching permissions per ARO
                $acoNode = $aco['Action'];
                foreach($aros as $aro) {
                        $aroId = $aro[$Aro->alias][$Aro->primaryKey];
                        $evaluate = $this->_evaluate_permissions($permKeys, array('id' => $aroId, 'alias' => $Aro->alias), $aco, $key);

                        $perms[str_replace('/', ':', $acoNode)][$Aro->alias . ":" . $aroId . '-inherit'] = $evaluate['inherited'];
                        $perms[str_replace('/', ':', $acoNode)][$Aro->alias . ":" . $aroId] = $evaluate['allowed'];
                }
        }

        $this->request->data = array('Perms' => $perms);
        $this->set('aroAlias', $Aro->alias);
        $aroDisplayField = Configure::read('ManageAcl.displayField');
        $this->set('aroDisplayField', $aroDisplayField[$model]);
        $this->set(compact('acos', 'aros'));
    }
	
    /**
     * Recursive function to find permissions avoiding slow $this->Acl->check().
     */
    private function _evaluate_permissions($permKeys, $aro, $aco, $aco_index) {
        $permissions = Set::extract("/Aro[model={$aro['alias']}][foreign_key={$aro['id']}]/ArosAco/.", $aco);
        $permissions = array_shift($permissions);		
        $allowed = false;
        $inherited = false;
        $inheritedPerms = array();
        $allowedPerms = array();

        /**
         * Manually checking permission
         * Part of this logic comes from DbAcl::check()
         */
        foreach ($permKeys as $key) {
                if (!empty($permissions)) {
                        if ($permissions[$key] == -1) {
                                $allowed = false;
                                break;
                        } elseif ($permissions[$key] == 1) {
                                $allowedPerms[$key] = 1;
                        } elseif ($permissions[$key] == 0) {
                                $inheritedPerms[$key] = 0;
                        }
                } else {
                        $inheritedPerms[$key] = 0;
                }
        }

        if (count($allowedPerms) === count($permKeys)) {
                $allowed = true;
        } elseif (count($inheritedPerms) === count($permKeys)) {
                if ($aco['Aco']['parent_id'] == null) {
                        $this->lookup +=1;
                        $acoNode = (isset($aco['Action'])) ? $aco['Action'] : null;
                        $aroNode = array('model' => $aro['alias'], 'foreign_key' => $aro['id']);
                        $allowed = $this->CustomAcl->check($aroNode, $acoNode);
                        //$allowed = true;
                        $this->acos[$aco_index]['evaluated'][$aro['id']] = array(
                                'allowed' => $allowed,
                                'inherited' => true
                        );
                }
                else {
                        /**
                         * Do not use Set::extract here. First of all it is terribly slow, 
                         * besides this we need the aco array index ($key) to cache are result.
                         */
                        foreach ($this->acos as $key => $a) {
                                if ($a['Aco']['id'] == $aco['Aco']['parent_id']) {
                                        $parent_aco = $a;
                                        break;
                                }
                        }
                        // Return cached result if present
                        if (isset($parent_aco['evaluated'][$aro['id']])) {
                                return $parent_aco['evaluated'][$aro['id']];
                        }

                        // Perform lookup of parent aco
                        $evaluate = $this->_evaluate_permissions($permKeys, $aro, $parent_aco, $key);

                        // Store result in acos array so we need less recursion for the next lookup
                        $this->acos[$key]['evaluated'][$aro['id']] = $evaluate;
                        $this->acos[$key]['evaluated'][$aro['id']]['inherited'] = true;

                        $allowed = $evaluate['allowed'];
                }
                $inherited = true;
        }

        return array(
                'allowed' => $allowed,
                'inherited' => $inherited,
        );
    }

    /**
     * Returns permissions keys in Permission schema
     * @see DbAcl::_getKeys()
     */
    protected function _getKeys() {
        $keys = $this->ArosAco->schema();
        $newKeys = array();
        $keys = array_keys($keys);
        foreach ($keys as $key) {
                if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
                        $newKeys[] = $key;
                }
        }
        return $newKeys;
    }
}
