<?php
/**
 * Test suite plugin session handler
 */
class TestPluginSession implements CakeSessionHandlerInterface {

	public static function open() {
		return true;
	}

	public static function close() {
		
	}

	public static function read($id) {
		
	}

	public static function write($id, $data) {
		
	}

	public static function destroy($id) {
		
	}

	public static function gc($expires = null) {
		
	}
}