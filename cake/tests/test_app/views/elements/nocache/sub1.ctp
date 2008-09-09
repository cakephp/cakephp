<?php echo $this->element('nocache/sub2'); ?>

<cake:nocache>
	<?php $foobar = 'in sub1'; ?>
	<?php echo $foobar; ?>
</cake:nocache>

<?php echo 'printing: "' . $foobar . '"'; ?>