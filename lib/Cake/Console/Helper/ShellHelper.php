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
     * Constructor.
     *
     * @param ConsoleOutput $consoleOutput The ConsoleOutput instance to use.
     * @param array $config The settings for this helper.
     */
    public function __construct(ConsoleOutput $consoleOutput)
    {
        $this->_consoleOutput = $consoleOutput;
    }

    /**
     * This method should output content using `$this->_consoleOutput`.
     *
     * @param array $args The arguments for the helper.
     * @return void
     */
    abstract public function output($args);
}