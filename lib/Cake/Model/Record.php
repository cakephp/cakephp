<?php

class Record implements ArrayAccess
{
	protected $_data;

	protected $_db_source;

	protected $_model_name;

	public function __construct(DataSource $source)
	{
		$this->setDbSource($source);
	}

	public function setModelName($model)
	{
		$this->_model_name = $model;
	}

	public function getModelName()
	{
		return $this->_model_name;
	}

	public function setDbSource(DataSource $source)
	{
		$this->_db_source = $source;
	}

	public function getDbSource()
	{
		return $this->_db_source;
	}

	public function __call($method, $args)
	{
		$type = substr($method, 0, 3);
		$field = strtolower(substr($method, 3));

		if($type == 'get')
		{
			if($this->offsetExists($field))
			{
				return $this->offsetGet($field);
			}
		} elseif($type == 'set')
		{
			if(isset($args[0]))
			{
				$this->offsetSet($field, $args[0]);
			}
		}
	}

	public function __set($key, $value)
	{
		return $this->offsetSet($key, $value);
	}

	public function __get($key)
	{
		return $this->offsetGet($key);
	}

	public function offsetGet($key)
	{
		if($key == $this->getModelName())
		{
			return $this;
		}

		if(!$this->offsetExists($key))
		{
			// TODO : fetch model associations
			throw new CakeException(__d('cake_dev', 'No association found - %s', $key));
		} else {
			return $this->_data[$key];
		}
	}

	public function offsetSet($key, $value)
	{
		$this->_data[$key] = $value;
	}

	public function offsetUnset($key)
	{
		unset($this->_data[$key]);
	}
	
	public function offsetExists($key)
	{
		if($key == $this->getModelName())
		{
			return !empty($this->_data);
		}

		return isset($this->_data[$key]);
	}
}
