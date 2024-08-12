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
use Cake\Core\Exception\CakeException;
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
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionObject;
use ReflectionProperty;
use Throwable;
use function Cake\Core\h;
use function Cake\Core\pr;

/**
 * Provide custom logging and error handling.
 *
 * Debugger extends PHP's default error handling and gives
 * simpler to use more powerful interfaces.
 *
 * @link https://book.cakephp.org/5/en/development/debugging.html#namespace-Cake\Error
 */
class Debugger
{
    use InstanceConfigTrait;

    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'outputMask' => [],
        'exportFormatter' => null,
        'editor' => 'phpstorm',
    ];

    /**
     * A map of editors to their link templates.
     *
     * @var array<string, string|callable>
     */
    protected array $editors = [
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
    protected array $_data = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $docRef = ini_get('docref_root');
        if (!$docRef && function_exists('ini_set')) {
            ini_set('docref_root', 'https://secure.php.net/');
        }
        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        $config = array_intersect_key((array)Configure::read('Debugger'), $this->_defaultConfig);
        $this->setConfig($config);
    }

    /**
     * Returns a reference to the Debugger singleton object instance.
     *
     * @param class-string<\Cake\Error\Debugger>|null $class Class name.
     * @return static
     */
    public static function getInstance(?string $class = null): static
    {
        /** @var array<int, static> $instance */
        static $instance = [];
        if ($class) {
            if (!$instance || strtolower($class) !== strtolower(get_class($instance[0]))) {
                $instance[0] = new $class();
            }
        }
        if (!$instance) {
            $instance[0] = new Debugger();
        }

        /** @var static */
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
    public static function configInstance(array|string|null $key = null, mixed $value = null, bool $merge = true): mixed
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
    public static function addEditor(string $name, Closure|string $template): void
    {
        $instance = static::getInstance();
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
            throw new InvalidArgumentException(sprintf(
                'Unknown editor `%s`. Known editors are `%s`.',
                $name,
                $known
            ));
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
            throw new InvalidArgumentException(sprintf(
                'Cannot format editor URL `%s` is not a known editor.',
                $editor
            ));
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
     * @link https://book.cakephp.org/5/en/development/debugging.html#outputting-values
     */
    public static function dump(mixed $var, int $maxDepth = 3): void
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
    public static function log(mixed $var, string|int $level = 'debug', int $maxDepth = 3): void
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
     * Get the frames from $exception that are not present in $parent
     *
     * @param \Throwable $exception The exception to get frames from.
     * @param ?\Throwable $parent The parent exception to compare frames with.
     * @return array An array of frame structures.
     */
    public static function getUniqueFrames(Throwable $exception, ?Throwable $parent): array
    {
        if ($parent === null) {
            return $exception->getTrace();
        }
        $parentFrames = $parent->getTrace();
        $frames = $exception->getTrace();

        $parentCount = count($parentFrames) - 1;
        $frameCount = count($frames) - 1;

        // Reverse loop through both traces removing frames that
        // are the same.
        for ($i = $frameCount, $p = $parentCount; $i >= 0 && $p >= 0; $p--) {
            $parentTail = $parentFrames[$p];
            $tail = $frames[$i];

            // Frames without file/line are never equal to another frame.
            $isEqual = (
                (
                    isset($tail['file']) &&
                    isset($tail['line']) &&
                    isset($parentTail['file']) &&
                    isset($parentTail['line'])
                ) &&
                ($tail['file'] === $parentTail['file']) &&
                ($tail['line'] === $parentTail['line'])
            );
            if ($isEqual) {
                unset($frames[$i]);
                $i--;
            }
        }

        return $frames;
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
     * @link https://book.cakephp.org/5/en/development/debugging.html#generating-stack-traces
     */
    public static function trace(array $options = []): array|string
    {
        // Remove the frame for Debugger::trace()
        $backtrace = debug_backtrace();
        array_shift($backtrace);

        return Debugger::formatTrace($backtrace, $options);
    }

    /**
     * Formats a stack trace based on the supplied options.
     *
     * ### Options
     *
     * - `depth` - The number of stack frames to return. Defaults to 999
     * - `format` - The format you want the return. Defaults to 'text'. If
     *    format is 'array' or 'points' the return will be an array.
     * - `args` - Should arguments for functions be shown? If true, the arguments for each method call
     *   will be displayed.
     * - `start` - The stack frame to start generating a trace from. Defaults to 0
     *
     * @param \Throwable|array $backtrace Trace as array or an exception object.
     * @param array<string, mixed> $options Format for outputting stack trace.
     * @return array|string Formatted stack trace.
     * @link https://book.cakephp.org/5/en/development/debugging.html#generating-stack-traces
     */
    public static function formatTrace(Throwable|array $backtrace, array $options = []): array|string
    {
        if ($backtrace instanceof Throwable) {
            $backtrace = $backtrace->getTrace();
        }

        $defaults = [
            'depth' => 999,
            'format' => 'text',
            'args' => false,
            'start' => 0,
            'scope' => null,
            'exclude' => ['call_user_func_array', 'trigger_error'],
        ];
        $options = Hash::merge($defaults, $options);

        $count = count($backtrace) + 1;
        $back = [];

        for ($i = $options['start']; $i < $count && $i < $options['depth']; $i++) {
            $frame = ['file' => '[main]', 'line' => ''];
            if (isset($backtrace[$i])) {
                $frame = $backtrace[$i] + ['file' => '[internal]', 'line' => '??'];
            }

            $signature = $reference = $frame['file'];
            if (!empty($frame['class'])) {
                $signature = $frame['class'] . $frame['type'] . $frame['function'];
                $reference = $signature . '(';
                if ($options['args'] && isset($frame['args'])) {
                    $args = [];
                    foreach ($frame['args'] as $arg) {
                        $args[] = Debugger::exportVar($arg);
                    }
                    $reference .= implode(', ', $args);
                }
                $reference .= ')';
            }
            if (in_array($signature, $options['exclude'], true)) {
                continue;
            }
            if ($options['format'] === 'points') {
                $back[] = ['file' => $frame['file'], 'line' => $frame['line'], 'reference' => $reference];
            } elseif ($options['format'] === 'array') {
                if (!$options['args']) {
                    unset($frame['args']);
                }
                $back[] = $frame;
            } elseif ($options['format'] === 'text') {
                $path = static::trimPath($frame['file']);
                $back[] = sprintf('%s - %s, line %d', $reference, $path, $frame['line']);
            } else {
                debug($options);
                throw new InvalidArgumentException(
                    "Invalid trace format of `{$options['format']}` chosen. Must be one of `array`, `points` or `text`."
                );
            }
        }
        if ($options['format'] === 'array' || $options['format'] === 'points') {
            return $back;
        }

        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
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
        if (defined('APP') && str_starts_with($path, APP)) {
            return str_replace(APP, 'APP/', $path);
        }
        if (defined('CAKE_CORE_INCLUDE_PATH') && str_starts_with($path, CAKE_CORE_INCLUDE_PATH)) {
            return str_replace(CAKE_CORE_INCLUDE_PATH, 'CORE', $path);
        }
        if (defined('ROOT') && str_starts_with($path, ROOT)) {
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
     * @link https://book.cakephp.org/5/en/development/debugging.html#getting-an-excerpt-from-a-file
     */
    public static function excerpt(string $file, int $line, int $context = 2): array
    {
        $lines = [];
        if (!file_exists($file)) {
            return [];
        }
        $data = file_get_contents($file);
        if (!$data) {
            return $lines;
        }
        if (str_contains($data, "\n")) {
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
        $added = false;
        if (!str_contains($str, '<?php')) {
            $added = true;
            $str = "<?php \n" . $str;
        }
        $highlight = highlight_string($str, true);
        if ($added) {
            $highlight = str_replace(
                ['&lt;?php&nbsp;<br/>', '&lt;?php&nbsp;<br />', '&lt;?php '],
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
            throw new CakeException(sprintf(
                'The `%s` formatter does not implement `%s`.',
                $class,
                FormatterInterface::class
            ));
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
    public static function exportVar(mixed $var, int $maxDepth = 3): string
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
    public static function exportVarAsPlainText(mixed $var, int $maxDepth = 3): string
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
    public static function exportVarAsNodes(mixed $var, int $maxDepth = 3): NodeInterface
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
    protected static function export(mixed $var, DebugContext $context): NodeInterface
    {
        $type = static::getType($var);

        if (str_starts_with($type, 'resource ')) {
            return new ScalarNode($type, $var);
        }

        return match ($type) {
            'float', 'string', 'null' => new ScalarNode($type, $var),
            'bool' => new ScalarNode('bool', $var),
            'int' => new ScalarNode('int', $var),
            'array' => static::exportArray($var, $context->withAddedDepth()),
            'unknown' => new SpecialNode('(unknown)'),
            default => static::exportObject($var, $context->withAddedDepth()),
        };
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

        $className = $var::class;
        if ($isRef) {
            return new ReferenceNode($className, $refNum);
        }
        $node = new ClassNode($className, $refNum);

        $remaining = $context->remainingDepth();
        if ($remaining > 0) {
            if (method_exists($var, '__debugInfo')) {
                try {
                    foreach ((array)$var->__debugInfo() as $key => $val) {
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
     * Get the type of the given variable. Will return the class name
     * for objects.
     *
     * @param mixed $var The variable to get the type of.
     * @return string The type of variable.
     */
    public static function getType(mixed $var): string
    {
        $type = get_debug_type($var);

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
    public static function printVar(mixed $var, array $location = [], ?bool $showHtml = null): void
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
     * - Convert newlines into `<br>`
     *
     * @param string $message The string message to format.
     * @return string Formatted message.
     */
    public static function formatHtmlMessage(string $message): string
    {
        $message = h($message);
        $message = (string)preg_replace('/`([^`]+)`/', '<code>$0</code>', $message);

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
