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
namespace Cake\I18n;

use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use Locale;
use function Cake\Core\pluginSplit;

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
    protected string $_name;

    /**
     * The package (domain) plugin
     *
     * @var string|null
     */
    protected ?string $_plugin = null;

    /**
     * The locale to load for the given package.
     *
     * @var string
     */
    protected string $_locale;

    /**
     * The extension name.
     *
     * @var string
     */
    protected string $_extension;

    /**
     * Creates a translation file loader. The file to be loaded corresponds to
     * the following rules:
     *
     * - The locale is a folder under the `resources/locales/` directory, a fallback will be
     *   used if the folder is not found.
     * - The $name corresponds to the file name to load
     * - If there is a loaded plugin with the underscored version of $name, the
     *   translation file will be loaded from such plugin.
     *
     * ### Examples:
     *
     * Load and parse resources/locales/fr/validation.po
     *
     * ```
     * $loader = new MessagesFileLoader('validation', 'fr_FR', 'po');
     * $package = $loader();
     * ```
     *
     * Load and parse resources/locales/fr_FR/validation.mo
     *
     * ```
     * $loader = new MessagesFileLoader('validation', 'fr_FR', 'mo');
     * $package = $loader();
     * ```
     *
     * Load the plugins/MyPlugin/resources/locales/fr/my_plugin.po file:
     *
     * ```
     * $loader = new MessagesFileLoader('my_plugin', 'fr_FR', 'mo');
     * $package = $loader();
     *
     * Vendor prefixed plugins are expected to use `my_prefix_my_plugin` syntax.
     * ```
     *
     * @param string $name The name (domain) of the translations package.
     * @param string $locale The locale to load, this will be mapped to a folder
     * in the system.
     * @param string $extension The file extension to use. This will also be mapped
     * to a messages parser class.
     */
    public function __construct(string $name, string $locale, string $extension = 'po')
    {
        $this->_name = $name;
        // If space is not added after slash, the character after it remains lowercased
        $pluginName = Inflector::camelize(str_replace('/', '/ ', $this->_name));
        if (strpos($this->_name, '.')) {
            [$this->_plugin, $this->_name] = pluginSplit($pluginName);
        } elseif (Plugin::isLoaded($pluginName)) {
            $this->_plugin = $pluginName;
        }
        $this->_locale = $locale;
        $this->_extension = $extension;
    }

    /**
     * Loads the translation file and parses it. Returns an instance of a translations
     * package containing the messages loaded from the file.
     *
     * @return \Cake\I18n\Package|false
     * @throws \Cake\Core\Exception\CakeException if no file parser class could be found for the specified
     * file extension.
     */
    public function __invoke(): Package|false
    {
        $folders = $this->translationsFolders();
        $file = $this->translationFile($folders, $this->_name, $this->_extension);
        if (!$file) {
            return false;
        }

        $name = ucfirst($this->_extension);
        $class = App::className($name, 'I18n\Parser', 'FileParser');

        if (!$class) {
            throw new CakeException(sprintf('Could not find class `%s`.', "{$name}FileParser"));
        }

        /** @var \Cake\I18n\Parser\MoFileParser|\Cake\I18n\Parser\PoFileParser $object */
        $object = new $class();
        $messages = $object->parse($file);
        $package = new Package('default');
        $package->setMessages($messages);

        return $package;
    }

    /**
     * Returns the folders where the file should be looked for according to the locale
     * and package name.
     *
     * @return array<string> The list of folders where the translation file should be looked for
     */
    public function translationsFolders(): array
    {
        $locale = Locale::parseLocale($this->_locale) + ['region' => null];

        $folders = [
            implode('_', [$locale['language'], $locale['region']]),
            $locale['language'],
        ];

        $searchPaths = [];

        $localePaths = App::path('locales');
        if (!$localePaths && defined('APP')) {
            $localePaths[] = ROOT . 'resources' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR;
        }
        foreach ($localePaths as $path) {
            foreach ($folders as $folder) {
                $searchPaths[] = $path . $folder . DIRECTORY_SEPARATOR;
            }
        }

        if ($this->_plugin && Plugin::isLoaded($this->_plugin)) {
            $basePath = App::path('locales', $this->_plugin)[0];
            foreach ($folders as $folder) {
                $searchPaths[] = $basePath . $folder . DIRECTORY_SEPARATOR;
            }
        }

        return $searchPaths;
    }

    /**
     * @param list<string> $folders Folders
     * @param string $name File name
     * @param string $ext File extension
     * @return string|null File if found
     */
    protected function translationFile(array $folders, string $name, string $ext): ?string
    {
        $file = null;

        $name = str_replace('/', '_', $name);

        foreach ($folders as $folder) {
            $path = $folder . $name . ".$ext";
            if (is_file($path)) {
                $file = $path;
                break;
            }
        }

        return $file;
    }
}
