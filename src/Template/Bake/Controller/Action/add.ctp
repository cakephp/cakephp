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
<% $compact = ["'" . $singularName . "'"]; %>
/**
 * Add method
 *
 * @return void
 */
	public function add() {
		$<%= $singularName %> = $this-><%= $currentModelName %>->newEntity($this->request->data);
		if ($this->request->is('post')) {
			if ($this-><%= $currentModelName; %>->save($<%= $singularName %>)) {
				$this->Flash->success('The <%= strtolower($singularHumanName) %> has been saved.');
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Flash->error('The <%= strtolower($singularHumanName) %> could not be saved. Please, try again.');
			}
		}
<%
		$associations = array_merge(
			$this->Bake->aliasExtractor($modelObj, 'belongsTo'),
			$this->Bake->aliasExtractor($modelObj, 'belongsToMany')
		);
		foreach ($associations as $assoc):
			$association = $modelObj->association($assoc);
			$otherName = $association->target()->alias();
			$otherPlural = $this->_variableName($otherName);
%>
		$<%= $otherPlural %> = $this-><%= $currentModelName %>-><%= $otherName %>->find('list');
<%
			$compact[] = "'$otherPlural'";
		endforeach;
%>
		$this->set(compact(" <%= join(', ', $compact) %> "));
	}
