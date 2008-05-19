<?php 
class AdFixture extends CakeTestFixture {
    var $name = 'Ad';    
        
    var $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'campaign_id' => array('type' => 'integer'),
        'parent_id' => array('type' => 'integer'),
        'lft' => array('type' => 'integer'),
        'rght' => array('type' => 'integer'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
    ); 
    
    var $records = array(
        array( 'id' => 1, 'parent_id' => NULL, 'lft' => 1,  'rght' => 2,  'campaign_id' => 1, 'name' => 'Nordover' ),
        array( 'id' => 2, 'parent_id' => NULL, 'lft' => 3,  'rght' => 4,  'campaign_id' => 1, 'name' => 'Statbergen' ),
        array( 'id' => 3, 'parent_id' => NULL, 'lft' => 5,  'rght' => 6,  'campaign_id' => 1, 'name' => 'Feroy' ),
        array( 'id' => 4, 'parent_id' => NULL, 'lft' => 7, 'rght' => 12,  'campaign_id' => 2, 'name' => 'Newcastle' ),
        array( 'id' => 5, 'parent_id' => NULL, 'lft' => 8,  'rght' => 9,  'campaign_id' => 2, 'name' => 'Dublin' ),
        array( 'id' => 6, 'parent_id' => NULL, 'lft' => 10, 'rght' => 11, 'campaign_id' => 2, 'name' => 'Alborg' ),
        array( 'id' => 7, 'parent_id' => NULL, 'lft' => 13, 'rght' => 14, 'campaign_id' => 3, 'name' => 'New York' ),
    );
    
} 
?> 