<?php
App::uses('DboSource', 'Model/Datasource');
class DboDummy extends DboSource {
	function connect() {
		return true;
	}
}
