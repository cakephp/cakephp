<?php
/**
 * Test suite plugin session handler
 */

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

class TestPluginSession implements CakeSessionHandlerInterface {

	public function open() {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($id) {
		return '';
	}

	public function write($id, $data) {
		return true;
	}

	public function destroy($id) {
		return true;
	}

	public function gc($expires = null) {
		return true;
	}

}
