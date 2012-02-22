<?php
App::uses('AppController', 'Controller');
/**
 * AclRoles Controller
 *
 * @property AclRole $AclRole
 */
class AclRolesController extends AppController {
	public $helpers = array('Html', 'Form', 'Js');


/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->AclRole->recursive = 0;
		$this->set('aclRoles', $this->paginate());
	}

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		$this->set('aclRole', $this->AclRole->read(null, $id));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->AclRole->create();
			if ($this->AclRole->save($this->request->data)) {
				$this->Session->setFlash(__('The acl role has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl role could not be saved. Please, try again.'));
			}
		}
		$acls = $this->AclRole->Acl->find('list');
		$aclFunctions = $this->AclRole->AclFunction->find('list');
		$roles = $this->AclRole->Role->find('list');
		$this->set(compact('acls', 'aclFunctions', 'roles'));
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->AclRole->save($this->request->data)) {
				$this->Session->setFlash(__('The acl role has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl role could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->AclRole->read(null, $id);
		}
		$acls = $this->AclRole->Acl->find('list');
		$aclFunctions = $this->AclRole->AclFunction->find('list');
		$roles = $this->AclRole->Role->find('list');
		$this->set(compact('acls', 'aclFunctions', 'roles'));
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
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		if ($this->AclRole->delete()) {
			$this->Session->setFlash(__('Acl role deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl role was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->AclRole->recursive = 0;
		$this->set('aclRoles', $this->paginate());
	}

/**
 * admin_view method
 *
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		$this->set('aclRole', $this->AclRole->read(null, $id));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->AclRole->create();
			if ($this->AclRole->save($this->request->data)) {
				$this->Session->setFlash(__('The acl role has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl role could not be saved. Please, try again.'));
			}
		}
		$acls = $this->AclRole->Acl->find('list');
		$aclFunctions = $this->AclRole->AclFunction->find('list');
		$roles = $this->AclRole->Role->find('list');
		$this->set(compact('acls', 'aclFunctions', 'roles'));
	}

/**
 * admin_edit method
 *
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->AclRole->save($this->request->data)) {
				$this->Session->setFlash(__('The acl role has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl role could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->AclRole->read(null, $id);
		}
		$acls = $this->AclRole->Acl->find('list');
		$aclFunctions = $this->AclRole->AclFunction->find('list');
		$roles = $this->AclRole->Role->find('list');
		$this->set(compact('acls', 'aclFunctions', 'roles'));
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
		$this->AclRole->id = $id;
		if (!$this->AclRole->exists()) {
			throw new NotFoundException(__('Invalid acl role'));
		}
		if ($this->AclRole->delete()) {
			$this->Session->setFlash(__('Acl role deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl role was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
}
