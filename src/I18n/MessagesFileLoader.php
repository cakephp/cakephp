<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\Package;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use Locale;
use RuntimeException;

/**
 * A generic translations package factory that will load translations files
 * based on the file extension and the package name.
 *
 * This class is a callable, so it can be used as a package loader argument.
 */
class MessagesFileLoader
{

    /**
     * The package (domain) name.
     *
     * @var string
     */
    protected $_name;

    /**
     * The locale to load for the given package.
     *
     * @var string
     */
    protected $_locale;

    /**
     * The extension name.
     *
     * @var string
     */
    protected $_extension;

    /**
     * Creates a translation file loader. The file to be loaded corresponds to
     * the following rules:
     *
     * - The locale is a folder under the `Locale` directory, a fallback will be
     *   used if the folder is not found.
     * - The $name corresponds to the file name to load
     * - If there is a loaded plugin with the underscored version of $name, the
     *   translation file will be loaded from such plugin.
     *
     * ### Examples:
     *
     * Load and parse src/Locale/fr/validation.po
     *
     * ```
     * $loader = new MessagesFileLoader('validation', 'fr_FR', 'po');
     * $package = $loader();
     * ```
     *
     * Load and parse  src/Locale/fr_FR/validation.mo
     *
     * ```
     * $loader = new MessagesFileLoader('validation', 'fr_FR', 'mo');
     * $package = $loader();
     * ```
     *
     * Load the plugins/MyPlugin/src/Locale/fr/my_plugin.po file:
     *
     * ```
     * $loader = new MessagesFileLoader('my_plugin', 'fr_FR', 'mo');
     * $package = $loader();
     * ```
     *
     * @param string $name The name (domain) of the translations package.
     * @param string $locale The locale to load, this will be mapped to a folder
     * in the system.
     * @param string $extension The file extension to use. This will also be mapped
     * to a messages parser class.
     */
    public function __construct($name, $locale, $extension = 'po')
    {
        $this->_name = $name;
        $this->_locale = $locale;
        $this->_extension = $extension;
    }

    /**
     * Loads the translation file and parses it. Returns an instance of a translations
     * package containing the messages loaded from the file.
     *
     * @return \Aura\Intl\Package
     * @throws \RuntimeException if no file parser class could be found for the specified
     * file extension.
     */
    public function __invoke()
    {
        $package = new Package('default');
        $folders = $this->translationsFolders();
        $ext = $this->_extension;
        $file = false;

        $fileName = $this->_name;
        $pos = strpos($fileName, '/');
        if ($pos !== false) {
            $fileName = substr($fileName, $pos + 1);
        }
        foreach ($folders as $folder) {
            $path = $folder . $fileName . ".$ext";
            if (is_file($path)) {
                $file = $path;
                break;
            }
        }

        if (!$file) {
            return $package;
        }

        $name = ucfirst($ext);
        $class = App::classname($name, 'I18n\Parser', 'FileParser');

        if (!$class) {
            throw new RuntimeException(sprintf('Could not find class %s', "{$name}FileParser"));
        }

        $messages = (new $class)->parse($file);
        $package->setMessages($messages);
        return $package;
    }

    /**
     * Returns the folders where the file should be looked for according to the locale
     * and package name.
     *
     * @return array The list of folders where the translation file should be looked for
     */
    public function translationsFolders()
    {
        $locale = Locale::parseLocale($this->_locale) + ['region' => null];

        $folders = [
            implode('_', [$locale['language'], $locale['region']]),
            $locale['language']
        ];

        $searchPaths = [];

        $localePaths = App::path('Locale');
        if (empty($localePaths)) {
            $localePaths[] = APP . 'Locale' . DIRECTORY_SEPARATOR;
        }
        foreach ($localePaths as $path) {
            foreach ($folders as $folder) {
                $searchPaths[] = $path . $folder . DIRECTORY_SEPARATOR;
            }
        }

        // If space is not added after slash, the character after it remains lowercased
        $pluginName = Inflector::camelize(str_replace('/', '/ ', $this->_name));
        if (Plugin::loaded($pluginName)) {
            $basePath = Plugin::classPath($pluginName) . 'Locale' . DIRECTORY_SEPARATOR;
            foreach ($folders as $folder) {
                $searchPaths[] = $basePath . $folder . DIRECTORY_SEPARATOR;
            }
        }

        return $searchPaths;
    }
}
