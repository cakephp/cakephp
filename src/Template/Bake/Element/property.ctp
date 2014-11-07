<%
use Cake\Utility\Inflector;

$last = count($value) - 1;
%>

/**
 * <%= Inflector::humanize($name) %>

 *
 * @var array
 */
	public $<%= $name %> = [
<% foreach($value as $i => $val): %>
		'<%= $val %>'<%= $i < $last ? ',' : ''; %>

<% endforeach; %>
	];
