
/**
 * Index method
 *
 * @return void
 */
	public function index() {
		$this->set('bakeArticles', $this->paginate($this->BakeArticles));
	}

/**
 * View method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$bakeArticle = $this->BakeArticles->get($id);
		$this->set('bakeArticle', $bakeArticle);
	}

/**
 * Add method
 *
 * @return void
 */
	public function add() {
		$bakeArticle = $this->BakeArticles->newEntity();
		if ($this->request->is('post')) {
			$bakeArticle = $this->BakeArticles->patchEntity($bakeArticle, $this->request->data);
			if ($this->BakeArticles->save($bakeArticle)) {
				$this->Session->setFlash(__('The bake article has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The bake article could not be saved. Please, try again.'));
			}
		}
		$bakeTags = $this->BakeArticles->association('BakeTags')->find('list');
		$this->set(compact('bakeTags'));
	}

/**
 * Edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$bakeArticle = $this->BakeArticles->get($id);
		if ($this->request->is(['post', 'put'])) {
			$bakeArticle = $this->BakeArticles->patchEntity($bakeArticle, $this->request->data);
			if ($this->BakeArticles->save($bakeArticle)) {
				$this->Session->setFlash(__('The bake article has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The bake article could not be saved. Please, try again.'));
			}
		}
		$bakeTags = $this->BakeArticles->association('BakeTags')->find('list');
		$this->set(compact('bakeTags'));
	}

/**
 * Delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$bakeArticle = $this->BakeArticles->get($id);
		$this->request->allowMethod('post', 'delete');
		if ($this->BakeArticles->delete($bakeArticle)) {
			$this->Session->setFlash(__('The bake article has been deleted.'));
		} else {
			$this->Session->setFlash(__('The bake article could not be deleted. Please, try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}
