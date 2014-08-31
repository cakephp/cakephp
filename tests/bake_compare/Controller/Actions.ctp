
/**
 * Index method
 *
 * @return void
 */
	public function index() {
		$this->paginate = [
			'contain' => ['BakeUsers']
		];
		$this->set('bakeArticles', $this->paginate($this->BakeArticles));
	}

/**
 * View method
 *
 * @param string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function view($id = null) {
		$bakeArticle = $this->BakeArticles->get($id, [
			'contain' => ['BakeUsers', 'BakeTags', 'BakeComments']
		]);
		$this->set('bakeArticle', $bakeArticle);
	}

/**
 * Add method
 *
 * @return void
 */
	public function add() {
		$bakeArticle = $this->BakeArticles->newEntity($this->request->data);
		if ($this->request->is('post')) {
			if ($this->BakeArticles->save($bakeArticle)) {
				$this->Flash->success('The bake article has been saved.');
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Flash->error('The bake article could not be saved. Please, try again.');
			}
		}
		$bakeUsers = $this->BakeArticles->BakeUsers->find('list');
		$bakeTags = $this->BakeArticles->BakeTags->find('list');
		$this->set(compact('bakeArticle', 'bakeUsers', 'bakeTags'));
	}

/**
 * Edit method
 *
 * @param string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function edit($id = null) {
		$bakeArticle = $this->BakeArticles->get($id, [
			'contain' => ['BakeTags']
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$bakeArticle = $this->BakeArticles->patchEntity($bakeArticle, $this->request->data);
			if ($this->BakeArticles->save($bakeArticle)) {
				$this->Flash->success('The bake article has been saved.');
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Flash->error('The bake article could not be saved. Please, try again.');
			}
		}
		$bakeUsers = $this->BakeArticles->BakeUsers->find('list');
		$bakeTags = $this->BakeArticles->BakeTags->find('list');
		$this->set(compact('bakeArticle', 'bakeUsers', 'bakeTags'));
	}

/**
 * Delete method
 *
 * @param string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function delete($id = null) {
		$bakeArticle = $this->BakeArticles->get($id);
		$this->request->allowMethod('post', 'delete');
		if ($this->BakeArticles->delete($bakeArticle)) {
			$this->Flash->success('The bake article has been deleted.');
		} else {
			$this->Flash->error('The bake article could not be deleted. Please, try again.');
		}
		return $this->redirect(['action' => 'index']);
	}
