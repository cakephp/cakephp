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
 * @since         4.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Preload;

use Cake\Filesystem\Filesystem;
use Cake\I18n\FrozenTime;
use Cake\Preload\Exception\ResourceNotFoundException;
use Cake\Utility\Inflector;
use Iterator;
use RuntimeException;
use SplFileInfo;

/**
 * Writes a preload file
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class Preloader
{
    /**
     * An array of PreloadResource instances
     *
     * @var PreloadResource[]
     */
    private $_preloadResources = [];

    /**
     * Returns an array of PreloadResource after sorting alphabetically
     *
     * @return PreloadResource[]
     */
    public function getPreloadResources(): array
    {
        uasort($this->_preloadResources, function (PreloadResource $a, PreloadResource $b) {
            return strcasecmp($a->getFile(), $b->getFile());
        });

        return $this->_preloadResources;
    }

    /**
     * Loads files in the file system $path recursively as PreloadResources after applying the optional callback
     *
     * @param string The file system path
     * @param callable|null $callback An optional callback which receives SplFileInfo as an argument
     * @return $this
     */
    public function loadPath(string $path, $callback = null)
    {
        $iterator = (new Filesystem())->findRecursive(
            $path,
            function (SplFileInfo $file) use ($callback) {
                if (is_callable($callback)) {
                    return $callback($file);
                }
                return true;
            }
        );

        $this->append($iterator);

        return $this;
    }

    /**
     * Appends an \Iterator of SplFileInfo to self::_preloadResources. The result of FileSystem()->findRecursive,
     * or an Iterator of SplFileInfo (such as \Cake\Collection\Collection) may be passed as an argument.
     *
     * Files must have the php extension and not be in a /tests/ folder. CamelCase files will be treated as classes
     * and loaded via require_once. All other files will be loaded with opcache_compile_file().
     *
     * @param \Iterator $iterator An object implementing Iterator
     */
    public function append(Iterator $iterator): void
    {
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || strstr($file->getPath(), '/tests/')) {
                continue;
            }

            if (Inflector::camelize($file->getFilename()) === $file->getFilename()) {
                $this->_preloadResources[] = new PreloadResource('require_once', $file->getPathname());
                continue;
            }

            $this->_preloadResources[] = new PreloadResource('opcache_compile_file', $file->getPathname());
        }
    }

    /**
     * Write preloader to the specified path
     *
     * @param string|null $path Default file path is ROOT . 'preload.php'
     * @return bool
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function write(string $path = ROOT . DS . 'preload.php'): bool
    {
        if (!function_exists('opcache_get_status')) {
            throw new RuntimeException('OPcache must be enabled');
        }

        if ((file_exists($path) && !is_writable($path)) || !is_writable(ROOT)) {
            throw new RuntimeException('OPcache must be enabled');
        }

        return (bool)file_put_contents($path, $this->contents());
    }

    /**
     * Returns a string to be written to the preload file
     *
     * @return string
     */
    private function contents(): string
    {
        ob_start();

        $title = sprintf("# Preload Generated at %s \n", FrozenTime::now());

        echo "<?php\n\n";
        echo "$title \n";
        echo "if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {\n";
        echo "\treturn;\n";
        echo "}\n\n";
        echo "require_once('" . ROOT . DS . 'vendor' . DS . 'autoload.php' . "'); \n";

        $scripts = [];

        foreach ($this->getPreloadResources() as $resource) {
            try {
                if ($resource->getType() === 'require_once') {
                    echo $resource->getResource();
                    continue;
                }
                $scripts[] = $resource->getResource();
            } catch (ResourceNotFoundException $e) {
                triggerWarning('Preloader skipped the following: ' . $e->getMessage());
            }
        }

        if (!empty($scripts)) {
            echo "# Scripts \n";
            echo implode('', $scripts ?? []);
        }

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
