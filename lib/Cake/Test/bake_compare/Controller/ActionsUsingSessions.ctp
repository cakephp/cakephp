
/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->BakeArticle->recursive = 0;
		$this->set('bakeArticles', $this->Paginator->paginate());
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->BakeArticle->exists($id)) {
			throw new NotFoundException(__('Invalid bake article'));
		}
		$options = array('conditions' => array('BakeArticle.' . $this->BakeArticle->primaryKey => $id));
		$this->set('bakeArticle', $this->BakeArticle->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->BakeArticle->create();
			if ($this->BakeArticle->save($this->request->data)) {
				$this->Session->setFlash(__('The bake article has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The bake article could not be saved. Please, try again.'));
			}
		}
		$bakeTags = $this->BakeArticle->BakeTag->find('list');
		$this->set(compact('bakeTags'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->BakeArticle->exists($id)) {
			throw new NotFoundException(__('Invalid bake article'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->BakeArticle->save($this->request->data)) {
				$this->Session->setFlash(__('The bake article has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The bake article could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('BakeArticle.' . $this->BakeArticle->primaryKey => $id));
			$this->request->data = $this->BakeArticle->find('first', $options);
		}
		$bakeTags = $this->BakeArticle->BakeTag->find('list');
		$this->set(compact('bakeTags'));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->BakeArticle->id = $id;
		if (!$this->BakeArticle->exists()) {
			throw new NotFoundException(__('Invalid bake article'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->BakeArticle->delete()) {
			$this->Session->setFlash(__('The bake article has been deleted.'));
		} else {
			$this->Session->setFlash(__('The bake article could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}
