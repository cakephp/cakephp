<?php echo $this->element('nocache/sub2'); ?>

<!--nocache-->
	<?php $foobar = 'in sub1'; ?>
	<?php echo $foobar; ?>
<!--/nocache-->

<?php echo 'printing: "' . $foobar . '"'; ?>