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
use Cake\Console\CommandHiddenInterface;
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

        // Filter by command prefix if provided
        $filter = $args->getArgument('command');
        if ($filter) {
            $commands = $this->filterByPrefix($commands, $filter);
        }

        if ($args->getOption('xml')) {
            $this->asXml($io, $commands);

            return static::CODE_SUCCESS;
        }

        $verbose = $io->level() >= ConsoleIo::VERBOSE;
        $this->asText($io, $commands, $verbose);

        return static::CODE_SUCCESS;
    }

    /**
     * Filter commands by prefix.
     *
     * @param iterable<string, string|object> $commands The command collection.
     * @param string $prefix The prefix to filter by.
     * @return array<string, string|object> Filtered commands.
     */
    protected function filterByPrefix(iterable $commands, string $prefix): array
    {
        $filtered = [];
        foreach ($commands as $name => $class) {
            if (str_starts_with($name, $prefix . ' ') || $name === $prefix) {
                $filtered[$name] = $class;
            }
        }

        return $filtered;
    }

    /**
     * Output text.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param iterable<string, string|object> $commands The command collection to output.
     * @param bool $verbose Whether to show verbose output with descriptions.
     * @return void
     */
    protected function asText(ConsoleIo $io, iterable $commands, bool $verbose = false): void
    {
        $invert = [];
        foreach ($commands as $name => $class) {
            // Skip hidden commands
            if (is_subclass_of($class, CommandHiddenInterface::class)) {
                continue;
            }
            if (is_object($class)) {
                $class = $class::class;
            }
            $invert[$class] ??= [];
            $invert[$class][] = $name;
        }

        $commandList = [];
        foreach ($invert as $class => $names) {
            preg_match('/^(.+)\\\\Command\\\\/', $class, $matches);
            // Probably not a useful class
            if (!$matches) {
                continue;
            }
            $shortestName = $this->getShortestName($names);
            if (str_contains($shortestName, '.')) {
                [, $shortestName] = explode('.', $shortestName, 2);
            }

            $commandList[] = [
                'name' => $shortestName,
                'description' => is_subclass_of($class, BaseCommand::class) ? $class::getDescription() : '',
            ];
        }
        sort($commandList);

        if ($verbose) {
            $version = Configure::version();
            $debug = Configure::read('debug') ? 'true' : 'false';
            $io->out("<info>CakePHP:</info> {$version} (debug: {$debug})", 2);
            $this->outputPaths($io);
            $this->outputGrouped($io, $invert);
        } else {
            $this->outputCompactCommands($io, $commandList);
            $io->out('');
        }

        $root = $this->getRootName();
        $io->out("To run a command, type <info>`{$root} command_name [args|options]`</info>");
        $io->out("To get help on a specific command, type <info>`{$root} command_name --help`</info>");
        if (!$verbose) {
            $io->out("To see full descriptions and plugin grouping, use <info>`{$root} --help -v`</info>", 2);
        } else {
            $io->out('', 2);
        }
    }

    /**
     * Output commands grouped by plugin/namespace (verbose mode).
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param array<string, array<string>> $invert Inverted command map (class => names).
     * @return void
     */
    protected function outputGrouped(ConsoleIo $io, array $invert): void
    {
        $grouped = [];
        $plugins = Plugin::loaded();
        foreach ($invert as $class => $names) {
            preg_match('/^(.+)\\\\Command\\\\/', $class, $matches);
            if (!$matches || $names === []) {
                continue;
            }
            $namespace = str_replace('\\', '/', $matches[1]);
            $prefix = 'app';
            if ($namespace === 'Cake') {
                $prefix = 'cakephp';
            } elseif (method_exists($class, 'getGroup')) {
                $prefix = $class::getGroup();
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

        if (isset($grouped['app'])) {
            $app = $grouped['app'];
            unset($grouped['app']);
            $grouped = ['app' => $app] + $grouped;
        }

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
    }

    /**
     * Output commands with inline descriptions, grouped by prefix.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param array<array{name: string, description: string}> $commands List of commands with names and descriptions.
     * @return void
     */
    protected function outputCompactCommands(ConsoleIo $io, array $commands): void
    {
        $maxWidth = $this->getTerminalWidth();

        // Group commands by their first word (prefix)
        $groups = [];
        foreach ($commands as $data) {
            $parts = explode(' ', $data['name'], 2);
            $prefix = $parts[0];
            $subcommand = $parts[1] ?? null;

            $groups[$prefix] ??= [];
            $groups[$prefix][] = [
                'subcommand' => $subcommand,
                'description' => $data['description'],
            ];
        }

        // Separate single commands from grouped commands
        $singleCommands = [];
        $groupedCommands = [];

        foreach ($groups as $prefix => $cmds) {
            if (count($cmds) === 1 && $cmds[0]['subcommand'] === null) {
                $singleCommands[$prefix] = $cmds[0];
            } else {
                $groupedCommands[$prefix] = $cmds;
            }
        }

        // Find the longest full command name for padding
        $maxNameLength = 0;
        foreach ($commands as $data) {
            $maxNameLength = max($maxNameLength, strlen($data['name']));
        }
        $nameColumnWidth = $maxNameLength + 3;

        // Output single commands under "Available Commands:" header
        $isFirst = true;
        if ($singleCommands !== []) {
            $io->out('<info>Available Commands:</info>');
            foreach ($singleCommands as $prefix => $cmd) {
                $description = $cmd['description'];
                $linePrefix = '  ' . str_pad($prefix, $nameColumnWidth - 2);

                if ($description !== '') {
                    $description = strtok($description, "\n");
                    $this->outputWrappedLine($io, $linePrefix, $description, $maxWidth);
                } else {
                    $io->out($linePrefix);
                }
            }
            $isFirst = false;
        }

        // Output grouped commands with headers
        foreach ($groupedCommands as $prefix => $cmds) {
            if (!$isFirst) {
                $io->out('');
            }
            $io->out("<info>{$prefix}:</info>");

            foreach ($cmds as $cmd) {
                $fullName = $cmd['subcommand'] !== null ? $prefix . ' ' . $cmd['subcommand'] : $prefix;
                $description = $cmd['description'];

                $linePrefix = '  ' . str_pad($fullName, $nameColumnWidth - 2);

                if ($description !== '') {
                    $description = strtok($description, "\n");
                    $this->outputWrappedLine($io, $linePrefix, $description, $maxWidth);
                } else {
                    $io->out($linePrefix);
                }
            }
            $isFirst = false;
        }
    }

    /**
     * Output a line with description, wrapping based on terminal width.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param string $prefix The line prefix (command name with padding)
     * @param string $description The description text
     * @param int $maxWidth Maximum terminal width
     * @param int $maxChars Maximum total description characters (0 = unlimited)
     * @return void
     */
    protected function outputWrappedLine(
        ConsoleIo $io,
        string $prefix,
        string $description,
        int $maxWidth,
        int $maxChars = 200,
    ): void {
        $availableWidth = $maxWidth - strlen($prefix);

        if ($availableWidth <= 10) {
            $io->out($prefix);

            return;
        }

        // Truncate description to max chars if set
        if ($maxChars > 0 && strlen($description) > $maxChars) {
            $description = substr($description, 0, $maxChars - 3) . '...';
        }

        if (strlen($description) <= $availableWidth) {
            $io->out($prefix . $description);

            return;
        }

        // Wrap description across multiple lines
        $indent = str_repeat(' ', strlen($prefix));
        $remaining = $description;
        $firstLine = true;

        while ($remaining !== '') {
            $linePrefix = $firstLine ? $prefix : $indent;
            $firstLine = false;

            if (strlen($remaining) <= $availableWidth) {
                $io->out($linePrefix . $remaining);
                break;
            }

            // Find word break point
            $breakPoint = strrpos(substr($remaining, 0, $availableWidth), ' ');
            if ($breakPoint === false || $breakPoint < $availableWidth / 2) {
                $breakPoint = $availableWidth;
            }

            $io->out($linePrefix . substr($remaining, 0, $breakPoint));
            $remaining = ltrim(substr($remaining, $breakPoint));
        }
    }

    /**
     * Get terminal width for line wrapping.
     *
     * @return int Terminal width in columns
     */
    protected function getTerminalWidth(): int
    {
        // Check COLUMNS environment variable (commonly set by shells)
        $columns = getenv('COLUMNS');
        if ($columns !== false && is_numeric($columns) && (int)$columns > 0) {
            return (int)$columns;
        }

        // Try tput cols (Unix/Linux/macOS)
        if (str_contains(strtolower(PHP_OS), 'win') === false) {
            $result = null;
            $output = exec('tput cols 2>/dev/null', result_code: $result);
            if ($result === 0 && is_numeric($output) && (int)$output > 0) {
                return (int)$output;
            }

            // Try stty size (returns "rows cols")
            $output = exec('stty size 2>/dev/null', result_code: $result);
            if ($result === 0 && $output !== false && preg_match('/^\d+\s+(\d+)$/', $output, $matches)) {
                return (int)$matches[1];
            }
        }

        // Default to 120 columns (modern terminals)
        return 120;
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
            // Skip hidden commands
            if (is_subclass_of($class, CommandHiddenInterface::class)) {
                continue;
            }
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
        )->addArgument('command', [
            'help' => 'Filter commands by prefix (e.g., "cache" to show only cache commands).',
        ])->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true,
        ]);

        return $parser;
    }
}
