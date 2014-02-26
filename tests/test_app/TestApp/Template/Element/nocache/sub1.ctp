<?= $this->element('nocache/sub2'); ?>

<!--nocache-->
	<?php $foobar = 'in sub1'; ?>
	<?= $foobar; ?>
<!--/nocache-->

<?= 'printing: "' . $foobar . '"'; ?>