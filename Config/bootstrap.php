<?php

/**
 * List of AROs (Class aliases)
 * Order is important! Parent to Children
 */
Configure::write('ManageAcl.aros', array('Group', 'User'));

/**
 * Limit used to paginate AROs
 * Replace {alias} with ARO alias
 * Configure::write('ManageAcl.{alias}.limit', 3)
 */
// Configure::write('ManageAcl.Role.limit', 3);

/**
 * Routing Prefix
 * Set the prefix you would like to restrict the plugin to
 * @see Configure::read('Routing.prefixes')
 */
// Configure::write('ManageAcl.prefix', 'admin');

/**
 * Ugly identation?
 * Turn off when using CSS
 */
Configure::write('ManageAcl.uglyIdent', true);
				
/**
 * Actions to ignore when looking for new ACOs
 * Format: 'action', 'Controller/action' or 'Plugin.Controller/action'
 */
Configure::write('ManageAcl.ignoreActions', array('isAuthorized'));

/**
 * action permissions
 */
Configure::write('ManageAcl.actionPermissions', array('_create' => 1, '_read' => 1, '_update' => 1, '_delete' => 1));

/**
 * List of ARO models to load
 * Use only if ManageAcl.aros aliases are different than model name
 */
// Configure::write('ManageAcl.models', array('Group', 'Customer'));

/**
 * END OF USER SETTINGS
 */

Configure::write("ManageAcl.version", "1.0");
if (!is_array(Configure::read('ManageAcl.aros'))) {
	Configure::write('ManageAcl.aros', array(Configure::read('ManageAcl.aros')));
}
if (!is_array(Configure::read('ManageAcl.ignoreActions'))) {
	Configure::write('ManageAcl.ignoreActions', array(Configure::read('ManageAcl.ignoreActions')));
}
if (!Configure::read('ManageAcl.models')) {
	Configure::write('ManageAcl.models', Configure::read('ManageAcl.aros'));
}
