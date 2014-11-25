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
<?php
namespace <%= $namespace %>\Model\Entity;

use Cake\ORM\Entity;

/**
 * <%= $name %> Entity.
 */
class <%= $name %> extends Entity {
<% if (!empty($fields)): %>

/**
 * Fields that can be mass assigned using newEntity() or patchEntity().
 *
 * @var array
 */
	protected $_accessible = [
<% foreach ($fields as $field): %>
		'<%= $field %>' => true,
<% endforeach; %>
	];
<% endif %>
<% if (!empty($hidden)): %>

/**
 * Fields that are excluded from JSON an array versions of the entity.
 *
 * @var array
 */
	protected $_hidden = [<%= $this->Bake->stringifyList($hidden) %>];
<% endif %>

}
