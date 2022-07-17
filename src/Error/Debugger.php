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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\Debug\ArrayItemNode;
use Cake\Error\Debug\ArrayNode;
use Cake\Error\Debug\ClassNode;
use Cake\Error\Debug\ConsoleFormatter;
use Cake\Error\Debug\DebugContext;
use Cake\Error\Debug\FormatterInterface;
use Cake\Error\Debug\HtmlFormatter;
use Cake\Error\Debug\NodeInterface;
use Cake\Error\Debug\PropertyNode;
use Cake\Error\Debug\ReferenceNode;
use Cake\Error\Debug\ScalarNode;
use Cake\Error\Debug\SpecialNode;
use Cake\Error\Debug\TextFormatter;
use Cake\Error\Renderer\HtmlErrorRenderer;
use Cake\Error\Renderer\TextErrorRenderer;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\Utility\Text;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Provide custom logging and error handling.
 *
 * Debugger extends PHP's default error handling and gives
 * simpler to use more powerful interfaces.
 *
 * @link https://book.cakephp.org/4/en/development/debugging.html#namespace-Cake\Error
 */
class Debugger
{
    use InstanceConfigTrait;

    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'outputMask' => [],
        'exportFormatter' => null,
        'editor' => 'phpstorm',
    ];

    /**
     * The current output format.
     *
     * @var string
     */
    protected $_outputFormat = 'js';

    /**
     * Templates used when generating trace or error strings. Can be global or indexed by the format
     * value used in $_outputFormat.
     *
     * @var array<string, array<string, mixed>>
     */
    protected $_templates = [
        'log' => [
            // These templates are not actually used, as Debugger::log() is called instead.
            'trace' => '{:reference} - {:path}, line {:line}',
            'error' => '{:error} ({:code}): {:description} in [{:file}, line {:line}]',
        ],
        'js' => [
            'error' => '',
            'info' => '',
            'trace' => '<pre class="stack-trace">{:trace}</pre>',
            'code' => '',
            'context' => '',
            'links' => [],
            'escapeContext' => true,
        ],
        'html' => [
            'trace' => '<pre class="cake-error trace"><b>Trace</b> <p>{:trace}</p></pre>',
            'context' => '<pre class="cake-error context"><b>Context</b> <p>{:context}</p></pre>',
            'escapeContext' => true,
        ],
        'txt' => [
            'error' => "{:error}: {:code} :: {:description} on line {:line} of {:path}\n{:info}",
            'code' => '',
            'info' => '',
        ],
        'base' => [
            'traceLine' => '{:reference} - {:path}, line {:line}',
            'trace' => "Trace:\n{:trace}\n",
            'context' => "Context:\n{:context}\n",
        ],
    ];

    /**
     * Mapping for error renderers.
     *
     * Error renderers are replacing output formatting with
     * an object based system. Having Debugger handle and render errors
     * will be deprecated and the new ErrorTrap system should be used instead.
     *
     * @var array<string, class-string>
     */
    protected $renderers = [
        'txt' => TextErrorRenderer::class,
        // The html alias currently uses no JS and will be deprecated.
        'js' => HtmlErrorRenderer::class,
    ];

    /**
     * A map of editors to their link templates.
     *
     * @var array<string, string|callable>
     */
    protected $editors = [
        'atom' => 'atom://core/open/file?filename={file}&line={line}',
        'emacs' => 'emacs://open?url=file://{file}&line={line}',
        'macvim' => 'mvim://open/?url=file://{file}&line={line}',
        'phpstorm' => 'phpstorm://open?file={file}&line={line}',
        'sublime' => 'subl://open?url=file://{file}&line={line}',
        'textmate' => 'txmt://open?url=file://{file}&line={line}',
        'vscode' => 'vscode://file/{file}:{line}',
    ];

    /**
     * Holds current output data when outputFormat is false.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $docRef = ini_get('docref_root');
        if (empty($docRef) && function_exists('ini_set')) {
            ini_set('docref_root', 'https://secure.php.net/');
        }
        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        $config = array_intersect_key((array)Configure::read('Debugger'), $this->_defaultConfig);
        $this->setConfig($config);

        $e = '<pre class="cake-error">';
        $e .= '<a href="javascript:void(0);" onclick="document.getElementById(\'{:id}-trace\')';
        $e .= '.style.display = (document.getElementById(\'{:id}-trace\').style.display == ';
        $e .= '\'none\' ? \'\' : \'none\');"><b>{:error}</b> ({:code})</a>: {:description} ';
        $e .= '[<b>{:path}</b>, line <b>{:line}</b>]';

        $e .= '<div id="{:id}-trace" class="cake-stack-trace" style="display: none;">';
        $e .= '{:links}{:info}</div>';
        $e .= '</pre>';
        $this->_templates['js']['error'] = $e;

        $t = '<div id="{:id}-trace" class="cake-stack-trace" style="display: none;">';
        $t .= '{:context}{:code}{:trace}</div>';
        $this->_templates['js']['info'] = $t;

        $links = [];
        $link = '<a href="javascript:void(0);" onclick="document.getElementById(\'{:id}-code\')';
        $link .= '.style.display = (document.getElementById(\'{:id}-code\').style.display == ';
        $link .= '\'none\' ? \'\' : \'none\')">Code</a>';
        $links['code'] = $link;

        $link = '<a href="javascript:void(0);" onclick="document.getElementById(\'{:id}-context\')';
        $link .= '.style.display = (document.getElementById(\'{:id}-context\').style.display == ';
        $link .= '\'none\' ? \'\' : \'none\')">Context</a>';
        $links['context'] = $link;

        $this->_templates['js']['links'] = $links;

        $this->_templates['js']['context'] = '<pre id="{:id}-context" class="cake-context cake-debug" ';
        $this->_templates['js']['context'] .= 'style="display: none;">{:context}</pre>';

        $this->_templates['js']['code'] = '<pre id="{:id}-code" class="cake-code-dump" ';
        $this->_templates['js']['code'] .= 'style="display: none;">{:code}</pre>';

        $e = '<pre class="cake-error"><b>{:error}</b> ({:code}) : {:description} ';
        $e .= '[<b>{:path}</b>, line <b>{:line}]</b></pre>';
        $this->_templates['html']['error'] = $e;

        $this->_templates['html']['context'] = '<pre class="cake-context cake-debug"><b>Context</b> ';
        $this->_templates['html']['context'] .= '<p>{:context}</p></pre>';
    }

    /**
     * Returns a reference to the Debugger singleton object instance.
     *
     * @param string|null $class Class name.
     * @return static
     */
    public static function getInstance(?string $class = null)
    {
        static $instance = [];
        if (!empty($class)) {
            if (!$instance || strtolower($class) !== strtolower(get_class($instance[0]))) {
                $instance[0] = new $class();
            }
        }
        if (!$instance) {
            $instance[0] = new Debugger();
        }

        return $instance[0];
    }

    /**
     * Read or write configuration options for the Debugger instance.
     *
     * @param array<string, mixed>|string|null $key The key to get/set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return mixed Config value being read, or the object itself on write operations.
     * @throws \Cake\Core\Exception\CakeException When trying to set a key that is invalid.
     */
    public static function configInstance($key = null, $value = null, bool $merge = true)
    {
        if ($key === null) {
            return static::getInstance()->getConfig($key);
        }

        if (is_array($key) || func_num_args() >= 2) {
            return static::getInstance()->setConfig($key, $value, $merge);
        }

        return static::getInstance()->getConfig($key);
    }

    /**
     * Reads the current output masking.
     *
     * @return array<string, string>
     */
    public static function outputMask(): array
    {
        return static::configInstance('outputMask');
    }

    /**
     * Sets configurable masking of debugger output by property name and array key names.
     *
     * ### Example
     *
     * Debugger::setOutputMask(['password' => '[*************]');
     *
     * @param array<string, string> $value An array where keys are replaced by their values in output.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return void
     */
    public static function setOutputMask(array $value, bool $merge = true): void
    {
        static::configInstance('outputMask', $value, $merge);
    }

    /**
     * Add an editor link format
     *
     * Template strings can use the `{file}` and `{line}` placeholders.
     * Closures templates must return a string, and accept two parameters:
     * The file and line.
     *
     * @param string $name The name of the editor.
     * @param \Closure|string $template The string template or closure
     * @return void
     */
    public static function addEditor(string $name, $template): void
    {
        $instance = static::getInstance();
        if (!is_string($template) && !($template instanceof Closure)) {
            $type = getTypeName($template);
            throw new RuntimeException("Invalid editor type of `{$type}`. Expected string or Closure.");
        }
        $instance->editors[$name] = $template;
    }

    /**
     * Choose the editor link style you want to use.
     *
     * @param string $name The editor name.
     * @return void
     */
    public static function setEditor(string $name): void
    {
        $instance = static::getInstance();
        if (!isset($instance->editors[$name])) {
            $known = implode(', ', array_keys($instance->editors));
            throw new RuntimeException("Unknown editor `{$name}`. Known editors are {$known}");
        }
        $instance->setConfig('editor', $name);
    }

    /**
     * Get a formatted URL for the active editor.
     *
     * @param string $file The file to create a link for.
     * @param int $line The line number to create a link for.
     * @return string The formatted URL.
     */
    public static function editorUrl(string $file, int $line): string
    {
        $instance = static::getInstance();
        $editor = $instance->getConfig('editor');
        if (!isset($instance->editors[$editor])) {
            throw new RuntimeException("Cannot format editor URL `{$editor}` is not a known editor.");
        }

        $template = $instance->editors[$editor];
        if (is_string($template)) {
            return str_replace(['{file}', '{line}'], [$file, (string)$line], $template);
        }

        return $template($file, $line);
    }

    /**
     * Recursively formats and outputs the contents of the supplied variable.
     *
     * @param mixed $var The variable to dump.
     * @param int $maxDepth The depth to output to. Defaults to 3.
     * @return void
     * @see \Cake\Error\Debugger::exportVar()
     * @link https://book.cakephp.org/4/en/development/debugging.html#outputting-values
     */
    public static function dump($var, int $maxDepth = 3): void
    {
        pr(static::exportVar($var, $maxDepth));
    }

    /**
     * Creates an entry in the log file. The log entry will contain a stack trace from where it was called.
     * as well as export the variable using exportVar. By default, the log is written to the debug log.
     *
     * @param mixed $var Variable or content to log.
     * @param string|int $level Type of log to use. Defaults to 'debug'.
     * @param int $maxDepth The depth to output to. Defaults to 3.
     * @return void
     */
    public static function log($var, $level = 'debug', int $maxDepth = 3): void
    {
        /** @var string $source */
        $source = static::trace(['start' => 1]);
        $source .= "\n";

        Log::write(
            $level,
            "\n" . $source . static::exportVarAsPlainText($var, $maxDepth)
        );
    }

    /**
     * Outputs a stack trace based on the supplied options.
     *
     * ### Options
     *
     * - `depth` - The number of stack frames to return. Defaults to 999
     * - `format` - The format you want the return. Defaults to the currently selected format. If
     *    format is 'array' or 'points' the return will be an array.
     * - `args` - Should arguments for functions be shown? If true, the arguments for each method call
     *   will be displayed.
     * - `start` - The stack frame to start generating a trace from. Defaults to 0
     *
     * @param array<string, mixed> $options Format for outputting stack trace.
     * @return array|string Formatted stack trace.
     * @link https://book.cakephp.org/4/en/development/debugging.html#generating-stack-traces
     */
    public static function trace(array $options = [])
    {
        return Debugger::formatTrace(debug_backtrace(), $options);
    }

    /**
     * Formats a stack trace based on the supplied options.
     *
     * ### Options
     *
     * - `depth` - The number of stack frames to return. Defaults to 999
     * - `format` - The format you want the return. Defaults to the currently selected format. If
     *    format is 'array' or 'points' the return will be an array.
     * - `args` - Should arguments for functions be shown? If true, the arguments for each method call
     *   will be displayed.
     * - `start` - The stack frame to start generating a trace from. Defaults to 0
     *
     * @param \Throwable|array $backtrace Trace as array or an exception object.
     * @param array<string, mixed> $options Format for outputting stack trace.
     * @return array|string Formatted stack trace.
     * @link https://book.cakephp.org/4/en/development/debugging.html#generating-stack-traces
     */
    public static function formatTrace($backtrace, array $options = [])
    {
        if ($backtrace instanceof Throwable) {
            $backtrace = $backtrace->getTrace();
        }
        $self = Debugger::getInstance();
        $defaults = [
            'depth' => 999,
            'format' => $self->_outputFormat,
            'args' => false,
            'start' => 0,
            'scope' => null,
            'exclude' => ['call_user_func_array', 'trigger_error'],
        ];
        $options = Hash::merge($defaults, $options);

        $count = count($backtrace);
        $back = [];

        $_trace = [
            'line' => '??',
            'file' => '[internal]',
            'class' => null,
            'function' => '[main]',
        ];

        for ($i = $options['start']; $i < $count && $i < $options['depth']; $i++) {
            $trace = $backtrace[$i] + ['file' => '[internal]', 'line' => '??'];
            $signature = $reference = '[main]';

            if (isset($backtrace[$i + 1])) {
                $next = $backtrace[$i + 1] + $_trace;
                $signature = $reference = $next['function'];

                if (!empty($next['class'])) {
                    $signature = $next['class'] . '::' . $next['function'];
                    $reference = $signature . '(';
                    if ($options['args'] && isset($next['args'])) {
                        $args = [];
                        foreach ($next['args'] as $arg) {
                            $args[] = Debugger::exportVar($arg);
                        }
                        $reference .= implode(', ', $args);
                    }
                    $reference .= ')';
                }
            }
            if (in_array($signature, $options['exclude'], true)) {
                continue;
            }
            if ($options['format'] === 'points') {
                $back[] = ['file' => $trace['file'], 'line' => $trace['line'], 'reference' => $reference];
            } elseif ($options['format'] === 'array') {
                if (!$options['args']) {
                    unset($trace['args']);
                }
                $back[] = $trace;
            } else {
                if (isset($self->_templates[$options['format']]['traceLine'])) {
                    $tpl = $self->_templates[$options['format']]['traceLine'];
                } else {
                    $tpl = $self->_templates['base']['traceLine'];
                }
                $trace['path'] = static::trimPath($trace['file']);
                $trace['reference'] = $reference;
                unset($trace['object'], $trace['args']);
                $back[] = Text::insert($tpl, $trace, ['before' => '{:', 'after' => '}']);
            }
        }

        if ($options['format'] === 'array' || $options['format'] === 'points') {
            return $back;
        }

        /** @psalm-suppress InvalidArgument */
        return implode("\n", $back);
    }

    /**
     * Shortens file paths by replacing the application base path with 'APP', and the CakePHP core
     * path with 'CORE'.
     *
     * @param string $path Path to shorten.
     * @return string Normalized path
     */
    public static function trimPath(string $path): string
    {
        if (defined('APP') && strpos($path, APP) === 0) {
            return str_replace(APP, 'APP/', $path);
        }
        if (defined('CAKE_CORE_INCLUDE_PATH') && strpos($path, CAKE_CORE_INCLUDE_PATH) === 0) {
            return str_replace(CAKE_CORE_INCLUDE_PATH, 'CORE', $path);
        }
        if (defined('ROOT') && strpos($path, ROOT) === 0) {
            return str_replace(ROOT, 'ROOT', $path);
        }

        return $path;
    }

    /**
     * Grabs an excerpt from a file and highlights a given line of code.
     *
     * Usage:
     *
     * ```
     * Debugger::excerpt('/path/to/file', 100, 4);
     * ```
     *
     * The above would return an array of 8 items. The 4th item would be the provided line,
     * and would be wrapped in `<span class="code-highlight"></span>`. All the lines
     * are processed with highlight_string() as well, so they have basic PHP syntax highlighting
     * applied.
     *
     * @param string $file Absolute path to a PHP file.
     * @param int $line Line number to highlight.
     * @param int $context Number of lines of context to extract above and below $line.
     * @return array<string> Set of lines highlighted
     * @see https://secure.php.net/highlight_string
     * @link https://book.cakephp.org/4/en/development/debugging.html#getting-an-excerpt-from-a-file
     */
    public static function excerpt(string $file, int $line, int $context = 2): array
    {
        $lines = [];
        if (!file_exists($file)) {
            return [];
        }
        $data = file_get_contents($file);
        if (empty($data)) {
            return $lines;
        }
        if (strpos($data, "\n") !== false) {
            $data = explode("\n", $data);
        }
        $line--;
        if (!isset($data[$line])) {
            return $lines;
        }
        for ($i = $line - $context; $i < $line + $context + 1; $i++) {
            if (!isset($data[$i])) {
                continue;
            }
            $string = str_replace(["\r\n", "\n"], '', static::_highlight($data[$i]));
            if ($i === $line) {
                $lines[] = '<span class="code-highlight">' . $string . '</span>';
            } else {
                $lines[] = $string;
            }
        }

        return $lines;
    }

    /**
     * Wraps the highlight_string function in case the server API does not
     * implement the function as it is the case of the HipHop interpreter
     *
     * @param string $str The string to convert.
     * @return string
     */
    protected static function _highlight(string $str): string
    {
        if (function_exists('hphp_log') || function_exists('hphp_gettid')) {
            return htmlentities($str);
        }
        $added = false;
        if (strpos($str, '<?php') === false) {
            $added = true;
            $str = "<?php \n" . $str;
        }
        $highlight = highlight_string($str, true);
        if ($added) {
            $highlight = str_replace(
                ['&lt;?php&nbsp;<br/>', '&lt;?php&nbsp;<br />'],
                '',
                $highlight
            );
        }

        return $highlight;
    }

    /**
     * Get the configured export formatter or infer one based on the environment.
     *
     * @return \Cake\Error\Debug\FormatterInterface
     * @unstable This method is not stable and may change in the future.
     * @since 4.1.0
     */
    public function getExportFormatter(): FormatterInterface
    {
        $instance = static::getInstance();
        $class = $instance->getConfig('exportFormatter');
        if (!$class) {
            if (ConsoleFormatter::environmentMatches()) {
                $class = ConsoleFormatter::class;
            } elseif (HtmlFormatter::environmentMatches()) {
                $class = HtmlFormatter::class;
            } else {
                $class = TextFormatter::class;
            }
        }
        $instance = new $class();
        if (!$instance instanceof FormatterInterface) {
            throw new RuntimeException(
                "The `{$class}` formatter does not implement " . FormatterInterface::class
            );
        }

        return $instance;
    }

    /**
     * Converts a variable to a string for debug output.
     *
     * *Note:* The following keys will have their contents
     * replaced with `*****`:
     *
     *  - password
     *  - login
     *  - host
     *  - database
     *  - port
     *  - prefix
     *  - schema
     *
     * This is done to protect database credentials, which could be accidentally
     * shown in an error message if CakePHP is deployed in development mode.
     *
     * @param mixed $var Variable to convert.
     * @param int $maxDepth The depth to output to. Defaults to 3.
     * @return string Variable as a formatted string
     */
    public static function exportVar($var, int $maxDepth = 3): string
    {
        $context = new DebugContext($maxDepth);
        $node = static::export($var, $context);

        return static::getInstance()->getExportFormatter()->dump($node);
    }

    /**
     * Converts a variable to a plain text string.
     *
     * @param mixed $var Variable to convert.
     * @param int $maxDepth The depth to output to. Defaults to 3.
     * @return string Variable as a string
     */
    public static function exportVarAsPlainText($var, int $maxDepth = 3): string
    {
        return (new TextFormatter())->dump(
            static::export($var, new DebugContext($maxDepth))
        );
    }

    /**
     * Convert the variable to the internal node tree.
     *
     * The node tree can be manipulated and serialized more easily
     * than many object graphs can.
     *
     * @param mixed $var Variable to convert.
     * @param int $maxDepth The depth to generate nodes to. Defaults to 3.
     * @return \Cake\Error\Debug\NodeInterface The root node of the tree.
     */
    public static function exportVarAsNodes($var, int $maxDepth = 3): NodeInterface
    {
        return static::export($var, new DebugContext($maxDepth));
    }

    /**
     * Protected export function used to keep track of indentation and recursion.
     *
     * @param mixed $var The variable to dump.
     * @param \Cake\Error\Debug\DebugContext $context Dump context
     * @return \Cake\Error\Debug\NodeInterface The dumped variable.
     */
    protected static function export($var, DebugContext $context): NodeInterface
    {
        $type = static::getType($var);
        switch ($type) {
            case 'float':
            case 'string':
            case 'resource':
            case 'resource (closed)':
            case 'null':
                return new ScalarNode($type, $var);
            case 'boolean':
                return new ScalarNode('bool', $var);
            case 'integer':
                return new ScalarNode('int', $var);
            case 'array':
                return static::exportArray($var, $context->withAddedDepth());
            case 'unknown':
                return new SpecialNode('(unknown)');
            default:
                return static::exportObject($var, $context->withAddedDepth());
        }
    }

    /**
     * Export an array type object. Filters out keys used in datasource configuration.
     *
     * The following keys are replaced with ***'s
     *
     * - password
     * - login
     * - host
     * - database
     * - port
     * - prefix
     * - schema
     *
     * @param array $var The array to export.
     * @param \Cake\Error\Debug\DebugContext $context The current dump context.
     * @return \Cake\Error\Debug\ArrayNode Exported array.
     */
    protected static function exportArray(array $var, DebugContext $context): ArrayNode
    {
        $items = [];

        $remaining = $context->remainingDepth();
        if ($remaining >= 0) {
            $outputMask = static::outputMask();
            foreach ($var as $key => $val) {
                if (array_key_exists($key, $outputMask)) {
                    $node = new ScalarNode('string', $outputMask[$key]);
                } elseif ($val !== $var) {
                    // Dump all the items without increasing depth.
                    $node = static::export($val, $context);
                } else {
                    // Likely recursion, so we increase depth.
                    $node = static::export($val, $context->withAddedDepth());
                }
                $items[] = new ArrayItemNode(static::export($key, $context), $node);
            }
        } else {
            $items[] = new ArrayItemNode(
                new ScalarNode('string', ''),
                new SpecialNode('[maximum depth reached]')
            );
        }

        return new ArrayNode($items);
    }

    /**
     * Handles object to node conversion.
     *
     * @param object $var Object to convert.
     * @param \Cake\Error\Debug\DebugContext $context The dump context.
     * @return \Cake\Error\Debug\NodeInterface
     * @see \Cake\Error\Debugger::exportVar()
     */
    protected static function exportObject(object $var, DebugContext $context): NodeInterface
    {
        $isRef = $context->hasReference($var);
        $refNum = $context->getReferenceId($var);

        $className = get_class($var);
        if ($isRef) {
            return new ReferenceNode($className, $refNum);
        }
        $node = new ClassNode($className, $refNum);

        $remaining = $context->remainingDepth();
        if ($remaining > 0) {
            if (method_exists($var, '__debugInfo')) {
                try {
                    foreach ($var->__debugInfo() as $key => $val) {
                        $node->addProperty(new PropertyNode("'{$key}'", null, static::export($val, $context)));
                    }

                    return $node;
                } catch (Exception $e) {
                    return new SpecialNode("(unable to export object: {$e->getMessage()})");
                }
            }

            $outputMask = static::outputMask();
            $objectVars = get_object_vars($var);
            foreach ($objectVars as $key => $value) {
                if (array_key_exists($key, $outputMask)) {
                    $value = $outputMask[$key];
                }
                /** @psalm-suppress RedundantCast */
                $node->addProperty(
                    new PropertyNode((string)$key, 'public', static::export($value, $context->withAddedDepth()))
                );
            }

            $ref = new ReflectionObject($var);

            $filters = [
                ReflectionProperty::IS_PROTECTED => 'protected',
                ReflectionProperty::IS_PRIVATE => 'private',
            ];
            foreach ($filters as $filter => $visibility) {
                $reflectionProperties = $ref->getProperties($filter);
                foreach ($reflectionProperties as $reflectionProperty) {
                    $reflectionProperty->setAccessible(true);

                    if (
                        method_exists($reflectionProperty, 'isInitialized') &&
                        !$reflectionProperty->isInitialized($var)
                    ) {
                        $value = new SpecialNode('[uninitialized]');
                    } else {
                        $value = static::export($reflectionProperty->getValue($var), $context->withAddedDepth());
                    }
                    $node->addProperty(
                        new PropertyNode(
                            $reflectionProperty->getName(),
                            $visibility,
                            $value
                        )
                    );
                }
            }
        }

        return $node;
    }

    /**
     * Get the output format for Debugger error rendering.
     *
     * @return string Returns the current format when getting.
     * @deprecated 4.4.0 Update your application so use ErrorTrap instead.
     */
    public static function getOutputFormat(): string
    {
        deprecationWarning('Debugger::getOutputFormat() is deprecated.');

        return Debugger::getInstance()->_outputFormat;
    }

    /**
     * Set the output format for Debugger error rendering.
     *
     * @param string $format The format you want errors to be output as.
     * @return void
     * @throws \InvalidArgumentException When choosing a format that doesn't exist.
     * @deprecated 4.4.0 Update your application so use ErrorTrap instead.
     */
    public static function setOutputFormat(string $format): void
    {
        deprecationWarning('Debugger::setOutputFormat() is deprecated.');
        $self = Debugger::getInstance();

        if (!isset($self->_templates[$format])) {
            throw new InvalidArgumentException('Invalid Debugger output format.');
        }
        $self->_outputFormat = $format;
    }

    /**
     * Add an output format or update a format in Debugger.
     *
     * ```
     * Debugger::addFormat('custom', $data);
     * ```
     *
     * Where $data is an array of strings that use Text::insert() variable
     * replacement. The template vars should be in a `{:id}` style.
     * An error formatter can have the following keys:
     *
     * - 'error' - Used for the container for the error message. Gets the following template
     *   variables: `id`, `error`, `code`, `description`, `path`, `line`, `links`, `info`
     * - 'info' - A combination of `code`, `context` and `trace`. Will be set with
     *   the contents of the other template keys.
     * - 'trace' - The container for a stack trace. Gets the following template
     *   variables: `trace`
     * - 'context' - The container element for the context variables.
     *   Gets the following templates: `id`, `context`
     * - 'links' - An array of HTML links that are used for creating links to other resources.
     *   Typically this is used to create javascript links to open other sections.
     *   Link keys, are: `code`, `context`, `help`. See the JS output format for an
     *   example.
     * - 'traceLine' - Used for creating lines in the stacktrace. Gets the following
     *   template variables: `reference`, `path`, `line`
     *
     * Alternatively if you want to use a custom callback to do all the formatting, you can use
     * the callback key, and provide a callable:
     *
     * ```
     * Debugger::addFormat('custom', ['callback' => [$foo, 'outputError']];
     * ```
     *
     * The callback can expect two parameters. The first is an array of all
     * the error data. The second contains the formatted strings generated using
     * the other template strings. Keys like `info`, `links`, `code`, `context` and `trace`
     * will be present depending on the other templates in the format type.
     *
     * @param string $format Format to use, including 'js' for JavaScript-enhanced HTML, 'html' for
     *    straight HTML output, or 'txt' for unformatted text.
     * @param array $strings Template strings, or a callback to be used for the output format.
     * @return array The resulting format string set.
     * @deprecated 4.4.0 Update your application so use ErrorTrap instead.
     */
    public static function addFormat(string $format, array $strings): array
    {
        deprecationWarning('Debugger::addFormat() is deprecated.');
        $self = Debugger::getInstance();
        if (isset($self->_templates[$format])) {
            if (isset($strings['links'])) {
                $self->_templates[$format]['links'] = array_merge(
                    $self->_templates[$format]['links'],
                    $strings['links']
                );
                unset($strings['links']);
            }
            $self->_templates[$format] = $strings + $self->_templates[$format];
        } else {
            $self->_templates[$format] = $strings;
        }
        unset($self->renderers[$format]);

        return $self->_templates[$format];
    }

    /**
     * Add a renderer to the current instance.
     *
     * @param string $name The alias for the the renderer.
     * @param class-string<\Cake\Error\ErrorRendererInterface> $class The classname of the renderer to use.
     * @return void
     * @deprecated 4.4.0 Update your application so use ErrorTrap instead.
     */
    public static function addRenderer(string $name, string $class): void
    {
        deprecationWarning('Debugger::addRenderer() is deprecated.');
        if (!in_array(ErrorRendererInterface::class, class_implements($class))) {
            throw new InvalidArgumentException(
                'Invalid renderer class. $class must implement ' . ErrorRendererInterface::class
            );
        }
        $self = Debugger::getInstance();
        $self->renderers[$name] = $class;
    }

    /**
     * Takes a processed array of data from an error and displays it in the chosen format.
     *
     * @param array $data Data to output.
     * @return void
     * @deprecated 4.4.0 Update your application so use ErrorTrap instead.
     */
    public function outputError(array $data): void
    {
        $defaults = [
            'level' => 0,
            'error' => 0,
            'code' => 0,
            'description' => '',
            'file' => '',
            'line' => 0,
            'context' => [],
            'start' => 2,
        ];
        $data += $defaults;

        $outputFormat = $this->_outputFormat;
        if (isset($this->renderers[$outputFormat])) {
            /** @var array $trace */
            $trace = static::trace(['start' => $data['start'], 'format' => 'points']);
            $error = new PhpError($data['code'], $data['description'], $data['file'], $data['line'], $trace);
            $renderer = new $this->renderers[$outputFormat]();
            echo $renderer->render($error, Configure::read('debug'));

            return;
        }

        $files = static::trace(['start' => $data['start'], 'format' => 'points']);
        $code = '';
        $file = null;
        if (isset($files[0]['file'])) {
            $file = $files[0];
        } elseif (isset($files[1]['file'])) {
            $file = $files[1];
        }
        if ($file) {
            $code = static::excerpt($file['file'], $file['line'], 1);
        }
        $trace = static::trace(['start' => $data['start'], 'depth' => '20']);
        $insertOpts = ['before' => '{:', 'after' => '}'];
        $context = [];
        $links = [];
        $info = '';

        foreach ((array)$data['context'] as $var => $value) {
            $context[] = "\${$var} = " . static::exportVar($value, 3);
        }

        switch ($this->_outputFormat) {
            case false:
                $this->_data[] = compact('context', 'trace') + $data;

                return;
            case 'log':
                static::log(compact('context', 'trace') + $data);

                return;
        }

        $data['trace'] = $trace;
        $data['id'] = 'cakeErr' . uniqid();
        $tpl = $this->_templates[$outputFormat] + $this->_templates['base'];

        if (isset($tpl['links'])) {
            foreach ($tpl['links'] as $key => $val) {
                $links[$key] = Text::insert($val, $data, $insertOpts);
            }
        }

        if (!empty($tpl['escapeContext'])) {
            $data['description'] = h($data['description']);
        }

        $infoData = compact('code', 'context', 'trace');
        foreach ($infoData as $key => $value) {
            if (empty($value) || !isset($tpl[$key])) {
                continue;
            }
            if (is_array($value)) {
                $value = implode("\n", $value);
            }
            $info .= Text::insert($tpl[$key], [$key => $value] + $data, $insertOpts);
        }
        $links = implode(' ', $links);

        if (isset($tpl['callback']) && is_callable($tpl['callback'])) {
            $tpl['callback']($data, compact('links', 'info'));

            return;
        }
        echo Text::insert($tpl['error'], compact('links', 'info') + $data, $insertOpts);
    }

    /**
     * Get the type of the given variable. Will return the class name
     * for objects.
     *
     * @param mixed $var The variable to get the type of.
     * @return string The type of variable.
     */
    public static function getType($var): string
    {
        $type = getTypeName($var);

        if ($type === 'NULL') {
            return 'null';
        }

        if ($type === 'double') {
            return 'float';
        }

        if ($type === 'unknown type') {
            return 'unknown';
        }

        return $type;
    }

    /**
     * Prints out debug information about given variable.
     *
     * @param mixed $var Variable to show debug information for.
     * @param array $location If contains keys "file" and "line" their values will
     *    be used to show location info.
     * @param bool|null $showHtml If set to true, the method prints the debug
     *    data encoded as HTML. If false, plain text formatting will be used.
     *    If null, the format will be chosen based on the configured exportFormatter, or
     *    environment conditions.
     * @return void
     */
    public static function printVar($var, array $location = [], ?bool $showHtml = null): void
    {
        $location += ['file' => null, 'line' => null];
        if ($location['file']) {
            $location['file'] = static::trimPath((string)$location['file']);
        }

        $debugger = static::getInstance();
        $restore = null;
        if ($showHtml !== null) {
            $restore = $debugger->getConfig('exportFormatter');
            $debugger->setConfig('exportFormatter', $showHtml ? HtmlFormatter::class : TextFormatter::class);
        }
        $contents = static::exportVar($var, 25);
        $formatter = $debugger->getExportFormatter();

        if ($restore) {
            $debugger->setConfig('exportFormatter', $restore);
        }
        echo $formatter->formatWrapper($contents, $location);
    }

    /**
     * Format an exception message to be HTML formatted.
     *
     * Does the following formatting operations:
     *
     * - HTML escape the message.
     * - Convert `bool` into `<code>bool</code>`
     * - Convert newlines into `<br />`
     *
     * @param string $message The string message to format.
     * @return string Formatted message.
     */
    public static function formatHtmlMessage(string $message): string
    {
        $message = h($message);
        $message = preg_replace('/`([^`]+)`/', '<code>$1</code>', $message);

        return nl2br($message);
    }

    /**
     * Verifies that the application's salt and cipher seed value has been changed from the default value.
     *
     * @return void
     */
    public static function checkSecurityKeys(): void
    {
        $salt = Security::getSalt();
        if ($salt === '__SALT__' || strlen($salt) < 32) {
            trigger_error(
                'Please change the value of `Security.salt` in `ROOT/config/app_local.php` ' .
                'to a random value of at least 32 characters.',
                E_USER_NOTICE
            );
        }
    }
}
