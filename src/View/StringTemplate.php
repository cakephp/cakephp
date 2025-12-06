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
namespace Cake\View;

use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use InvalidArgumentException;
use function Cake\Core\deprecationWarning;
use function Cake\Core\h;

/**
 * Provides an interface for registering and inserting
 * content into simple logic-less string templates.
 *
 * Used by several helpers to provide simple flexible templates
 * for generating HTML and other content.
 */
class StringTemplate
{
    use InstanceConfigTrait {
        getConfig as get;
    }

    /**
     * List of attributes that can be made compact.
     *
     * @var array<string, bool>
     */
    protected array $_compactAttributes = [
        'allowfullscreen' => true,
        'async' => true,
        'autofocus' => true,
        'autoload' => true,
        'autoplay' => true,
        'checked' => true,
        'compact' => true,
        'controls' => true,
        'declare' => true,
        'default' => true,
        'defaultchecked' => true,
        'defaultmuted' => true,
        'defaultselected' => true,
        'defer' => true,
        'disabled' => true,
        'enabled' => true,
        'formnovalidate' => true,
        'hidden' => true,
        'indeterminate' => true,
        'inert' => true,
        'ismap' => true,
        'itemscope' => true,
        'loop' => true,
        'multiple' => true,
        'muted' => true,
        'nohref' => true,
        'noresize' => true,
        'noshade' => true,
        'novalidate' => true,
        'nowrap' => true,
        'open' => true,
        'pauseonexit' => true,
        'readonly' => true,
        'required' => true,
        'reversed' => true,
        'scoped' => true,
        'seamless' => true,
        'selected' => true,
        'sortable' => true,
        'truespeed' => true,
        'typemustmatch' => true,
        'visible' => true,
    ];

    /**
     * The default templates this instance holds.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * A stack of template sets that have been stashed temporarily.
     *
     * @var array
     */
    protected array $_configStack = [];

    /**
     * Contains the list of compiled templates
     *
     * @var array<string, array>
     */
    protected array $_compiled = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $config A set of templates to add.
     */
    public function __construct(array $config = [])
    {
        $this->add($config);
    }

    /**
     * Push the current templates into the template stack.
     *
     * @return void
     */
    public function push(): void
    {
        $this->_configStack[] = [
            $this->_config,
            $this->_compiled,
        ];
    }

    /**
     * Restore the most recently pushed set of templates.
     *
     * @return void
     */
    public function pop(): void
    {
        if (!$this->_configStack) {
            return;
        }
        [$this->_config, $this->_compiled] = array_pop($this->_configStack);
    }

    /**
     * Registers a list of templates by name
     *
     * ### Example:
     *
     * ```
     * $templater->add([
     *   'link' => '<a href="{{url}}">{{title}}</a>'
     *   'button' => '<button>{{text}}</button>'
     * ]);
     * ```
     *
     * @param array<string, string> $templates An associative list of named templates.
     * @return $this
     */
    public function add(array $templates)
    {
        $this->setConfig($templates);
        $this->_compileTemplates(array_keys($templates));

        return $this;
    }

    /**
     * Compile templates into a more efficient printf() compatible format.
     *
     * @param array<string> $templates The template names to compile. If empty all templates will be compiled.
     * @return void
     */
    protected function _compileTemplates(array $templates = []): void
    {
        if (!$templates) {
            $templates = array_keys($this->_config);
        }
        foreach ($templates as $name) {
            $template = $this->get($name);
            if ($template === null) {
                throw new InvalidArgumentException(sprintf('String template `%s` is not valid.', $name));
            }

            assert(
                is_string($template),
                sprintf('Template for `%s` must be of type `string`, but is `%s`', $name, gettype($template)),
            );

            $template = str_replace('%', '%%', $template);
            preg_match_all('#\{\{([\w\.]+)\}\}#', $template, $matches);
            $this->_compiled[$name] = [
                str_replace($matches[0], '%s', $template),
                $matches[1],
            ];
        }
    }

    /**
     * Load a config file containing templates.
     *
     * Template files should define a `$config` variable containing
     * all the templates to load. Loaded templates will be merged with existing
     * templates.
     *
     * @param string $file The file to load
     * @return void
     */
    public function load(string $file): void
    {
        if ($file === '') {
            throw new CakeException('String template filename cannot be an empty string');
        }

        $loader = new PhpConfig();
        $templates = $loader->read($file);
        $this->add($templates);
    }

    /**
     * Remove the named template.
     *
     * @param string $name The template to remove.
     * @return void
     */
    public function remove(string $name): void
    {
        $this->deleteConfig($name);
        unset($this->_compiled[$name]);
    }

    /**
     * Format a template string with $data
     *
     * @param string $name The template name.
     * @param array<string, mixed> $data The data to insert.
     * @return string Formatted string
     * @throws \InvalidArgumentException If template not found.
     */
    public function format(string $name, array $data): string
    {
        if (!isset($this->_compiled[$name])) {
            throw new InvalidArgumentException(sprintf('Cannot find template named `%s`.', $name));
        }
        [$template, $placeholders] = $this->_compiled[$name];

        if (isset($data['templateVars'])) {
            $data += $data['templateVars'];
            unset($data['templateVars']);
        }
        $replace = [];
        foreach ($placeholders as $placeholder) {
            $replacement = $data[$placeholder] ?? null;
            if (is_array($replacement)) {
                $replacement = implode('', $replacement);
            }
            $replace[] = $replacement;
        }

        return vsprintf($template, $replace);
    }

    /**
     * Returns a space-delimited string with items of the $options array. If a key
     * of $options array happens to be one of those listed
     * in `StringTemplate::$_compactAttributes` and its value is one of:
     *
     * - '1' (string)
     * - 1 (integer)
     * - true (boolean)
     * - 'true' (string)
     *
     * Then the value will be reset to be identical with key's name.
     * If the value is not one of these 4, the parameter is not output.
     *
     * 'escape' is a special option in that it controls the conversion of
     * attributes to their HTML-entity encoded equivalents. Set to false to disable HTML-encoding.
     *
     * If value for any option key is set to `null` or `false`, that option will be excluded from output.
     *
     * This method uses the 'attribute' and 'compactAttribute' templates. Each of
     * these templates uses the `name` and `value` variables. You can modify these
     * templates to change how attributes are formatted.
     *
     * @param array<string, mixed>|null $options Array of options.
     * @param array<string>|null $exclude Array of options to be excluded, the options here will not be part of the return.
     * @return string Composed attributes.
     */
    public function formatAttributes(?array $options, ?array $exclude = null): string
    {
        $insertBefore = ' ';
        $options = (array)$options + ['escape' => true];

        if (!is_array($exclude)) {
            $exclude = [];
        }

        $exclude = ['escape' => true, 'idPrefix' => true, 'templateVars' => true, 'fieldName' => true]
            + array_flip($exclude);
        $escape = $options['escape'];
        $attributes = [];

        foreach ($options as $key => $value) {
            if (!isset($exclude[$key]) && $value !== false && $value !== null) {
                $attributes[] = $this->_formatAttribute((string)$key, $value, $escape);
            }
        }
        $out = trim(implode(' ', $attributes));

        return $out ? $insertBefore . $out : '';
    }

    /**
     * Formats an individual attribute, and returns the string value of the composed attribute.
     * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
     *
     * @param string $key The name of the attribute to create
     * @param mixed $value The value of the attribute to create.
     * @param bool $escape Define if the value must be escaped
     * @return string The composed attribute.
     */
    protected function _formatAttribute(string $key, mixed $value, bool $escape = true): string
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }
        if (is_numeric($key)) {
            return "{$value}=\"{$value}\"";
        }
        $truthy = [1, '1', true, 'true', $key];
        $isMinimized = isset($this->_compactAttributes[$key]);
        if (!preg_match('/\A(\w|[.-])+\z/', $key)) {
            $key = h($key);
        }
        if ($isMinimized && in_array($value, $truthy, true)) {
            return "{$key}=\"{$key}\"";
        }
        if ($isMinimized) {
            return '';
        }

        return $key . '="' . ($escape ? h($value) : $value) . '"';
    }

    /**
     * Merges two sets of CSS classes into a unique array.
     *
     * Accepts class lists as arrays or space-separated strings and returns
     * a unique, indexed array of all classes.
     *
     * @param array<string>|string $existing The existing class(es).
     * @param array<string>|string $new The new class(es) to merge.
     * @return array<string> A unique array of merged classes.
     */
    public function addClassNames(array|string $existing, array|string $new): array
    {
        if (is_string($existing)) {
            $existing = $existing === '' ? [] : explode(' ', $existing);
        }
        if (is_string($new)) {
            $new = $new === '' ? [] : explode(' ', $new);
        }
        $remove = [];
        $add = [];
        foreach ($new as $key => $value) {
            if (is_string($key)) {
                if ($value) {
                    $add[] = $key;
                } else {
                    $remove[] = $key;
                }
            } else {
                $add[] = $value;
            }
        }
        // Remove any classes and then add.
        $update = array_diff($existing, $remove);
        $update = array_merge($update, $add);

        return array_values(array_unique($update));
    }

    /**
     * Adds CSS classes to an attribute array.
     *
     * Merges the provided classes with any existing classes in the specified key
     * and returns the updated attribute array with unique class values.
     *
     * Deprecated: Passing a non-array as first argument is deprecated.
     *   Pass an attributes array instead.
     * Deprecated: Returning a non-array value is deprecated.
     *   The method will only return arrays in the future.
     *
     * @param array<string, mixed>|string|null $input The attribute array to add classes to.
     * @param array<string>|string|false|null $newClass The class(es) to add.
     * @param string $useIndex The array key to use. Defaults to 'class'.
     * @return array<string, string>|string|null The updated attribute array.
     */
    public function addClass(
        mixed $input,
        array|string|false|null $newClass,
        string $useIndex = 'class',
    ): array|string|null {
        if (!is_array($input)) {
            deprecationWarning(
                '5.3.0',
                'Passing a non-array as first argument to `StringTemplate::addClass()` is deprecated. ' .
                'Pass an attributes array instead.',
            );
        }

        // NOOP
        if (!$newClass) {
            return $input;
        }

        if (!is_array($input)) {
            if (is_string($input) && $input !== '') {
                $class = explode(' ', $input);
            } else {
                $class = [];
            }

            if (is_string($newClass)) {
                $newClass = explode(' ', $newClass);
            }

            $class = array_unique(array_merge($class, $newClass));

            deprecationWarning(
                '5.3.0',
                'Returning a non-array value from `StringTemplate::addClass()` is deprecated. ' .
                'The method will only return arrays in the future.',
            );

            return Hash::insert([], $useIndex, $class);
        }

        $existingClasses = Hash::get($input, $useIndex, []);
        $mergedClasses = $this->addClassNames($existingClasses, $newClass);

        return Hash::insert($input, $useIndex, $mergedClasses);
    }
}
