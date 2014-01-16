<?php
namespace TestPlugin\Model\Datasource\Session;

use Cake\Model\Datasource\Session\SessionHandlerInterface;

/**
 * Test suite plugin session handler
 */
class TestPluginSession implements SessionHandlerInterface {

	public function open() {
		return true;
	}

	public function close() {
	}

	public function read($id) {
	}

	public function write($id, $data) {
	}

	public function destroy($id) {
	}

	public function gc($expires = null) {
	}

}
