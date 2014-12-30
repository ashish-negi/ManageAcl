<?php
/**
 * Aro Model class for custom ACL implementation
 *
 * @author Ashish Negi <ashish.negi@ucodesoft.com>
 * @created 25-12-2014
 */
App::uses('ManageAclAppModel', 'ManageAcl.Model');
class Aro extends ManageAclAppModel {
    
    var $name = 'Aro';
    public $cacheQueries = false;
    public $actsAs = array('Tree' => array('type' => 'nested'));
    public $recursive = -1;
    public $hasAndBelongsToMany = array(
        'Aco' =>
            array(
                'className' => 'Aco',
                'joinTable' => 'aros_acos',
                'foreignKey' => 'aro_id',
                'associationForeignKey' => 'aco_id',
                'unique' => true,
                'conditions' => '',
                'fields' => '',
                'order' => '',
                'limit' => '',
                'offset' => '',
                'finderQuery' => '',
            )
    );
}
