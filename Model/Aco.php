<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Aro
 *
 * @author ucodesoft
 */
App::uses('ManageAclAppModel', 'ManageAcl.Model');
class Aco extends ManageAclAppModel {
    
    var $name = 'Aco';
    public $cacheQueries = false;
    public $actsAs = array('Tree' => array('type' => 'nested'));
    public $recursive = -1;
    public $hasAndBelongsToMany = array(
        'Aro' =>
            array(
                'className' => 'Aro',
                'joinTable' => 'aros_acos',
                'foreignKey' => 'aco_id',
                'associationForeignKey' => 'aro_id',
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
