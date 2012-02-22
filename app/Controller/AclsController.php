<?php
//XXX I have modified this from the default for testing of the ini to database parsing conversion

App::uses('AppController', 'Controller', 'Inflector', 'Utility');

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
	# override if you have it in a plugin: PluginName.User etc
}
if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_');
	# use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini');
	# stored in /app/Config/
}

/**
 * Acls Controller
 *
 * @property Acl $Acl
 */
class AclsController extends AppController {


/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Acl->recursive = 0;
		$this->set('acls', $this->paginate());
	}

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		$this->set('acl', $this->Acl->read(null, $id));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Acl->create();
			if ($this->Acl->save($this->request->data)) {
				$this->Session->setFlash(__('The acl has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl could not be saved. Please, try again.'));
			}
		}
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Acl->save($this->request->data)) {
				$this->Session->setFlash(__('The acl has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->Acl->read(null, $id);
		}
	}

/**
 * delete method
 *
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		if ($this->Acl->delete()) {
			$this->Session->setFlash(__('Acl deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl was not deleted'));
		$this->redirect(array('action' => 'index'));
	}

/**
 * test_parse method
 * @return void
 */
	public function test_parse() {

		$iniArray = parse_ini_file(APP . 'Config' . DS . ACL_FILE, true);
		/*
		 * The ini file is as below
		 * 
		 * 		[Tools.Countries]
		 *		* = superadmin ; this is a comment
		 *		 
		 *		[Account]
		 *		edit,change_pw = *
		 *		 
		 *		[Activities]
		 *		admin_index,admin_edit,admin_add,admin_delete = admin,superadmin
		 *		index = *
		 *		 
		 *		[Users]
		 *		index,search = user
		 *		* = moderator,admin
		 * 
		 * iniArray will be parsed into an array that we will have to rebuild from a database query
		 * The array format from the above ini is below
		 * 
		 * array(
		 *'Tools.Countries' => array(
		 *		'*' => 'superadmin'
		 *		),
		 *	'Account' => array(
		 *		'edit,change_pw' => '*'
		 *		),
		 *	'Activities' => array(
		 *		'admin_index,admin_edit,admin_add,admin_delete' => 'admin,superadmin',
		 *		'index' => '*'
		 *		),
		 *	'Users' => array(
		 *		'index,search' => 'user',
		 *		'*' => 'moderator,admin'
		 *		)
		 *	)
		 * 
		 * We will need to create 6 tables: acls, acl_functions, acl_roles, roles, roles_users, and users
		 * acls will contain columns: id, controller
		 * acl_functions will contain columns: id, acl_id, function 
		 * acl_roles will contain columns: id, acl_id, acl_function_id, role_id
		 * roles will contain columns: id, name, description, application_id
		 * 
		 * acls will have many acl_functions and have many acl_roles
		 * acl_functions can have many acl_roles and belongs to acls
		 * acl_roles belongs to acl_functions and acls
		 * acl_roles has many roles
		 * 
		 * We will have to do a for each acl, select * from acl_functions where acl_id = $thisacl,
		 * for each acl_function where acl_id = $thisacl, select * from acl_roles inner join roles on roles.id = acl_roles.id where acl_function_id = $thisaclfunction,
		 * and finally a select * from acl_roles inner join roles on roles.id = acl_roles.id where acl_id = $thisacl and acl_function_id IS NULL for creating the wildcard (*)
		 * and csv the results as arrays
		 * 
		 * Our framework will have a roles table that also has an application_id so that we can filter roles on 
		 * the admin page to certain applications and define rules for the controllers needed for that application
		 * 
		 * I will recommend caching the outputted array used here, and creating logic for beforesave, flushing the key.  
		 * We can possibly add a button in the GUI for flushing the cache.
		 * 
		 * 
		 */
		/**
		 * Justin's testing
		 */
		
		$this->Acl->recursive = 2;
		$this->loadModel('AclFunction');
		$this->loadModel('AclRole');
		
		//$controllerName = $this->Acl->find('all');
		//debug($controllerName);
		// foreach($controllerName as $item) {
    		// $key = key($item);
    		// $element = current($item);
// 			
			// // if(is_array($item)) {
			// // foreach($subitem as $subkey){
			// // 				 	
			// // }
			// // }
// 			
	   		 // if(!isset($result[$key])) {
	        	// $result[$key] = array();
	   		 // }
// 			 
    		// $result[$key][] = $element;
		// }
		//debug($result);
		
		
		///////////////////////////////////////////////////////////////////////////////////////
		// 									richard test begins
		///////////////////////////////////////////////////////////////////////////////////////
		
		$richardAclRoleArray = $this->Acl->AclRole->find('all', array('fields' => array('Acl.Controller', 'AclFunction.Function', 'Role.Name')));
		
		//debug($richardAclRoleArray);
		
		// this will be used to find if this key already exists or not
		$compareName = "NONE";
		
		foreach($richardAclRoleArray as $item) {
			$controllerIndex 	= $item['Acl']['Controller'];
			$functionIndex 		= $item['AclFunction']['Function'];
			$roleIndex			= $item['Role']['Name'];
			
			//debug($item);
			//debug($item['Acl']['Controller']);
			
			//TODO need validation on element exists or not on every level.
			//TODO Need to compare result to database to make sure it grabs all of data.
			
			if(!isset($richardResult[$controllerIndex])) {
				// if controller does not exist, then create new array
				$richardResult[$controllerIndex] = array();
				$compareName = "NONE";
			}
			
			// if Function doesn't exist, then create a new one.
			
			
			if($compareName != $roleIndex) {
				echo ('line 242 $roleIndex='.$roleIndex.' and '.' $compareName='.$compareName.'<br />');
				
				$compareName = $roleIndex;
				$richardResult[$controllerIndex][$functionIndex] = $compareName;
				
			} else {
				echo ('line 248 $roleIndex='.$roleIndex.' and '.' $compareName='.$compareName.'<br />');
				
				if (isset($richardResult[$controllerIndex][$functionIndex])) {
					if ($richardResult[$controllerIndex][$functionIndex] != "") {
						$richardResult[$controllerIndex][$functionIndex] = $richardResult[$controllerIndex][$functionIndex].",".$compareName;
					}
				} else {
					$richardResult[$controllerIndex][$functionIndex] = $compareName;
				}
			}
		}
		
		debug($richardResult);
		
		
		//$this->loadModel('Roles');
		//$richardControllerArray 	= $this->Acl->find('list', array('fields' => array('Controller')));
		//$richardControllerArray 	= $this->Acl->find('list');
		//$richardRoleArray 		= $this->Roles->find('list', array('fields' => array('Name')));
		//$richardRolesArray 			= $this->Roles->find('list');
		//$richardAclRoleArrayAll		= $this->AclRole->find('all');
		//$richardAclRoleArray		= $this->AclRole->find('all', array('fields' => array('Acl.Controller', 'Role.Name', 'AclFunction.Function')));
		//$richardAclFunctionArray	= $this->AclFunction->find('list');
		//$richardAclArray			= $this->Acl->find('list');
		//debug($richardControllerArray);
		//debug($richardRolesArray);
		//debug($richardAclRoleArrayAll);
		//debug($richardAclRoleArray);
		//debug($richardAclFunctionArray);
		//debug($richardAclArray);
		
		
		
		///////////////////////////////////////////////////////////////////////////////////////
		// 									richard test ends
		///////////////////////////////////////////////////////////////////////////////////////
		
		
		
		
		
		
		//$result = Set::flatten($controllerName);  //this was a tests
		
		//debug($controllerName);
		//$functionName = $this->AclFunction->find('all');
		//debug($functionName);
		
		$test = array(
			'Tools.Countries' => array(
				'*' => 'superadmin'
			),
			'Account' => array(
				'edit,change_pw' => '*'
			),
			'Activities' => array(
				'admin_index,admin_edit,admin_add,admin_delete' => 'admin,superadmin',
				'index' => '*'
			),
			'Users' => array(
				'index,search' => 'user',
				'*' => 'moderator,admin'
			)
		);
		//debug($iniArray);
		$data = array('test' => $test);

		$this -> set($data);
	}

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->Acl->recursive = 0;
		$this->set('acls', $this->paginate());
	}

/**
 * admin_view method
 *
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		$this->set('acl', $this->Acl->read(null, $id));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->Acl->create();
			if ($this->Acl->save($this->request->data)) {
				$this->Session->setFlash(__('The acl has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl could not be saved. Please, try again.'));
			}
		}
	}

/**
 * admin_edit method
 *
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Acl->save($this->request->data)) {
				$this->Session->setFlash(__('The acl has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->Acl->read(null, $id);
		}
	}

/**
 * admin_delete method
 *
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->Acl->id = $id;
		if (!$this->Acl->exists()) {
			throw new NotFoundException(__('Invalid acl'));
		}
		if ($this->Acl->delete()) {
			$this->Session->setFlash(__('Acl deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
}
