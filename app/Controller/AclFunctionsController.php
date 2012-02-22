<?php
App::uses('AppController', 'Controller');
/**
 * AclFunctions Controller
 *
 * @property AclFunction $AclFunction
 */
class AclFunctionsController extends AppController {


/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->AclFunction->recursive = 0;

		$this->set('aclFunctions', $this->paginate());
	}
	
	
/**
 * ajax_list method
 *
 * @return void
 */
	public function ajax_list() {
		//$this->AclFunction->recursive = 0;
		$aclFunctions = $this->AclFunction->find('list', array(
			//'conditions' => array('AclFunction.acl_id' => '4')
		));
		//$this->set('aclFunctions', $this->paginate());
		$this->set('_serialize', 'aclFunctions');
	}

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		$this->set('aclFunction', $this->AclFunction->read(null, $id));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->AclFunction->create();
			if ($this->AclFunction->save($this->request->data)) {
				$this->Session->setFlash(__('The acl function has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl function could not be saved. Please, try again.'));
			}
		}
		$acls = $this->AclFunction->Acl->find('list');
		$this->set(compact('acls'));
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->AclFunction->save($this->request->data)) {
				$this->Session->setFlash(__('The acl function has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl function could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->AclFunction->read(null, $id);
		}
		$acls = $this->AclFunction->Acl->find('list');
		$this->set(compact('acls'));
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
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		if ($this->AclFunction->delete()) {
			$this->Session->setFlash(__('Acl function deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl function was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->AclFunction->recursive = 0;
		$this->set('aclFunctions', $this->paginate());
	}

/**
 * admin_view method
 *
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		$this->set('aclFunction', $this->AclFunction->read(null, $id));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->AclFunction->create();
			if ($this->AclFunction->save($this->request->data)) {
				$this->Session->setFlash(__('The acl function has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl function could not be saved. Please, try again.'));
			}
		}
		$acls = $this->AclFunction->Acl->find('list');
		$this->set(compact('acls'));
	}

/**
 * admin_edit method
 *
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->AclFunction->save($this->request->data)) {
				$this->Session->setFlash(__('The acl function has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The acl function could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->AclFunction->read(null, $id);
		}
		$acls = $this->AclFunction->Acl->find('list');
		$this->set(compact('acls'));
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
		$this->AclFunction->id = $id;
		if (!$this->AclFunction->exists()) {
			throw new NotFoundException(__('Invalid acl function'));
		}
		if ($this->AclFunction->delete()) {
			$this->Session->setFlash(__('Acl function deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Acl function was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
}
