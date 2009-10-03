<?php
class GroupUpdateAllFixture extends CakeTestFixture {
    var $name = 'GroupUpdateAll';
    var $table = 'group_update_all';

    var $fields = array(
            'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
            'name' => array('type'=>'string', 'null' => false, 'length' => 29),
            'code' => array('type'=>'integer', 'null' => false, 'length' => 4),
            'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
            );
    var $records = array(
        array(
            'id'  => 1,
            'name'  => 'group one',
            'code'  => 120),
        array(
            'id'  => 2,
            'name'  => 'group two',
            'code'  => 125),
        array(
            'id'  => 3,
            'name'  => 'group three',
            'code'  => 130),
        array(
            'id'  => 4,
            'name'  => 'group four',
            'code'  => 135)
        );
}
?>