<%
$actions = ['index', 'view', 'add', 'edit', 'delete'];
foreach($actions as $action) {
	$out[] = trim($this->element('Controller/' . $action));
}
echo implode("\n\n", $out);
%>

