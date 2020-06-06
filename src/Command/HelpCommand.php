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
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Utility\Inflector;
use SimpleXMLElement;

/**
 * Print out command list
 */
class HelpCommand extends Command implements CommandCollectionAwareInterface
{
    /**
     * The command collection to get help on.
     *
     * @var \Cake\Console\CommandCollection
     */
    protected $commands;

    /**
     * {@inheritDoc}
     */
    public function setCommandCollection(CommandCollection $commands)
    {
        $this->commands = $commands;
    }

    /**
     * Main function Prints out the list of commands.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        if (!$args->getOption('xml')) {
            $io->out('<info>Current Paths:</info>', 2);
            $io->out('* app:  ' . APP_DIR . DIRECTORY_SEPARATOR);
            $io->out('* root: ' . ROOT . DIRECTORY_SEPARATOR);
            $io->out('* core: ' . CORE_PATH);
            $io->out('');

            $io->out('<info>Available Commands:</info>', 2);
        }

        $commands = $this->commands->getIterator();
        $commands->ksort();

        if ($args->getOption('xml')) {
            $this->asXml($io, $commands);

            return static::CODE_SUCCESS;
        }

        $this->asText($io, $commands);

        return static::CODE_SUCCESS;
    }

    /**
     * Output text.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param \ArrayIterator $commands The command collection to output.
     * @return void
     */
    protected function asText($io, $commands)
    {
        $invert = [];
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = get_class($class);
            }
            if (!isset($invert[$class])) {
                $invert[$class] = [];
            }
            $invert[$class][] = $name;
        }

        $prefixed = [];
        foreach ($invert as $class => $names) {
            $prefixedName = $this->findPrefixedName($names);
            if (!$prefixedName) {
                $prefix = preg_match('#^Cake\\\\(Command|Shell)\\\\#', $class) ? '[core]' : '[app]';
                $prefixed[$prefix][] = $this->getShortestName($names);
                continue;
            }

            list ($prefix, $name) = explode('.', $prefixedName, 2);
            $prefix = Inflector::camelize($prefix);

            $shortestName = $this->getShortestName($names);
            if (strpos($shortestName, '.') !== false) {
                list (, $shortestName) = explode('.', $shortestName, 2);
            }

            $prefixed[$prefix][] = $shortestName;
        }

        ksort($prefixed);

        foreach ($prefixed as $prefix => $names) {
            $io->out($prefix . ':');
            sort($names);
            foreach ($names as $name) {
                $io->out(' - ' . $name);
            }
        }
        $io->out('');

        $io->out('To run a command, type <info>`cake command_name [args|options]`</info>');
        $io->out('To get help on a specific command, type <info>`cake command_name --help`</info>', 2);
    }

    /**
     * @param string[] $names Names
     * @return string|null
     */
    protected function findPrefixedName(array $names)
    {
        foreach ($names as $name) {
            if (strpos($name, '.') !== false) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param string[] $names Names
     * @return string
     */
    protected function getShortestName(array $names)
    {
        if (count($names) <= 1) {
            return array_shift($names);
        }

        usort($names, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

        return array_shift($names);
    }

    /**
     * Output as XML
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param \ArrayIterator $commands The command collection to output
     * @return void
     */
    protected function asXml($io, $commands)
    {
        $shells = new SimpleXMLElement('<shells></shells>');
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = get_class($class);
            }
            $shell = $shells->addChild('shell');
            $shell->addAttribute('name', $name);
            $shell->addAttribute('call_as', $name);
            $shell->addAttribute('provider', $class);
            $shell->addAttribute('help', $name . ' -h');
        }
        $io->setOutputAs(ConsoleOutput::RAW);
        $io->out($shells->saveXML());
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->setDescription(
            'Get the list of available commands for this application.'
        )->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true,
        ]);

        return $parser;
    }
}
