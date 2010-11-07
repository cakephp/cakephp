<?php

class CreateExample extends Pake{
  function selfUp(){
    return array(
      'page_id' => 'integer',
      'type' => 'integer',
      'ordem' => 'integer',
      'banner' => 'string',
      'link' => 'text',
    );
  }
  
  function create(){
    return array(
      array('page_id' => 1, 'type' => '325', 'ordem' => '23', 'banner' => 'test.jpg', 'link' => '/home'),
      array('page_id' => 2, 'type' => '325', 'ordem' => '23', 'banner' => 'test.jpg', 'link' => '/home'),
    );
  }

}

?>