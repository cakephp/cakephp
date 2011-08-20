<?php
class Test2OtherSource extends DataSource {

	public function describe($model) {
		return compact('model');
	}

	public function listSources() {
		return array('test_source');
	}

	public function create($model, $fields = array(), $values = array()) {
		return compact('model', 'fields', 'values');
	}

	public function read($model, $queryData = array()) {
		return compact('model', 'queryData');
	}

	public function update($model, $fields = array(), $values = array()) {
		return compact('model', 'fields', 'values');
	}

	public function delete($model, $id) {
		return compact('model', 'id');
	}
}
