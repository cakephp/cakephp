<%
use Cake\Utility\Inflector;
%>

/**
 * <%= Inflector::humanize($name) %>
 *
 * @var array
 */
	public $<%= $name %> = [<%= $this->Bake->stringifyList($value, ['indent' => false]) %>];
