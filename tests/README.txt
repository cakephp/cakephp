--------------------------------------------------------------------------------

Cake Unit Test Suite

--------------------------------------------------------------------------------

$Id$
$Date$
$LastChangedBy$

--------------------------------------------------------------------------------

RUNNING THE TESTS

Some paths need to be set up for the unit test suite to run correctly. The paths
should be set in the caketest.config.ini file. The values that may need to be changed are:

  * TEST_CASES = 
  * TEST_GROUPS = 
  * SIMPLE_TEST_DEFAULT =
  * TEST_HTTP_PATH = 
  * CAKE_EXAMPLES_HTTP_PATH =
  * library_path =
  
  
All test cases should have the file suffix '.test.php'.
  Example:  controller.test.php
  
All group tests should have the file suffix '.group.php'.
  Example:  controller.group.php