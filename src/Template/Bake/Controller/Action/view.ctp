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
$allAssociations = array_merge(
	$this->Bake->aliasExtractor($modelObj, 'BelongsTo'),
	$this->Bake->aliasExtractor($modelObj, 'BelongsToMany'),
	$this->Bake->aliasExtractor($modelObj, 'HasOne'),
	$this->Bake->aliasExtractor($modelObj, 'HasMany')
);
%>
/**
 * View method
 *
 * @param string $id
 * @return void
 * @throws \Cake\Network\Exception\NotFoundException
 */
	public function view($id = null) {
		$<%= $singularName%> = $this-><%= $currentModelName %>->get($id, [
			'contain' => [<%= $this->Bake->stringifyList($allAssociations, ['indent' => false]) %>]
		]);
		$this->set('<%= $singularName %>', $<%= $singularName %>);
	}
