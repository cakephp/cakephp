<?php

class Record implements ArrayAccess
{
	protected $_data;

	protected $_db_source;

	protected $_model_name;

	public function setModelName($model)
	{
		$this->_model_name = $model;
	}

	public function getModel()
	{
		return ClassRegistry::init($this->getModelName());
	}

	public function getModelName()
	{
		return $this->_model_name;
	}

	public function getDataSource()
	{
		$model = $this->getModel();
		return ConnectionManager::getDataSource($model->useDbConfig);
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
				return $this->offsetSet($field, $args[0]);
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
		if($key == get_class($this->getModel()))
		{
			return $this;
		}

		if(!$this->offsetExists($key))
		{
			$model = $this->getModel();
			return $this->getDataSource()->getAssociatedData($model, $key, $this);
		} else {
			return $this->_data[$key];
		}
	}

	public function offsetSet($key, $value)
	{
		$this->_data[$key] = $value;

		return $this;
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

	public function save()
	{
		return $this->getModel()->save($this->_data);
	}

	public function delete()
	{
		return $this->getModel()->delete($this->_data);
	}
}
