<?php
declare(strict_types=1);

namespace TestApp\Datasource;

class FakeConnection
{
    protected $_config = [];

    /**
     * Constructor.
     *
     * @param array $config configuration for connecting to database
     */
    public function __construct($config = [])
    {
        $this->_config = $config;
    }

    /**
     * Returns the set config
     *
     * @return array
     */
    public function config()
    {
        return $this->_config;
    }

    /**
     * Returns the set name
     *
     * @return string
     */
    public function configName()
    {
        if (empty($this->_config['name'])) {
            return '';
        }

        return $this->_config['name'];
    }
}
