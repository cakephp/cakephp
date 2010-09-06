<?php
/**
 * Test suite app/libs session handler
 *
 */
class TestAppLibSession implements CakeSessionHandlerInterface {

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