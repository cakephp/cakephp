<?php
declare(strict_types=1);

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
namespace Cake\Console\Command;

use ArrayIterator;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use SimpleXMLElement;

/**
 * Print out command list
 */
class HelpCommand extends BaseCommand implements CommandCollectionAwareInterface
{
    /**
     * The command collection to get help on.
     *
     * @var \Cake\Console\CommandCollection
     */
    protected CommandCollection $commands;

    /**
     * @inheritDoc
     */
    public function setCommandCollection(CommandCollection $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * Main function Prints out the list of commands.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $commands = $this->commands->getIterator();
        if ($commands instanceof ArrayIterator) {
            $commands->ksort();
        }

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
     * @param iterable<string, string|object> $commands The command collection to output.
     * @return void
     */
    protected function asText(ConsoleIo $io, iterable $commands): void
    {
        $invert = [];
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = $class::class;
            }
            $invert[$class] ??= [];
            $invert[$class][] = $name;
        }
        $grouped = [];
        $plugins = Plugin::loaded();
        foreach ($invert as $class => $names) {
            preg_match('/^(.+)\\\\Command\\\\/', $class, $matches);
            // Probably not a useful class
            if (!$matches) {
                continue;
            }
            $namespace = str_replace('\\', '/', $matches[1]);
            $prefix = 'app';
            if ($namespace === 'Cake') {
                $prefix = 'cakephp';
            } elseif (in_array($namespace, $plugins, true)) {
                $prefix = Inflector::underscore($namespace);
            }
            $shortestName = $this->getShortestName($names);
            if (str_contains($shortestName, '.')) {
                [, $shortestName] = explode('.', $shortestName, 2);
            }

            $grouped[$prefix][] = [
                'name' => $shortestName,
                'description' => is_subclass_of($class, BaseCommand::class) ? $class::getDescription() : '',
            ];
        }
        ksort($grouped);

        if (isset($grouped['CakePHP'])) {
            $cakephp = $grouped['CakePHP'];
            $grouped = ['CakePHP' => $cakephp] + $grouped;
        }

        if (isset($grouped['App'])) {
            $app = $grouped['App'];
            $grouped = ['App' => $app] + $grouped;
        }

        $this->outputPaths($io);
        $io->out('<info>Available Commands:</info>', 2);

        foreach ($grouped as $prefix => $names) {
            $io->out("<info>{$prefix}</info>:");
            sort($names);
            foreach ($names as $data) {
                $io->out(' - ' . $data['name']);
                if ($data['description']) {
                    $io->info(str_pad(" \u{2514}", 13, "\u{2500}") . ' ' . $data['description']);
                }
            }
            $io->out('');
        }
        $root = $this->getRootName();

        $io->out("To run a command, type <info>`{$root} command_name [args|options]`</info>");
        $io->out("To get help on a specific command, type <info>`{$root} command_name --help`</info>", 2);
    }

    /**
     * Output relevant paths if defined
     *
     * @param \Cake\Console\ConsoleIo $io IO object.
     * @return void
     */
    protected function outputPaths(ConsoleIo $io): void
    {
        $paths = [];
        if (Configure::check('App.dir')) {
            $appPath = rtrim(Configure::read('App.dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            // Extra space is to align output
            $paths['app'] = ' ' . $appPath;
        }
        if (defined('ROOT')) {
            $paths['root'] = rtrim(ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        if (defined('CORE_PATH')) {
            $paths['core'] = rtrim(CORE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        if ($paths === []) {
            return;
        }
        $io->out('<info>Current Paths:</info>', 2);
        foreach ($paths as $key => $value) {
            $io->out("* {$key}: {$value}");
        }
        $io->out('');
    }

    /**
     * @param array<string> $names Names
     * @return string
     * @phpstan-param non-empty-array<string> $names
     */
    protected function getShortestName(array $names): string
    {
        usort($names, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

        return array_shift($names);
    }

    /**
     * Output as XML
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param iterable<string, string|object> $commands The command collection to output
     * @return void
     */
    protected function asXml(ConsoleIo $io, iterable $commands): void
    {
        $shells = new SimpleXMLElement('<shells></shells>');
        foreach ($commands as $name => $class) {
            if (is_object($class)) {
                $class = $class::class;
            }
            $shell = $shells->addChild('shell');
            $shell->addAttribute('name', $name);
            $shell->addAttribute('call_as', $name);
            $shell->addAttribute('provider', $class);
            $shell->addAttribute('help', $name . ' -h');
        }
        $io->setOutputAs(ConsoleOutput::RAW);
        $io->out((string)$shells->saveXML());
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Get the list of available commands for this application.',
        )->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true,
        ]);

        return $parser;
    }
}
