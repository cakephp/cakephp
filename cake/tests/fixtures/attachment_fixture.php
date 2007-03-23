<?php

class AttachmentFixture extends CakeTestFixture {
	var $name = 'Attachment';
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'comment_id' => array('type' => 'integer', 'null' => false),
		'attachment' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	var $records = array(
		array('id' => 1, 'comment_id' => 5, 'attachment' => 'attachment.zip',  'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31')
	);
}

?>