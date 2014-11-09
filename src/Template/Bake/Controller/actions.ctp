<%
$actions = ['index', 'view', 'add', 'edit', 'delete'];
foreach($actions as $action) {
	$out[] = trim($this->render('/Controller/Action/' . $action, false));
}
echo implode("\n\n", $out);
