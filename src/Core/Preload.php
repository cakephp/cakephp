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
namespace Cake\Core;

use Cake\I18n\FrozenTime;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use RuntimeException;

/**
 * Writes a preload file
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class Preload
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string|null
     */
    private $regex;

    /**
     * @var bool
     */
    private $src;

    /**
     * @var array;
     */
    private $plugins;

    /**
     * @var array;
     */
    private $files;

    /**
     * @param array $options An array of options
     */
    public function __construct(array $options = [])
    {
        $options = array_merge([
            'path' => ROOT . DS . 'preload.php',
            'regex' => '/^(((?!\/tests\/).)+\.php$)*$/i',
            'src' => false,
            'plugins' => [],
            'files' => [],
        ], $options);

        $this->path = $options['path'];
        $this->regex = $options['regex'];
        $this->src = $options['src'];
        $this->plugins = $options['plugins'];
        $this->files = $options['files'];
    }

    /**
     * Write preloader to the specified path after executing an optional callback. If no path is specified
     *
     * @param mixed|null $callback A callback to execute on the list of files to be preloaded
     * @return bool
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function write($callback = null): bool
    {
        $this->errorHandler();

        $files = $this->fileList();

        if (is_callable($callback)) {
            $files = $callback($files);
        }

        return (bool)file_put_contents($this->path, $this->contents($files));
    }

    /**
     * Returns array of files after filtering out some default file patterns
     *
     * @param array $files The array of files to apply the filters to
     * @return array
     */
    public function filter(array $files): array
    {
        $matches = [
            '\/vendor\/cakephp\/cakephp\/src\/TestSuite\/',
            '\/vendor\/cakephp\/cakephp\/src\/Console\/',
            '\/vendor\/cakephp\/cakephp\/src\/Command\/',
            '\/vendor\/cakephp\/cakephp\/src\/Shell\/',
        ];

        $paths = implode('|', $matches);
        $patterns = "/^(((?!$paths).)+\.php$)*$/";

        return array_filter($files, function ($file) use ($patterns) {
            return preg_match($patterns, $file) && $file !== __FILE__;
        });
    }

    /**
     * Returns a flat array of all files in the directory path.
     *
     * @param string $directory The directory path
     * @return string[]
     */
    private function findFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new LogicException("Invalid directory path: $directory");
        }

        $directory = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator(
            $iterator,
            $this->regex,
            RecursiveRegexIterator::ALL_MATCHES
        );

        $files = [];
        foreach ($regex as $r) {
            if (isset($r[0][0])) {
                $files[] = $r[0][0];
            }
        }

        return $files;
    }

    /**
     * Returns a list of files containing at least all CAKE core classes and optionally plugins and APP src
     *
     * @return array
     */
    private function fileList(): array
    {
        $files = $this->files;
        $files = array_merge($files, $this->findFiles(CAKE));

        if (!empty($this->plugins)) {
            foreach (Plugin::getCollection() as $name => $plugin) {
                if (in_array($name, $this->plugins)) {
                    $files = array_merge($files, $this->findFiles($plugin->getPath()));
                }
            }
        }

        if ($this->src) {
            $files = array_merge($files, $this->findFiles(APP));
        }

        return array_unique($files);
    }

    /**
     * Returns a string to be written to the preload file
     *
     * @param array $files The array of files to output to the preload file
     * @return string
     */
    private function contents(array $files): string
    {
        ob_start();
        echo "<?php\n";
        echo '# Preload generated at ' . FrozenTime::now() . "\n";
        echo "require_once('" . ROOT . DS . 'vendor' . DS . 'autoload.php' . "'); \n";
        foreach ($files as $file) {
            echo "require_once('$file'); \n";
        }
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    private function errorHandler(): void
    {
        if (!function_exists('opcache_get_status')) {
            throw new RuntimeException('OPcache must be enabled');
        }

        if ((file_exists($this->path) && !is_writable($this->path)) || !is_writable(APP)) {
            throw new RuntimeException('OPcache must be enabled');
        }
    }
}
