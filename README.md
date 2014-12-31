# ManageAcl for CakePHP 2.x

This plugins allows you to implement custom Acl module in your application.

## Features

* Manage Aros
* Manage Acos
* Updating Database with Aros/Acos and ArosAco (permissions)
* Revoking all permissions

## Requirements

* CakePHP 2.x

## Plugin Prerequisite

## 1. Configure Auth in your AppController
    public $components = array(
        'ManageAcl.CustomAcl', 
        'Auth' => array('Controller',
            'authenticate' => array(
                'Form' => array(
                    'contain' => false
                )
            )
        ),
        'Session'
    );
    public function beforeFilter() {
        $this->Auth->allow('login', 'logout', 'error');
        $this->Auth->loginAction = array(
            'controller' => 'authentications',
            'action' => 'login',
            'admin' => false
        );
        $this->Auth->logoutRedirect = array(
            'controller' => 'authentications',
            'action' => 'login',
            'admin' => false
        );
        $this->Auth->authorize =  array(
                              'Actions' => array('actionPath' => 'controllers')
                          );
    }
## 2. Overwrite your Auth functionality to work without default ACL
Add ActionsAuthorize.php file in app/Controller/Component/ under Auth folder.
file path should be app/Controller/Component/Auth/ActionsAuthorize.php so that it will overwrite default ActionsAuthorize file which load Cakephp ACL.
ActionsAuthorize.php file content  -

    App::uses('BaseAuthorize', 'Controller/Component/Auth');

    class ActionsAuthorize extends BaseAuthorize {
    
        /**
         * Authorize a user using the ManageAcl Plugin.
         *
         * @param array $user The user to authorize
         * @param CakeRequest $request The request needing authorization.
         * @return bool
         */
        public function authorize($user, CakeRequest $request) {
                App::import('Component', 'ManageAcl.CustomAcl');
                $this->CustomAcl = new CustomAclComponent(new ComponentCollection());
                $user = array($this->settings['userModel'] => $user);
                return $this->CustomAcl->check($user, $this->action($request));
        }
    }

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
if new database is generated :

    // param1 : command, param2 : truncate flag, param3 : data array
    $this->CustomAcl->generateAros('create', true, array('datasource' => $userName));
    // param1 : data array
    $this->CustomAcl->generateAcos(array('datasource' => $userName));
    // param1 : data array
    $this->CustomAcl->generateArosAcos(array('datasource' => $userName));
    
if user is added / updated

    $data = array('model' => 'User', 'ids' => array($userData['User']['id'] => $group));
    // param1 : command, param2 : truncate flag, param3 : data array
    $this->CustomAcl->generateAros('update', false, $data);
    // param1 : data array
    $this->CustomAcl->generateArosAcos($data);
    
### 7. You're done!

Enjoy!
