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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Filesystem;
use Cake\Utility\Inflector;
use ReflectionClass;

/**
 * Used by CommandCollection and CommandTask to scan the filesystem
 * for command classes.
 *
 * @internal
 */
class CommandScanner
{
    /**
     * Scan CakePHP internals for shells & commands.
     *
     * @return array A list of command metadata.
     */
    public function scanCore(): array
    {
        return $this->scanDir(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR,
            'Cake\Command\\',
            '',
            ['command_list']
        );
    }

    /**
     * Scan the application for shells & commands.
     *
     * @return array A list of command metadata.
     */
    public function scanApp(): array
    {
        $appNamespace = Configure::read('App.namespace');

        return $this->scanDir(
            App::classPath('Command')[0],
            $appNamespace . '\Command\\',
            '',
            []
        );
    }

    /**
     * Scan the named plugin for shells and commands
     *
     * @param string $plugin The named plugin.
     * @return array A list of command metadata.
     */
    public function scanPlugin(string $plugin): array
    {
        if (!Plugin::isLoaded($plugin)) {
            return [];
        }
        $path = Plugin::classPath($plugin);
        $namespace = str_replace('/', '\\', $plugin);
        $prefix = Inflector::underscore($plugin) . '.';

        return $this->scanDir($path . 'Command', $namespace . '\Command\\', $prefix, []);
    }

    /**
     * Scan a directory for .php files and return the class names that
     * should be within them.
     *
     * @param string $path The directory to read.
     * @param string $namespace The namespace the shells live in.
     * @param string $prefix The prefix to apply to commands for their full name.
     * @param list<string> $hide A list of command names to hide as they are internal commands.
     * @return array The list of shell info arrays based on scanning the filesystem and inflection.
     */
    protected function scanDir(string $path, string $namespace, string $prefix, array $hide): array
    {
        if (!is_dir($path)) {
            return [];
        }

        // This ensures `Command` class is not added to the list.
        $hide[] = '';

        $classPattern = '/Command\.php$/';
        $fs = new Filesystem();
        /** @var array<\SplFileInfo> $files */
        $files = $fs->find($path, $classPattern);

        $commands = [];
        foreach ($files as $fileInfo) {
            $file = $fileInfo->getFilename();

            $name = Inflector::underscore((string)preg_replace($classPattern, '', $file));
            if (in_array($name, $hide, true)) {
                continue;
            }

            $class = $namespace . $fileInfo->getBasename('.php');
            if (!is_subclass_of($class, CommandInterface::class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            if (is_subclass_of($class, BaseCommand::class)) {
                $name = $class::defaultName();
            }
            $commands[$path . $file] = [
                'file' => $path . $file,
                'fullName' => $prefix . $name,
                'name' => $name,
                'class' => $class,
            ];
        }

        ksort($commands);

        return array_values($commands);
    }
}
