<?php
/**
 * Test suite app/Model/Datasource/Session session handler
 */

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

class TestAppLibSession implements CakeSessionHandlerInterface {

	public function open() {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($id) {
	}

	public function write($id, $data) {
	}

	public function destroy($id) {
		return true;
	}

	public function gc($expires = null) {
		return true;
	}


}
