<?php

namespace Cake\Model\Datasource\Database;

/**
 * Represents a database diver containing all specificities for
 * a database engine including its SQL dialect
 *
 **/
abstract class Driver {

/**
 * Establishes a conenction to the databse server
 *
 * @return boolean true con success
 **/
	public abstract function connect($config = array());

/**
 * Returns wheter php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/
	public abstract function enabled();

}
