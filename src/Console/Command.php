<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Datasource\ModelAwareTrait;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

/**
 * Base class for console commands.
 */
class Command
{
    use LocatorAwareTrait;
    use LogTrait;
    use ModelAwareTrait;

    /**
     * The name of this command. Inflected from the class name.
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor
     *
     * By default CakePHP will construct command objects when
     * building the CommandCollection for your application.
     */
    public function __construct()
    {
        $locator = $this->getTableLocator() ? : 'Cake\ORM\TableRegistry';
        $this->modelFactory('Table', [$locator, 'get']);
    }

    /**
     * Set the name this command uses in the collection.
     *
     * Generally invoked by the CommandCollection when the command is added.
     *
     * @param string $name The name the command uses in the collection.
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the command name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the option parser.
     *
     * You can override buildOptionParser() to define your options & arguments.
     *
     * @return \Cake\Console\ConsoleOptionParser
     * @throws \RuntimeException When the parser is invalid
     */
    public function getOptionParser()
    {
        $parser = new ConsoleOptionParser($this->name);
        $parser = $this->buildOptionParser($parser);
        if (!($parser instanceof ConsoleOptionParser)) {
            throw new RuntimeException(sprintf(
                "Invalid option parser returned from buildOptionParser(). Expected %s, got %s",
                ConsoleOptionParser::class,
                get_class($parser)
            ));
        }

        return $parser;
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        return $parser;
    }

    /**
     * Hook method invoked by CakePHP when a command is about to be executed.
     *
     * Override this method and implement expensive/important setup steps that
     * should not run on every command run. This method will be called *before*
     * the options and arguments are validated and processed.
     *
     * @return void
     */
    public function initialize()
    {
    }
}
