<?php

class CakeTestSuite extends PHPUnit_Framework_TestSuite {

	protected $_fixtureManager = null;

	public function setFixtureManager(CakeFixtureManager $manager) {
		$this->_fixtureManager = $manager;
	}

	protected function setUp() {
		parent::setUp();
		if (!$this->_fixtureManager) {
			return;
		}
		$classes = array();
		foreach ($this->getIterator() as $test) {
			$this->_fixtureManager->fixturize($test);
		}
		$this->sharedFixture = $this->_fixtureManager;
	}

	protected function tearDown() {
		parent::tearDown();
		$this->_fixtureManager->shutDown();
		$this->_fixtureManager = null;
		$this->sharedFixture = null;
	}
}