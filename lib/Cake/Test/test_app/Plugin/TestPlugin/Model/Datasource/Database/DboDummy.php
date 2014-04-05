<?php

App::uses('DboSource', 'Model/Datasource');

class DboDummy extends DboSource {

	public function connect() {
		return true;
	}

}
