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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Filesystem;
use Cake\Utility\Inflector;
use InvalidArgumentException;

/**
 * trait for symlinking / copying plugin assets to app's webroot.
 *
 * @internal
 */
trait PluginAssetsTrait
{
    /**
     * Arguments
     *
     * @var \Cake\Console\Arguments
     */
    protected Arguments $args;

    /**
     * Console IO
     *
     * @var \Cake\Console\ConsoleIoInterface
     */
    protected ConsoleIoInterface $io;

    /**
     * Get list of plugins to process. Plugins without a webroot directory are skipped.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return array<string, mixed> List of plugins with meta data.
     */
    protected function list(?string $name = null): array
    {
        if ($name === null) {
            $pluginsList = Plugin::loaded();
        } else {
            $pluginsList = [$name];
        }

        $plugins = [];

        foreach ($pluginsList as $plugin) {
            $path = Plugin::path($plugin) . 'webroot';
            if (!is_dir($path)) {
                $this->io->verbose('', 1);
                $this->io->verbose(
                    sprintf('Skipping plugin %s. It does not have webroot folder.', $plugin),
                    2,
                );
                continue;
            }

            $link = Inflector::underscore($plugin);
            $wwwRoot = Configure::read('App.wwwRoot');
            $dir = $wwwRoot;
            $namespaced = false;
            if (str_contains($link, '/')) {
                $namespaced = true;
                $parts = explode('/', $link);
                $link = array_pop($parts);
                $dir = $wwwRoot . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
            }

            $plugins[$plugin] = [
                'srcPath' => Plugin::path($plugin) . 'webroot',
                'destDir' => $dir,
                'link' => $link,
                'namespaced' => $namespaced,
            ];
        }

        return $plugins;
    }

    /**
     * Process plugins
     *
     * @param array<string, mixed> $plugins List of plugins to process
     * @param bool $copy Force copy mode. Default false.
     * @param bool $overwrite Overwrite existing files.
     * @param bool $relative Relative. Default false.
     * @return void
     */
    protected function process(
        array $plugins,
        bool $copy = false,
        bool $overwrite = false,
        bool $relative = false,
    ): void {
        foreach ($plugins as $plugin => $config) {
            $this->io->out();
            $this->io->out('For plugin: ' . $plugin);
            $this->io->hr();

            if (
                $config['namespaced'] &&
                !is_dir($config['destDir']) &&
                !$this->createDirectory($config['destDir'])
            ) {
                continue;
            }

            $dest = $config['destDir'] . $config['link'];

            if (file_exists($dest)) {
                if ($overwrite && !$this->remove($config)) {
                    continue;
                }
                if (!$overwrite) {
                    $this->io->verbose(
                        $dest . ' already exists',
                        1,
                    );
                    continue;
                }
            }

            if (!$copy) {
                $result = $this->createSymlink(
                    $config['srcPath'],
                    $dest,
                    $relative,
                );
                if ($result) {
                    continue;
                }
            }

            $this->copyDirectory(
                $config['srcPath'],
                $dest,
            );
        }

        $this->io->out();
        $this->io->out('Done');
    }

    /**
     * Remove folder/symlink.
     *
     * @param array<string, mixed> $config Plugin config.
     * @return bool
     */
    protected function remove(array $config): bool
    {
        if ($config['namespaced'] && !is_dir($config['destDir'])) {
            $this->io->verbose(
                $config['destDir'] . $config['link'] . ' does not exist',
                1,
            );

            return false;
        }

        $dest = $config['destDir'] . $config['link'];

        if (!file_exists($dest)) {
            $this->io->verbose(
                $dest . ' does not exist',
                1,
            );

            return false;
        }

        if (is_link($dest)) {
            // phpcs:ignore
            $success = DIRECTORY_SEPARATOR === '\\' ? @rmdir($dest) : @unlink($dest);
            if ($success) {
                $this->io->out('Unlinked ' . $dest);

                return true;
            }
            $this->io->err('Failed to unlink  ' . $dest);

            return false;
        }

        $fs = new Filesystem();
        if ($fs->deleteDir($dest)) {
            $this->io->out('Deleted ' . $dest);

            return true;
        }
        $this->io->err('Failed to delete ' . $dest);

        return false;
    }

    /**
     * Create directory
     *
     * @param string $dir Directory name
     * @return bool
     */
    protected function createDirectory(string $dir): bool
    {
        $old = umask(0);
        // phpcs:disable
        $result = @mkdir($dir, 0755, true);
        // phpcs:enable
        umask($old);

        if ($result) {
            $this->io->out('Created directory ' . $dir);

            return true;
        }

        $this->io->err('Failed creating directory ' . $dir);

        return false;
    }

    /**
     * Create symlink
     *
     * @param string $target Target directory
     * @param string $link Link name
     * @param bool $relative Relative (true) or Absolute (false)
     * @return bool
     */
    protected function createSymlink(string $target, string $link, bool $relative = false): bool
    {
        if ($relative) {
            $target = $this->makeRelativePath($link, $target);
        }

        // phpcs:disable
        $result = @symlink($target, $link);
        // phpcs:enable

        if ($result) {
            $this->io->out('Created symlink ' . $link);

            return true;
        }

        return false;
    }

    /**
     * Generate a relative path from one directory to another.
     *
     * @param string $from The symlink path
     * @param string $to The target path
     * @return string Relative path
     */
    protected function makeRelativePath(string $from, string $to): string
    {
        $from = is_dir($from) ? rtrim($from, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname($from);
        $from = realpath($from);
        $to = realpath($to);

        if ($from === false || $to === false) {
            throw new InvalidArgumentException('Invalid path provided to _makeRelativePath.');
        }

        $fromParts = explode(DIRECTORY_SEPARATOR, $from);
        $toParts = explode(DIRECTORY_SEPARATOR, $to);

        $fromCount = count($fromParts);
        $toCount = count($toParts);

        // Remove common parts
        while ($fromCount && $toCount && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
            $fromCount--;
            $toCount--;
        }

        return str_repeat('..' . DIRECTORY_SEPARATOR, $fromCount) . implode(DIRECTORY_SEPARATOR, $toParts);
    }

    /**
     * Copy directory
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return bool
     */
    protected function copyDirectory(string $source, string $destination): bool
    {
        $fs = new Filesystem();
        if ($fs->copyDir($source, $destination)) {
            $this->io->out('Copied assets to directory ' . $destination);

            return true;
        }

        $this->io->err('Error copying assets to directory ' . $destination);

        return false;
    }
}
