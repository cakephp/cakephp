<?php

if (!class_exists('ConnectionManager') || Configure::read('debug') < 2) {
	return false;
}

$sources = ConnectionManager::sourceList();
foreach ($sources as $source):
	$db =& ConnectionManager::getDataSource($source);
	if (!$db->isInterfaceSupported('getLog')) {
		continue;
	}
	$logInfo = $db->getLog();
	$text = $logInfo['count'] > 1 ? 'queries' : 'query';
	printf(
		'<table class="cake-sql-log" id="cakeSqlLog_%s" summary="Cake SQL Log" cellspacing="0" border = "0">',
		preg_replace('/[^A-Za-z0-9_]/', '_', uniqid(time(), true))
	);
	printf('<caption>(%s) %s %s took %s ms</caption>', $source, $logInfo['count'], $text, $logInfo['time']);
?>
<thead>
	<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>
</thead>
<tbody>
<?php
	foreach ($logInfo['log'] as $k => $i) :
		echo "<tr><td>" . ($k + 1) . "</td><td>" . h($i['query']) . "</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n";
	endforeach;
?>
</tbody></table>
<?php endforeach; ?>
