<?php /* SVN FILE: $Id$ */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title_for_layout; ?></title>
	<cake:nocache><?php $x = 1; ?></cake:nocache>
</head>
<body>
	<cake:nocache><?php $x++; ?></cake:nocache>
	<cake:nocache><?php $x++; ?></cake:nocache>
	<?php echo $content_for_layout;	?>
	<cake:nocache><?php echo 'cached count is: ' . $x; ?></cake:nocache>
</body>
</html>
