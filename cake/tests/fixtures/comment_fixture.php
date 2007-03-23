<?php

class CommentFixture extends CakeTestFixture {
	var $name = 'Comment';
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'article_id' => array('type' => 'integer', 'null'=>false),
		'user_id' => array('type' => 'integer', 'null'=>false),
		'comment' => 'text',
		'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	var $records = array(
		array('id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
		array('id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
		array('id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
		array('id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
		array('id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
		array('id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
	);
}

?>