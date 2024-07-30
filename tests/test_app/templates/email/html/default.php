<?php
$content = explode("\n", (string) $content);

foreach ($content as $line):
    echo '<p> ' . $line . '</p>';
endforeach;
?>
