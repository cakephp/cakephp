<?php

if (!class_exists('Cache') || Configure::read('debug') < 2) {
	return false;
}

$noLogs = !isset($logs);
if ($noLogs):
	$logs = Cache::getLog();
endif;

foreach ($logs as $config => $logInfo):
	$text = $logInfo['count'] > 1 ? 'queries' : 'query';
	printf(
		'<table class="cake-sql-log cake-cache-log" id="cakeCacheLog_%s" summary="Cake Cache Log" cellspacing="0" border="0">',
		preg_replace('/[^A-Za-z0-9_]/', '_', uniqid(time(), true))
	);
	printf('<caption>(%s) %s %s took %s ms</caption>', $config, $logInfo['count'], $text, round($logInfo['time'], 5));
?>
<thead>
	<tr><th>Nr</th><th>Operation</th><th>Key</th><th>Value</th><th>Error</th><th>Took (ms)</th></tr>
</thead>
<tbody>
<?php
	foreach ($logInfo['logs'] as $k => $i) :
		$outputValue = var_export($i['value'], true);
		if (isset($outputValue{1024})) {
			$outputValue = substr($outputValue, 0, 1024) . ' [...]';
		}
		echo '<tr>',
			'<td>', ($k + 1), '</td>',
			'<td>', $i['action'], '</td>',
			'<td>', h($i['key']), '</td>',
			'<td>', h($outputValue), '</td>',
			'<td>', $i['error'], '</td>',
			'<td style="text-align: right">', round($i['time'], 5), '</td>',
			'</tr>', "\n";
	endforeach;
?>
</tbody></table>
<?php
endforeach;
