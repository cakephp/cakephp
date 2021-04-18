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
     * @param array $options An array of options
     */
    public function __construct(array $options)
    {
        $options = array_merge([
            'regex' => '/^(((?!\/tests\/).)+\.php$)*$/i',
            'src' => false,
            'plugins' => [],
        ], $options);

        $this->regex = $options['regex'];
        $this->src = $options['src'];
        $this->plugins = $options['plugins'];
    }

    /**
     * Write preloader to the specified path after executing an optional callback
     *
     * @param string $path The file to write the preloader to
     * @param array $files An initial list of files to add to the preloader
     * @param mixed|null $callback A callback to execute on the list of files to be preloaded
     * @return bool
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function write(string $path, array $files = [], $callback = null): bool
    {
        $this->errorHandler($path);

        $files = $this->fileList($files);

        if (is_callable($callback)) {
            $files = $callback($files);
        }

        return (bool)file_put_contents($path, $this->contents($files));
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
     * @param array $files The list of seed files
     * @return array
     */
    private function fileList(array $files = []): array
    {
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
     * @param string $path The file output path
     * @return void
     * @throws \RuntimeException
     */
    private function errorHandler(string $path): void
    {
        if (!function_exists('opcache_get_status')) {
            throw new RuntimeException('OPcache must be enabled');
        }

        if ((file_exists($path) && !is_writable($path)) || !is_writable(APP)) {
            throw new RuntimeException('OPcache must be enabled');
        }
    }
}
