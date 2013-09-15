<p>This is regular text</p>
<!--nocache-->
	<p>A. Layout Before Content</p>
	<?php $this->log('1. layout before content') ?>
<!--/nocache-->
<!--nocache--><?php echo $this->element('nocache/plain'); ?><!--/nocache-->
<!--nocache-->
	<p>C. Layout After Test Element But Before Content</p>
	<?php $this->log('3. layout after test element but before content') ?>
<!--/nocache-->
<?php echo $this->fetch('content'); ?>
<!--nocache-->
	<p>E. Layout After Content</p>
	<?php $this->log('5. layout after content') ?>
<!--/nocache-->
<p>Additional regular text.</p>
<?php echo $this->element('nocache/contains_nocache'); ?>
<!--nocache-->
	<p>G. Layout After Content And After Element With No Cache Tags</p>
	<?php $this->log('7. layout after content and after element with no cache tags') ?>
<!--/nocache-->
