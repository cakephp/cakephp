<?php
App::uses('AppController', 'Controller');
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
