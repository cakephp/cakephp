<?php

class CategoryFixture extends CakeTestFixture {
	var $name = 'Category';
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'parent_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	var $records = array(
		array('id' => 1, 'parent_id' => 0, 'name' => 'Category 1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		array('id' => 2, 'parent_id' => 1, 'name' => 'Category 1.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		array('id' => 3, 'parent_id' => 1, 'name' => 'Category 1.2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		array('id' => 4, 'parent_id' => 0, 'name' => 'Category 2', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		array('id' => 5, 'parent_id' => 0, 'name' => 'Category 3', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31'),
		array('id' => 6, 'parent_id' => 5, 'name' => 'Category 3.1', 'created' => '2007-03-18 15:30:23', 'updated' => '2007-03-18 15:32:31')
	);
}

?>