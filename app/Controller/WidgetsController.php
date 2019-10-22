<?php
App::uses('AppController', 'Controller');
/**
 * Widgets Controller
 *
 * @property Widget $Widget
 * @property PaginatorComponent $Paginator
 */
class WidgetsController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Widget->recursive = 0;
		$this->set('widgets', $this->Paginator->paginate());
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Widget->exists($id)) {
			throw new NotFoundException(__('Invalid widget'));
		}
		$options = array('conditions' => array('Widget.' . $this->Widget->primaryKey => $id));
		$this->set('widget', $this->Widget->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Widget->create();
			if ($this->Widget->save($this->request->data)) {
				$this->Flash->success(__('The widget has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Flash->error(__('The widget could not be saved. Please, try again.'));
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Widget->exists($id)) {
			throw new NotFoundException(__('Invalid widget'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Widget->save($this->request->data)) {
				$this->Flash->success(__('The widget has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Flash->error(__('The widget could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('Widget.' . $this->Widget->primaryKey => $id));
			$this->request->data = $this->Widget->find('first', $options);
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		if (!$this->Widget->exists($id)) {
			throw new NotFoundException(__('Invalid widget'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Widget->delete($id)) {
			$this->Flash->success(__('The widget has been deleted.'));
		} else {
			$this->Flash->error(__('The widget could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}
}
