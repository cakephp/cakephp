<table class="sql-log-query-explain debug-table">
<?php
$headers = array_shift($result);

echo $this->Html->tableHeaders($headers);
echo $this->Html->tableCells($result);
?>
</table>
<?php
// Consume and toss out the timers
$timers = DebugKitDebugger::getTimers(true);
