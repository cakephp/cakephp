<%
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
%>
/**
 * Delete method
 *
 * @param string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function delete($id = null) {
		$<%= $singularName %> = $this-><%= $currentModelName %>->get($id);
		$this->request->allowMethod(['post', 'delete']);
		if ($this-><%= $currentModelName; %>->delete($<%= $singularName %>)) {
			$this->Flash->success('The <%= strtolower($singularHumanName) %> has been deleted.');
		} else {
			$this->Flash->error('The <%= strtolower($singularHumanName) %> could not be deleted. Please, try again.');
		}
		return $this->redirect(['action' => 'index']);
	}
