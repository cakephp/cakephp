<?php
$content = explode("\n", $content);

foreach ($content as $line):
	echo '<p> ' . $line . '</p>';
endforeach;
?>