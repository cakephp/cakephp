<?php
$class = 'message';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
?>
<div id="<?= $key ?>Message" class="<?= h($class) ?>"><?= h($message) ?></div>
