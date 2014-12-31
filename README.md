# ManageAcl for CakePHP 2.x

This plugins allows you to implement custom Acl module in your application.

## Features

* Manage Aros
* Manage Acos
* Updating Database with Aros/Acos and ArosAco (permissions)
* Revoking all permissions

## Requirements

* CakePHP 2.x

## How to install

### 1. Download ManageAcl

#### Manually

Download the stable branch and paste the content in your `app/Plugin/` directory.

### 2. Configure the plugin

See `ManageAcl/Config/bootstrap.php`

ManageAcl.aros : write in there your requester models aliases (the order is important)

### 3. Enable the plugin

In `app/Config/bootstrap.php`

    CakePlugin::load('ManageAcl', array('bootstrap' => true));

### 4. Access the plugin at `/manage_acl/acl`

   * Update your AROs and ACOs
   * Set up your permissions (do not forget to enable your own public actions!)

### 5. Call component in your app
    public $components = array('ManageAcl.CustomAcl');

### 6. Update your Aros/Acos/ArosAcos
    // param1 : command, param2 : truncate flag, param3 : data array
    $this->CustomAcl->generateAros('create', true, array('datasource' => $userName));
    // param1 : data array
    $this->CustomAcl->generateAcos(array('datasource' => $userName));
    // param1 : data array
    $this->CustomAcl->generateArosAcos(array('datasource' => $userName));
    
### 7. You're done!

Enjoy!