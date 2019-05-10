<?php
declare(strict_types=1);

namespace TestPlugin\Datasource;

class TestSource
{
    /**
     * Config
     *
     * @var array
     */
    protected $_config;

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * config
     *
     * @return array
     */
    public function config()
    {
        return $this->_config;
    }
}
