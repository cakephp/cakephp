<?php

abstract class ShellHelper
{
    /**
     * Default config for this helper.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * ConsoleOutput instance.
     *
     * @var ConsoleOutput
     */
    protected $_consoleOutput;

    /**
     * Runtime config
     *
     * @var array
     */
    protected $_config = [];
    /**
     * Whether the config property has already been configured with defaults
     *
     * @var bool
     */
    protected $_configInitialized = false;

    /**
     * Constructor.
     *
     * @param ConsoleOutput $consoleOutput The ConsoleOutput instance to use.
     * @param array $config The settings for this helper.
     */
    public function __construct(ConsoleOutput $consoleOutput, array $config = array())
    {
        $this->_consoleOutput = $consoleOutput;
        $this->config($config);
    }

    public function config($config = null)
    {
        if ($config === null) {
            return $this->_config;
        }
        if (!$this->_configInitialized) {
            $this->_config = array_merge($this->_defaultConfig, $config);
            $this->_configInitialized = true;
        } else {
            $this->_config = array_merge($this->_config, $config);
        }
    }

    /**
     * This method should output content using `$this->_consoleOutput`.
     *
     * @param array $args The arguments for the helper.
     * @return void
     */
    abstract public function output($args);
}