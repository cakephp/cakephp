<?PHP

# Homepage
$Route->connect ('/', array('controller'=>'Pages', 'action'=>'index'));

# Tests
$Route->connect ('/test', array('controller'=>'Tests', 'action'=>'test_all'));

?>