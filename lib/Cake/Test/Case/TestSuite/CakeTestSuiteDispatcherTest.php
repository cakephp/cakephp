<?php

class CakeTestSuiteDispatcherTest extends CakeTestCase {

  public function setUp() {
    $this->vendors = App::path('vendors');
    $this->includePath = ini_get('include_path');
  }

  public function tearDown() {
    App::build(array('Vendor' => $this->vendors), App::RESET);
    ini_set('include_path', $this->includePath);
  }

  protected function clearPaths() {
    App::build(array('Vendor' => array('junk')), App::RESET);
    ini_set('include_path', 'junk');
  }

  public function testLoadTestFramework() {
    $dispatcher = new CakeTestSuiteDispatcher();

    $this->assertTrue($dispatcher->loadTestFramework());

    $this->clearPaths();

    $exception = null;

    try {
      $dispatcher->loadTestFramework();
    } catch (Exception $ex) {
      $exception = $ex;
    }

    $this->assertEquals(get_class($exception), "PHPUnit_Framework_Error_Warning");
  }

}
