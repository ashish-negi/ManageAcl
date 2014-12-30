<div class="view">
	<h3><?php echo sprintf(__('Acl Manager %s'), Configure::read('ManageAcl.version')); ?></h3>
	<p>Access control lists, or ACL, handle two main things: things that want stuff, and things that are wanted. In ACL lingo, things (most often users) that want to use stuff are represented by access request objects, or AROs. Things in the system that are wanted (most often actions or data) are represented by access control objects, or ACOs. The entities are called ‘objects’ because sometimes the requesting object isn’t a person. Sometimes you might want to limit the ability of certain CakePHP controllers to initiate logic in other parts of your application. ACOs could be anything you want to control, from a controller action, to a web service, to a line in your grandma’s online diary.</p>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Manage permissions'), array('action' => 'permissions')); ?></li>
		<li><?php echo $this->Html->link(__('Update ACOs'), array('action' => 'update_acos')); ?></li>
		<li><?php echo $this->Html->link(__('Update AROs'), array('action' => 'update_aros')); ?></li>
		<li><?php echo $this->Html->link(__('Generate Permissions'), array('action' => 'update_permissions')); ?></li>
		<li><?php echo $this->Html->link(__('Drop ACOs/AROs'), array('action' => 'drop'), array(), __("Do you want to drop all ACOs and AROs?")); ?></li>
		<li><?php echo $this->Html->link(__('Drop permissions'), array('action' => 'drop_perms'), array(), __("Do you want to drop all the permissions?")); ?></li>
	</ul>
</div>
