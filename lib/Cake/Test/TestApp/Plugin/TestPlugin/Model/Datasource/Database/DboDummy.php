<?php
namespace TestPlugin\Model\Datasource\Database;

use Cake\Model\Datasource\DboSource;

class DboDummy extends DboSource {

	public function connect() {
		return true;
	}

}
