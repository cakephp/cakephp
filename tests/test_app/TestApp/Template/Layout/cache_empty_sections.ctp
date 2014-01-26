<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?= $this->fetch('title'); ?></title>
	<!--nocache--><?php $x = 1; ?><!--/nocache-->
</head>
<body>
	<!--nocache--><?php $x++; ?><!--/nocache-->
	<!--nocache--><?php $x++; ?><!--/nocache-->
	<?= $this->fetch('content'); ?>
	<!--nocache--><?= 'cached count is: ' . $x; ?><!--/nocache-->
</body>
</html>
