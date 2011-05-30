<?php

class DboDummy extends DboSource {
	public function connect() {
		return true;
	}
}
