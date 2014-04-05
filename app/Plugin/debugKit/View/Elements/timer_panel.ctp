<?php
/**
 * Timer Panel Element
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

$this->Number = $this->Helpers->load('Number');
$this->SimpleGraph = $this->Helpers->load('DebugKit.SimpleGraph');

if (!isset($debugKitInHistoryMode)):
	$timers = DebugTimer::getAll(true);
	$currentMemory = DebugKitDebugger::getMemoryUse();
	$peakMemory = DebugKitDebugger::getPeakMemoryUse();
	$requestTime = DebugTimer::requestTime();
else:
	$content = $this->Toolbar->readCache('timer', $this->request->params['pass'][0]);
	if (is_array($content)):
		extract($content);
	endif;
endif;
?>
<div class="debug-info">
	<h2><?php echo __d('debug_kit', 'Memory'); ?></h2>
	<div class="peak-mem-use">
	<?php
		echo $this->Toolbar->message(__d('debug_kit', 'Peak Memory Use'), $this->Number->toReadableSize($peakMemory)); ?>
	</div>

	<?php
	$headers = array(__d('debug_kit', 'Message'), __d('debug_kit', 'Memory use'));
	$memoryPoints = DebugKitDebugger::getMemoryPoints();

	$rows = array();
	foreach ($memoryPoints as $key => $value):
		$rows[] = array($key, $this->Number->toReadableSize($value));
	endforeach;

	echo $this->Toolbar->table($rows, $headers);
	?>
</div>

<div class="debug-info debug-timers">
	<h2><?php echo __d('debug_kit', 'Timers'); ?></h2>
	<div class="request-time">
		<?php $totalTime = __d('debug_kit', '%s (ms)', $this->Number->precision($requestTime * 1000, 0)); ?>
		<?php echo $this->Toolbar->message(__d('debug_kit', 'Total Request Time:'), $totalTime)?>
	</div>
<?php
$rows = array();
$end = end($timers);
$maxTime = $end['end'];

$headers = array(
	__d('debug_kit', 'Message'),
	__d('debug_kit', 'Time in ms'),
	__d('debug_kit', 'Graph')
);

$i = 0;
$values = array_values($timers);

foreach ($timers as $timerName => $timeInfo):
	$indent = 0;
	for ($j = 0; $j < $i; $j++) {
		if (($values[$j]['end'] > $timeInfo['start']) && ($values[$j]['end']) > ($timeInfo['end'])) {
			$indent++;
		}
	}
	$indent = str_repeat(' &raquo; ', $indent);
	$rows[] = array(
		$indent . $timeInfo['message'],
		$this->Number->precision($timeInfo['time'] * 1000, 2),
		$this->SimpleGraph->bar(
			$this->Number->precision($timeInfo['time'] * 1000, 2),
			$this->Number->precision($timeInfo['start'] * 1000, 2),
			array(
				'max' => $maxTime * 1000,
				'requestTime' => $requestTime * 1000,
			)
		)
	);
	$i++;
endforeach;

if (strtolower($this->Toolbar->getName()) === 'firephptoolbar'):
	for ($i = 0, $len = count($rows); $i < $len; $i++):
		unset($rows[$i][2]);
	endfor;
	unset($headers[2]);
endif;

echo $this->Toolbar->table($rows, $headers, array('title' => 'Timers'));

if (!isset($debugKitInHistoryMode)):
	$this->Toolbar->writeCache('timer', compact('timers', 'currentMemory', 'peakMemory', 'requestTime'));
endif;
?>
</div>
