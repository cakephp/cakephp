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
    //Comming
  }

}

?>