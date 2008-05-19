<?php 
class CampaignFixture extends CakeTestFixture {
    var $name = 'Campaign';    
        
    var $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
    ); 
    
    var $records = array(
        array( 'id' => 1 , 'name' => 'Hurtigruten' ),
        array( 'id' => 2 , 'name' => 'Colorline' ),
        array( 'id' => 3 , 'name' => 'Queen of Scandinavia' )    
    );
} 
?> 