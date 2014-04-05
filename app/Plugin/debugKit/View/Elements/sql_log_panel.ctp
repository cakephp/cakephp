<?php
/**
 * SQL Log Panel Element
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$headers = array('Query', 'Affected', 'Num. rows', 'Took (ms)', 'Actions');
if (isset($debugKitInHistoryMode)) {
	$content = $this->Toolbar->readCache('sql_log', $this->request->params['pass'][0]);
}
?>
<h2><?php echo __d('debug_kit', 'Sql Logs')?></h2>
<?php if (!empty($content)) : ?>
	<?php foreach ($content['connections'] as $dbName => $explain): ?>
	<div class="sql-log-panel-query-log">
		<h4><?php echo $dbName ?></h4>
		<?php
			if (!isset($debugKitInHistoryMode)):
				$queryLog = $this->Toolbar->getQueryLogs($dbName, array(
					'explain' => $explain, 'threshold' => $content['threshold']
				));
			else:
				$queryLog = $content[$dbName];
			endif;
			if (empty($queryLog['queries'])):
				if (Configure::read('debug') < 2):
					echo ' ' . __d('debug_kit', 'No query logs when debug < 2.');
				else:
					echo ' ' . __d('debug_kit', 'No query logs.');
				endif;
			else:
				echo '<h5>';
				echo __d(
					'debug_kit',
					'Total Time: %s ms <br />Total Queries: %s queries',
					$queryLog['time'],
					$queryLog['count']
				);
				echo '</h5>';
				echo $this->Toolbar->table($queryLog['queries'], $headers, array('title' => 'SQL Log ' . $dbName));
			?>
		<h4><?php echo __d('debug_kit', 'Query Explain:'); ?></h4>
		<div id="sql-log-explain-<?php echo $dbName ?>">
			<a id="debug-kit-explain-<?php echo $dbName ?>"> </a>
			<p><?php echo __d('debug_kit', 'Click an "Explain" link above, to see the query explanation.'); ?></p>
		</div>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
<?php else:
	echo $this->Toolbar->message('Warning', __d('debug_kit', 'No active database connections'));
endif;
