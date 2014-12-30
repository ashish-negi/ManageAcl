<?php
/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       ManageAclAppModel
 */
App::uses('Model', 'Model');
App::uses('ConnectionManager', 'Model');
App::uses('AppModel', 'Model');
class ManageAclAppModel extends AppModel {

    /**
    * Retrieves the Aro/Aco node for this model
    *
    * @param string|array|Model $ref Array with 'model' and 'foreign_key', model object, or string value
    * @return array Node found in database
    * @throws CakeException when binding to a model that doesn't exist.
    */
    public function node($ref = null) {
            $db = $this->getDataSource();
            $type = $this->alias;
            $result = null;

            if (!empty($this->useTable)) {
                    $table = $this->useTable;
            } else {
                    $table = Inflector::pluralize(Inflector::underscore($type));
            }

            if (empty($ref)) {
                    return null;
            } elseif (is_string($ref)) {
                    $path = explode('/', $ref);
                    $start = $path[0];
                    unset($path[0]);

                    $queryData = array(
                            'conditions' => array(
                                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
                                    $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")),
                            'fields' => array('id', 'parent_id', 'model', 'foreign_key', 'alias'),
                            'joins' => array(array(
                                    'table' => $table,
                                    'alias' => "{$type}0",
                                    'type' => 'INNER',
                                    'conditions' => array("{$type}0.alias" => $start)
                            )),
                            'order' => $db->name("{$type}.lft") . ' DESC'
                    );
                    $conditionsAfterJoin = array();

                    foreach ($path as $i => $alias) {
                            $j = $i - 1;

                            $queryData['joins'][] = array(
                                    'table' => $table,
                                    'alias' => "{$type}{$i}",
                                    'type' => 'INNER',
                                    'conditions' => array(
                                            $db->name("{$type}{$i}.alias") . ' = ' . $db->value($alias, 'string')
                                    )
                            );

                            // it will be better if this conditions will performs after join operation
                            $conditionsAfterJoin[] = $db->name("{$type}{$j}.id") . ' = ' . $db->name("{$type}{$i}.parent_id");
                            $conditionsAfterJoin[] = $db->name("{$type}{$i}.rght") . ' < ' . $db->name("{$type}{$j}.rght");
                            $conditionsAfterJoin[] = $db->name("{$type}{$i}.lft") . ' > ' . $db->name("{$type}{$j}.lft");

                            $queryData['conditions'] = array('or' => array(
                                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght"),
                                    $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}{$i}.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}{$i}.rght"))
                            );
                    }
                    $queryData['conditions'] = array_merge($queryData['conditions'], $conditionsAfterJoin);
                    $result = $db->read($this, $queryData, -1);
                    $path = array_values($path);

                    if (
                            !isset($result[0][$type]) ||
                            (!empty($path) && $result[0][$type]['alias'] != $path[count($path) - 1]) ||
                            (empty($path) && $result[0][$type]['alias'] != $start)
                    ) {
                            return false;
                    }
            } elseif (is_object($ref) && $ref instanceof Model) {
                    $ref = array('model' => $ref->name, 'foreign_key' => $ref->id);
            } elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
                    $name = key($ref);
                    list(, $alias) = pluginSplit($name);

                    $model = ClassRegistry::init(array('class' => $name, 'alias' => $alias));

                    if (empty($model)) {
                            throw new CakeException('cake_dev', "Model class '%s' not found in AclNode::node() when trying to bind %s object", $type, $this->alias);
                    }

                    $tmpRef = null;
                    if (method_exists($model, 'bindNode')) {
                            $tmpRef = $model->bindNode($ref);
                    }
                    if (empty($tmpRef)) {
                            $ref = array('model' => $alias, 'foreign_key' => $ref[$name][$model->primaryKey]);
                    } else {
                            if (is_string($tmpRef)) {
                                    return $this->node($tmpRef);
                            }
                            $ref = $tmpRef;
                    }
            }
            if (is_array($ref)) {
                    if (is_array(current($ref)) && is_string(key($ref))) {
                            $name = key($ref);
                            $ref = current($ref);
                    }
                    foreach ($ref as $key => $val) {
                            if (strpos($key, $type) !== 0 && strpos($key, '.') === false) {
                                    unset($ref[$key]);
                                    $ref["{$type}0.{$key}"] = $val;
                            }
                    }
                    $queryData = array(
                            'conditions' => $ref,
                            'fields' => array('id', 'parent_id', 'model', 'foreign_key', 'alias'),
                            'joins' => array(array(
                                    'table' => $table,
                                    'alias' => "{$type}0",
                                    'type' => 'INNER',
                                    'conditions' => array(
                                            $db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
                                            $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")
                                    )
                            )),
                            'order' => $db->name("{$type}.lft") . ' DESC'
                    );
                    $result = $db->read($this, $queryData, -1);

                    if (!$result) {
                            throw new CakeException(__d('cake_dev', "AclNode::node() - Couldn't find %s node identified by \"%s\"", $type, print_r($ref, true)));
                    }
            }
            return $result;
    }
}
