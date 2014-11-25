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

$belongsTo = $this->Bake->aliasExtractor($modelObj, 'BelongsTo');
$belongsToMany = $this->Bake->aliasExtractor($modelObj, 'BelongsToMany');
$compact = ["'" . $singularName . "'"];
%>

/**
 * Edit method
 *
 * @param string|null $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function edit($id = null) {
		$<%= $singularName %> = $this-><%= $currentModelName %>->get($id, [
			'contain' => [<%= $this->Bake->stringifyList($belongsToMany, ['indent' => false]) %>]
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$<%= $singularName %> = $this-><%= $currentModelName %>->patchEntity($<%= $singularName %>, $this->request->data);
			if ($this-><%= $currentModelName; %>->save($<%= $singularName %>)) {
				$this->Flash->success('The <%= strtolower($singularHumanName) %> has been saved.');
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Flash->error('The <%= strtolower($singularHumanName) %> could not be saved. Please, try again.');
			}
		}
<%
		foreach (array_merge($belongsTo, $belongsToMany) as $assoc):
			$association = $modelObj->association($assoc);
			$otherName = $association->target()->alias();
			$otherPlural = $this->_variableName($otherName);
%>
		$<%= $otherPlural %> = $this-><%= $currentModelName %>-><%= $otherName %>->find('list');
<%
			$compact[] = "'$otherPlural'";
		endforeach;
%>
		$this->set(compact(<%= join(', ', $compact) %>));
	}
