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
use Cake\Utility\Inflector;
%>
<?php
namespace <%= $namespace %>\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * <%= $name %> Model
 */
class <%= $name %>Table extends Table {

/**
 * Initialize method
 *
 * @param array $config The configuration for the Table.
 * @return void
 */
	public function initialize(array $config) {
<% if (!empty($table)): %>
		$this->table('<%= $table %>');
<% endif %>
<% if (!empty($displayField)): %>
		$this->displayField('<%= $displayField %>');
<% endif %>
<% if (!empty($primaryKey)): %>
<% if (count($primaryKey) > 1): %>
		$this->primaryKey([<%= $this->Bake->stringifyList((array)$primaryKey, ['indent' => false]) %>]);
<% else: %>
		$this->primaryKey('<%= current((array)$primaryKey) %>');
<% endif %>
<% endif %>
<% foreach ($behaviors as $behavior => $behaviorData): %>
		$this->addBehavior('<%= $behavior %>'<%= $behaviorData ? ", [" . implode(', ', $behaviorData) . ']' : '' %>);
<% endforeach %>
<% foreach ($associations as $type => $assocs): %>
<% foreach ($assocs as $assoc): %>
		$this-><%= $type %>('<%= $assoc['alias'] %>', [<%= $this->Bake->stringifyList($assoc, ['indent' => 3]) %>]);
<% endforeach %>
<% endforeach %>
	}
<% if (!empty($validation)): %>

/**
 * Default validation rules.
 *
 * @param \Cake\Validation\Validator $validator instance
 * @return \Cake\Validation\Validator
 */
	public function validationDefault(Validator $validator) {
		$validator
<% $validationMethods = []; %>
<%
foreach ($validation as $field => $rules):
	foreach ($rules as $ruleName => $rule):
		if ($rule['rule'] && !isset($rule['provider'])):
			$validationMethods[] = sprintf(
				"->add('%s', '%s', ['rule' => '%s'])",
				$field,
				$ruleName,
				$rule['rule']
			);
		elseif ($rule['rule'] && isset($rule['provider'])):
			$validationMethods[] = sprintf(
				"->add('%s', '%s', ['rule' => '%s', 'provider' => '%s'])",
				$field,
				$ruleName,
				$rule['rule'],
				$rule['provider']
			);
		endif;

		if (isset($rule['allowEmpty'])):
			if (is_string($rule['allowEmpty'])):
				$validationMethods[] = sprintf(
					"->allowEmpty('%s', '%s')",
					$field,
					$rule['allowEmpty']
				);
			elseif ($rule['allowEmpty']):
				$validationMethods[] = sprintf(
					"->allowEmpty('%s')",
					$field
				);
			else:
				$validationMethods[] = sprintf(
					"->requirePresence('%s', 'create')",
					$field
				);
				$validationMethods[] = sprintf(
					"->notEmpty('%s')",
					$field
				);
			endif;
		endif;
	endforeach;
endforeach;
%>
<%= "\t\t\t" . implode("\n\t\t\t", $validationMethods) . ";" %>


		return $validator;
	}
<% endif %>

}
