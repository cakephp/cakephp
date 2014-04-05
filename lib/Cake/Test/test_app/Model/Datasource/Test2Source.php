<?php
class Test2Source extends DataSource {

	public function describe($model) {
		return compact('model');
	}

	public function listSources($data = null) {
		return array('test_source');
	}

	public function create(Model $model, $fields = null, $values = null) {
		return compact('model', 'fields', 'values');
	}

	public function read(Model $model, $queryData = array(), $recursive = null) {
		return compact('model', 'queryData');
	}

	public function update(Model $model, $fields = array(), $values = array(), $conditions = null) {
		return compact('model', 'fields', 'values');
	}

	public function delete(Model $model, $id = null) {
		return compact('model', 'id');
	}
}
