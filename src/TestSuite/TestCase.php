<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Error\Debugger;
use Cake\Error\PhpError;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Entity;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Cake\TestSuite\Constraint\EventFired;
use Cake\TestSuite\Constraint\EventFiredWith;
use Cake\TestSuite\Fixture\FixtureStrategyInterface;
use Cake\TestSuite\Fixture\TruncateStrategy;
use Cake\Utility\Inflector;
use Closure;
use Exception;
use LogicException;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionException;
use function Cake\Core\pluginSplit;

/**
 * Cake TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use LocatorAwareTrait;
    use PHPUnitConsecutiveTrait;

    /**
     * Fixtures used by this test case.
     *
     * @var array<string>
     */
    protected array $fixtures = [];

    /**
     * @var \Cake\TestSuite\Fixture\FixtureStrategyInterface|null
     */
    protected ?FixtureStrategyInterface $fixtureStrategy = null;

    /**
     * Configure values to restore at end of test.
     *
     * @var array
     */
    protected array $_configure = [];

    /**
     * @var \Cake\Error\PhpError|null
     */
    private ?PhpError $_capturedError = null;

    /**
     * Overrides SimpleTestCase::skipIf to provide a boolean return value
     *
     * @param bool $shouldSkip Whether the test should be skipped.
     * @param string $message The message to display.
     * @return bool
     */
    public function skipIf(bool $shouldSkip, string $message = ''): bool
    {
        if ($shouldSkip) {
            $this->markTestSkipped($message);
        }

        return $shouldSkip;
    }

    /**
     * Helper method for tests that needs to use error_reporting()
     *
     * @param int $errorLevel value of error_reporting() that needs to use
     * @param callable $callable callable function that will receive asserts
     * @return void
     */
    public function withErrorReporting(int $errorLevel, callable $callable): void
    {
        $default = error_reporting();
        error_reporting($errorLevel);
        try {
            $callable();
        } finally {
            error_reporting($default);
        }
    }

    /**
     * Capture errors from $callable so that you can do assertions on the error.
     *
     * If no error is captured an assertion will fail.
     *
     * @param int $errorLevel The value of error_reporting() to use.
     * @param \Closure $callable A closure to capture errors from.
     * @return \Cake\Error\PhpError The captured error.
     */
    public function captureError(int $errorLevel, Closure $callable): PhpError
    {
        $default = error_reporting();
        error_reporting($errorLevel);

        $this->_capturedError = null;
        set_error_handler(
            function (int $code, string $description, string $file, int $line) {
                $trace = Debugger::trace(['start' => 1, 'format' => 'points']);
                assert(is_array($trace));
                $this->_capturedError = new PhpError($code, $description, $file, $line, $trace);

                return true;
            },
            $errorLevel
        );

        try {
            $callable();
        } finally {
            restore_error_handler();
            error_reporting($default);
        }
        if ($this->_capturedError === null) {
            $this->fail('No error was captured');
        }
        /** @var \Cake\Error\PhpError $this->_capturedError */
        return $this->_capturedError;
    }

    /**
     * Helper method for check deprecation methods
     *
     * @param \Closure $callable callable function that will receive asserts
     * @return void
     */
    public function deprecated(Closure $callable): void
    {
        $duplicate = Configure::read('Error.allowDuplicateDeprecations');
        Configure::write('Error.allowDuplicateDeprecations', true);
        /** @var bool $deprecation Expand type for psalm */
        $deprecation = false;

        $previousHandler = set_error_handler(
            function ($code, $message, $file, $line, $context = null) use (&$previousHandler, &$deprecation): bool {
                if ($code == E_USER_DEPRECATED) {
                    $deprecation = true;

                    return true;
                }
                if ($previousHandler) {
                    return $previousHandler($code, $message, $file, $line, $context);
                }

                return false;
            }
        );
        try {
            $callable();
        } finally {
            restore_error_handler();
            if ($duplicate !== Configure::read('Error.allowDuplicateDeprecations')) {
                Configure::write('Error.allowDuplicateDeprecations', $duplicate);
            }
        }
        $this->assertTrue($deprecation, 'Should have at least one deprecation warning');
    }

    /**
     * Setup the test case, backup the static object values so they can be restored.
     * Specifically backs up the contents of Configure and paths in App if they have
     * not already been backed up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupFixtures();

        if (!$this->_configure) {
            $this->_configure = Configure::read();
        }
        if (class_exists(Router::class, false)) {
            Router::reload();
        }

        EventManager::instance(new EventManager());
    }

    /**
     * teardown any static object changes and restore them.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->teardownFixtures();

        if ($this->_configure) {
            Configure::clear();
            Configure::write($this->_configure);
        }
        $this->getTableLocator()->clear();
        $this->_configure = [];
        $this->_tableLocator = null;
        if (class_exists(Mockery::class)) {
            Mockery::close();
        }
    }

    /**
     * Initialized and loads any use fixtures.
     *
     * @return void
     */
    protected function setupFixtures(): void
    {
        $fixtureNames = $this->getFixtures();

        $this->fixtureStrategy = $this->getFixtureStrategy();
        $this->fixtureStrategy->setupTest($fixtureNames);
    }

    /**
     * Unloads any use fixtures.
     *
     * @return void
     */
    protected function teardownFixtures(): void
    {
        if ($this->fixtureStrategy) {
            $this->fixtureStrategy->teardownTest();
            $this->fixtureStrategy = null;
        }
    }

    /**
     * Returns fixture strategy used by these tests.
     *
     * @return \Cake\TestSuite\Fixture\FixtureStrategyInterface
     */
    protected function getFixtureStrategy(): FixtureStrategyInterface
    {
        return new TruncateStrategy();
    }

    /**
     * Load routes for the application.
     *
     * If no application class can be found an exception will be raised.
     * Routes for plugins will *not* be loaded. Use `loadPlugins()` or use
     * `Cake\TestSuite\IntegrationTestCaseTrait` to better simulate all routes
     * and plugins being loaded.
     *
     * @param array|null $appArgs Constructor parameters for the application class.
     * @return void
     * @since 4.0.1
     */
    public function loadRoutes(?array $appArgs = null): void
    {
        $appArgs ??= [rtrim(CONFIG, DIRECTORY_SEPARATOR)];
        /** @var class-string $className */
        $className = Configure::read('App.namespace') . '\\Application';
        try {
            $reflect = new ReflectionClass($className);
            $app = $reflect->newInstanceArgs($appArgs);
            assert($app instanceof RoutingApplicationInterface);
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf('Cannot load `%s` to load routes from.', $className), 0, $e);
        }
        $builder = Router::createRouteBuilder('/');
        $app->routes($builder);
    }

    /**
     * Load plugins into a simulated application.
     *
     * Useful to test how plugins being loaded/not loaded interact with other
     * elements in CakePHP or applications.
     *
     * @param array<string, mixed> $plugins List of Plugins to load.
     * @return \Cake\Http\BaseApplication
     */
    public function loadPlugins(array $plugins = []): BaseApplication
    {
        /**
         * @psalm-suppress MissingTemplateParam
         */
        $app = new class ('') extends BaseApplication
        {
            /**
             * @param \Cake\Http\MiddlewareQueue $middlewareQueue
             * @return \Cake\Http\MiddlewareQueue
             */
            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }
        };

        foreach ($plugins as $pluginName => $config) {
            if (is_array($config)) {
                $app->addPlugin($pluginName, $config);
            } else {
                $app->addPlugin($config);
            }
        }
        $app->pluginBootstrap();
        $builder = Router::createRouteBuilder('/');
        $app->pluginRoutes($builder);

        return $app;
    }

    /**
     * Remove plugins from the global plugin collection.
     *
     * Useful in test case teardown methods.
     *
     * @param list<string> $names A list of plugins you want to remove.
     * @return void
     */
    public function removePlugins(array $names = []): void
    {
        $collection = Plugin::getCollection();
        foreach ($names as $name) {
            $collection->remove($name);
        }
    }

    /**
     * Clear all plugins from the global plugin collection.
     *
     * Useful in test case teardown methods.
     *
     * @return void
     */
    public function clearPlugins(): void
    {
        Plugin::getCollection()->clear();
    }

    /**
     * Asserts that a global event was fired. You must track events in your event manager for this assertion to work
     *
     * @param string $name Event name
     * @param \Cake\Event\EventManager|null $eventManager Event manager to check, defaults to global event manager
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertEventFired(string $name, ?EventManager $eventManager = null, string $message = ''): void
    {
        if (!$eventManager) {
            $eventManager = EventManager::instance();
        }
        $this->assertThat($name, new EventFired($eventManager), $message);
    }

    /**
     * Asserts an event was fired with data
     *
     * If a third argument is passed, that value is used to compare with the value in $dataKey
     *
     * @param string $name Event name
     * @param string $dataKey Data key
     * @param mixed $dataValue Data value
     * @param \Cake\Event\EventManager|null $eventManager Event manager to check, defaults to global event manager
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertEventFiredWith(
        string $name,
        string $dataKey,
        mixed $dataValue,
        ?EventManager $eventManager = null,
        string $message = ''
    ): void {
        if (!$eventManager) {
            $eventManager = EventManager::instance();
        }
        $this->assertThat($name, new EventFiredWith($eventManager, $dataKey, $dataValue), $message);
    }

    /**
     * Assert text equality, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $expected The expected value.
     * @param string $result The actual value.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextNotEquals(string $expected, string $result, string $message = ''): void
    {
        $expected = str_replace(["\r\n", "\r"], "\n", $expected);
        $result = str_replace(["\r\n", "\r"], "\n", $result);
        $this->assertNotEquals($expected, $result, $message);
    }

    /**
     * Assert text equality, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $expected The expected value.
     * @param string $result The actual value.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextEquals(string $expected, string $result, string $message = ''): void
    {
        $expected = str_replace(["\r\n", "\r"], "\n", $expected);
        $result = str_replace(["\r\n", "\r"], "\n", $result);
        $this->assertEquals($expected, $result, $message);
    }

    /**
     * Asserts that a string starts with a given prefix, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $prefix The prefix to check for.
     * @param string $string The string to search in.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextStartsWith(string $prefix, string $string, string $message = ''): void
    {
        $prefix = str_replace(["\r\n", "\r"], "\n", $prefix);
        $string = str_replace(["\r\n", "\r"], "\n", $string);
        $this->assertNotEmpty($prefix);
        $this->assertStringStartsWith($prefix, $string, $message);
    }

    /**
     * Asserts that a string starts not with a given prefix, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $prefix The prefix to not find.
     * @param string $string The string to search.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextStartsNotWith(string $prefix, string $string, string $message = ''): void
    {
        $prefix = str_replace(["\r\n", "\r"], "\n", $prefix);
        $string = str_replace(["\r\n", "\r"], "\n", $string);
        $this->assertNotEmpty($prefix);
        $this->assertStringStartsNotWith($prefix, $string, $message);
    }

    /**
     * Asserts that a string ends with a given prefix, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $suffix The suffix to find.
     * @param string $string The string to search.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextEndsWith(string $suffix, string $string, string $message = ''): void
    {
        $suffix = str_replace(["\r\n", "\r"], "\n", $suffix);
        $string = str_replace(["\r\n", "\r"], "\n", $string);
        $this->assertNotEmpty($suffix);
        $this->assertStringEndsWith($suffix, $string, $message);
    }

    /**
     * Asserts that a string ends not with a given prefix, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $suffix The suffix to not find.
     * @param string $string The string to search.
     * @param string $message The message to use for failure.
     * @return void
     */
    public function assertTextEndsNotWith(string $suffix, string $string, string $message = ''): void
    {
        $suffix = str_replace(["\r\n", "\r"], "\n", $suffix);
        $string = str_replace(["\r\n", "\r"], "\n", $string);
        $this->assertNotEmpty($suffix);
        $this->assertStringEndsNotWith($suffix, $string, $message);
    }

    /**
     * Assert that a string contains another string, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $needle The string to search for.
     * @param string $haystack The string to search through.
     * @param string $message The message to display on failure.
     * @param bool $ignoreCase Whether the search should be case-sensitive.
     * @return void
     */
    public function assertTextContains(
        string $needle,
        string $haystack,
        string $message = '',
        bool $ignoreCase = false
    ): void {
        $needle = str_replace(["\r\n", "\r"], "\n", $needle);
        $haystack = str_replace(["\r\n", "\r"], "\n", $haystack);

        if ($ignoreCase) {
            $this->assertStringContainsStringIgnoringCase($needle, $haystack, $message);
        } else {
            $this->assertStringContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert that a text doesn't contain another text, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $needle The string to search for.
     * @param string $haystack The string to search through.
     * @param string $message The message to display on failure.
     * @param bool $ignoreCase Whether the search should be case-sensitive.
     * @return void
     */
    public function assertTextNotContains(
        string $needle,
        string $haystack,
        string $message = '',
        bool $ignoreCase = false
    ): void {
        $needle = str_replace(["\r\n", "\r"], "\n", $needle);
        $haystack = str_replace(["\r\n", "\r"], "\n", $haystack);

        if ($ignoreCase) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $haystack, $message);
        } else {
            $this->assertStringNotContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert that a string matches SQL with db-specific characters like quotes removed.
     *
     * @param string $expected The expected sql
     * @param string $actual The sql to compare
     * @param string $message The message to display on failure
     * @return void
     */
    public function assertEqualsSql(
        string $expected,
        string $actual,
        string $message = ''
    ): void {
        $this->assertEquals($expected, preg_replace('/[`"\[\]]/', '', $actual), $message);
    }

    /**
     * Assertion for comparing a regex pattern against a query having its identifiers
     * quoted. It accepts queries quoted with the characters `<` and `>`. If the third
     * parameter is set to true, it will alter the pattern to both accept quoted and
     * unquoted queries
     *
     * @param string $pattern The expected sql pattern
     * @param string $actual The sql to compare
     * @param bool $optional Whether quote characters (marked with <>) are optional
     * @return void
     */
    public function assertRegExpSql(string $pattern, string $actual, bool $optional = false): void
    {
        $optional = $optional ? '?' : '';
        $pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
        $pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
        $this->assertMatchesRegularExpression('#' . $pattern . '#', $actual);
    }

    /**
     * Asserts HTML tags.
     *
     * Takes an array $expected and generates a regex from it to match the provided $string.
     * Samples for $expected:
     *
     * Checks for an input tag with a name attribute (contains any non-empty value) and an id
     * attribute that contains 'my-input':
     *
     * ```
     * ['input' => ['name', 'id' => 'my-input']]
     * ```
     *
     * Checks for two p elements with some text in them:
     *
     * ```
     * [
     *   ['p' => true],
     *   'textA',
     *   '/p',
     *   ['p' => true],
     *   'textB',
     *   '/p'
     * ]
     * ```
     *
     * You can also specify a pattern expression as part of the attribute values, or the tag
     * being defined, if you prepend the value with preg: and enclose it with slashes, like so:
     *
     * ```
     * [
     *   ['input' => ['name', 'id' => 'preg:/FieldName\d+/']],
     *   'preg:/My\s+field/'
     * ]
     * ```
     *
     * Important: This function is very forgiving about whitespace and also accepts any
     * permutation of attribute order. It will also allow whitespace between specified tags.
     *
     * @param array $expected An array, see above
     * @param string $string An HTML/XHTML/XML string
     * @param bool $fullDebug Whether more verbose output should be used.
     * @return bool
     */
    public function assertHtml(array $expected, string $string, bool $fullDebug = false): bool
    {
        $regex = [];
        $normalized = [];
        foreach ($expected as $key => $val) {
            if (!is_numeric($key)) {
                $normalized[] = [$key => $val];
            } else {
                $normalized[] = $val;
            }
        }
        $i = 0;
        foreach ($normalized as $tags) {
            if (!is_array($tags)) {
                $tags = (string)$tags;
            }
            $i++;
            if (is_string($tags) && $tags[0] === '<') {
                $tags = [substr($tags, 1) => []];
            } elseif (is_string($tags)) {
                $tagsTrimmed = preg_replace('/\s+/m', '', $tags);

                if (preg_match('/^\*?\//', $tags, $match) && $tagsTrimmed !== '//') {
                    $prefix = ['', ''];

                    if ($match[0] === '*/') {
                        $prefix = ['Anything, ', '.*?'];
                    }
                    $regex[] = [
                        sprintf('%sClose %s tag', $prefix[0], substr($tags, strlen($match[0]))),
                        sprintf('%s\s*<[\s]*\/[\s]*%s[\s]*>[\n\r]*', $prefix[1], substr($tags, strlen($match[0]))),
                        $i,
                    ];
                    continue;
                }
                if ($tags && preg_match('/^preg\:\/(.+)\/$/i', $tags, $matches)) {
                    $tags = $matches[1];
                    $type = 'Regex matches';
                } else {
                    $tags = '\s*' . preg_quote($tags, '/');
                    $type = 'Text equals';
                }
                $regex[] = [
                    sprintf('%s `%s`', $type, $tags),
                    $tags,
                    $i,
                ];
                continue;
            }
            foreach ($tags as $tag => $attributes) {
                $regex[] = [
                    sprintf('Open %s tag', $tag),
                    sprintf('[\s]*<%s', preg_quote($tag, '/')),
                    $i,
                ];
                if ($attributes === true) {
                    $attributes = [];
                }
                $attrs = [];
                $explanations = [];
                $i = 1;
                foreach ($attributes as $attr => $val) {
                    if (is_numeric($attr) && preg_match('/^preg:\/(.+)\/$/i', (string)$val, $matches)) {
                        $attrs[] = $matches[1];
                        $explanations[] = sprintf('Regex `%s` matches', $matches[1]);
                        continue;
                    }
                    $val = (string)$val;

                    $quotes = '["\']';
                    if (is_numeric($attr)) {
                        $attr = $val;
                        $val = '.+?';
                        $explanations[] = sprintf('Attribute `%s` present', $attr);
                    } elseif ($val && preg_match('/^preg:\/(.+)\/$/i', $val, $matches)) {
                        $val = str_replace(
                            ['.*', '.+'],
                            ['.*?', '.+?'],
                            $matches[1]
                        );
                        $quotes = $val !== $matches[1] ? '["\']' : '["\']?';

                        $explanations[] = sprintf('Attribute `%s` matches `%s`', $attr, $val);
                    } else {
                        $explanations[] = sprintf('Attribute `%s` == `%s`', $attr, $val);
                        $val = preg_quote($val, '/');
                    }
                    $attrs[] = '[\s]+' . preg_quote($attr, '/') . '=' . $quotes . $val . $quotes;
                    $i++;
                }
                if ($attrs) {
                    $regex[] = [
                        'explains' => $explanations,
                        'attrs' => $attrs,
                    ];
                }
                $regex[] = [
                    sprintf('End %s tag', $tag),
                    '[\s]*\/?[\s]*>[\n\r]*',
                    $i,
                ];
            }
        }

        foreach ($regex as $i => $assertion) {
            $matches = false;
            if (isset($assertion['attrs'])) {
                /**
                 * @var array<string, mixed> $assertion
                 * @var string $string
                 */
                $string = $this->_assertAttributes($assertion, $string, $fullDebug, $regex);
                if ($fullDebug === true && $string === false) {
                    debug($string, true);
                    debug($regex, true);
                }
                continue;
            }

            // If 'attrs' is not present then the array is just a regular int-offset one
            /**
             * @var array<int, mixed> $assertion
             */
            [$description, $expressions, $itemNum] = $assertion;
            $expression = '';
            foreach ((array)$expressions as $expression) {
                $expression = sprintf('/^%s/s', $expression);
                if ($string && preg_match($expression, $string, $match)) {
                    $matches = true;
                    $string = substr($string, strlen($match[0]));
                    break;
                }
            }
            if (!$matches) {
                if ($fullDebug === true) {
                    debug($string);
                    debug($regex);
                }
                $this->assertMatchesRegularExpression(
                    $expression,
                    (string)$string,
                    sprintf('Item #%d / regex #%d failed: %s', $itemNum, $i, $description)
                );

                return false;
            }
        }

        $this->assertTrue(true, '%s');

        return true;
    }

    /**
     * Check the attributes as part of an assertTags() check.
     *
     * @param array<string, mixed> $assertions Assertions to run.
     * @param string $string The HTML string to check.
     * @param bool $fullDebug Whether more verbose output should be used.
     * @param array|string $regex Full regexp from `assertHtml`
     * @return string|false
     */
    protected function _assertAttributes(
        array $assertions,
        string $string,
        bool $fullDebug = false,
        array|string $regex = ''
    ): string|false {
        $asserts = $assertions['attrs'];
        $explains = $assertions['explains'];
        do {
            $matches = false;
            $j = null;
            foreach ($asserts as $j => $assert) {
                if (preg_match(sprintf('/^%s/s', $assert), $string, $match)) {
                    $matches = true;
                    $string = substr($string, strlen($match[0]));
                    array_splice($asserts, $j, 1);
                    array_splice($explains, $j, 1);
                    break;
                }
            }
            if ($matches === false) {
                if ($fullDebug === true) {
                    debug($string);
                    debug($regex);
                }
                $this->assertTrue(false, 'Attribute did not match. Was expecting ' . $explains[$j]);
            }
            $len = count($asserts);
        } while ($len > 0);

        return $string;
    }

    /**
     * Normalize a path for comparison.
     *
     * @param string $path Path separated by "/" slash.
     * @return string Normalized path separated by DIRECTORY_SEPARATOR.
     */
    protected function _normalizePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

// phpcs:disable

    /**
     * Compatibility function to test if a value is between an acceptable range.
     *
     * @param float $expected
     * @param float $result
     * @param float $margin the rage of acceptation
     * @param string $message the text to display if the assertion is not correct
     * @return void
     */
    protected static function assertWithinRange($expected, $result, $margin, $message = '')
    {
        $upper = $result + $margin;
        $lower = $result - $margin;
        static::assertTrue(($expected <= $upper) && ($expected >= $lower), $message);
    }

    /**
     * Compatibility function to test if a value is not between an acceptable range.
     *
     * @param float $expected
     * @param float $result
     * @param float $margin the rage of acceptation
     * @param string $message the text to display if the assertion is not correct
     * @return void
     */
    protected static function assertNotWithinRange($expected, $result, $margin, $message = '')
    {
        $upper = $result + $margin;
        $lower = $result - $margin;
        static::assertTrue(($expected > $upper) || ($expected < $lower), $message);
    }

    /**
     * Compatibility function to test paths.
     *
     * @param string $expected
     * @param string $result
     * @param string $message the text to display if the assertion is not correct
     * @return void
     */
    protected static function assertPathEquals($expected, $result, $message = '')
    {
        $expected = str_replace(DIRECTORY_SEPARATOR, '/', $expected);
        $result = str_replace(DIRECTORY_SEPARATOR, '/', $result);
        static::assertEquals($expected, $result, $message);
    }

    /**
     * Compatibility function for skipping.
     *
     * @param bool $condition Condition to trigger skipping
     * @param string $message Message for skip
     * @return bool
     */
    protected function skipUnless($condition, $message = '')
    {
        if (!$condition) {
            $this->markTestSkipped($message);
        }

        return $condition;
    }

// phpcs:enable

    /**
     * Mock a model, maintain fixtures and table association
     *
     * @param string $alias The model to get a mock for.
     * @param list<string> $methods The list of methods to mock
     * @param array<string, mixed> $options The config data for the mock's constructor.
     * @throws \Cake\ORM\Exception\MissingTableClassException
     * @return \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockForModel(string $alias, array $methods = [], array $options = []): Table|MockObject
    {
        $className = $this->_getTableClassName($alias, $options);
        $connectionName = $className::defaultConnectionName();
        $connection = ConnectionManager::get($connectionName);

        $locator = $this->getTableLocator();

        [, $baseClass] = pluginSplit($alias);
        $options += ['alias' => $baseClass, 'connection' => $connection];
        $options += $locator->getConfig($alias);
        $reflection = new ReflectionClass($className);
        $classMethods = array_map(function ($method) {
            return $method->name;
        }, $reflection->getMethods());

        $existingMethods = array_intersect($classMethods, $methods);
        $nonExistingMethods = array_diff($methods, $existingMethods);

        $builder = $this->getMockBuilder($className)
            ->setConstructorArgs([$options]);

        if ($existingMethods || !$nonExistingMethods) {
            $builder->onlyMethods($existingMethods);
        }

        if ($nonExistingMethods) {
            trigger_error('Adding non existent methods to your model ' .
                'via testing will not work in future PHPUnit versions.', E_USER_DEPRECATED);
            $builder->addMethods($nonExistingMethods);
        }

        /** @var \Cake\ORM\Table $mock */
        $mock = $builder->getMock();

        if (empty($options['entityClass']) && $mock->getEntityClass() === Entity::class) {
            $parts = explode('\\', $className);
            $entityAlias = Inflector::classify(Inflector::underscore(substr(array_pop($parts), 0, -5)));
            $entityClass = implode('\\', array_slice($parts, 0, -1)) . '\\Entity\\' . $entityAlias;
            if (class_exists($entityClass)) {
                $mock->setEntityClass($entityClass);
            }
        }

        if (stripos($mock->getTable(), 'mock') === 0) {
            $mock->setTable(Inflector::tableize($baseClass));
        }

        $locator->set($baseClass, $mock);
        $locator->set($alias, $mock);

        return $mock;
    }

    /**
     * Gets the class name for the table.
     *
     * @param string $alias The model to get a mock for.
     * @param array<string, mixed> $options The config data for the mock's constructor.
     * @return class-string<\Cake\ORM\Table>
     * @throws \Cake\ORM\Exception\MissingTableClassException
     */
    protected function _getTableClassName(string $alias, array $options): string
    {
        if (empty($options['className'])) {
            $class = Inflector::camelize($alias);
            /** @var class-string<\Cake\ORM\Table>|null $className */
            $className = App::className($class, 'Model/Table', 'Table');
            if (!$className) {
                throw new MissingTableClassException([$alias]);
            }
            $options['className'] = $className;
        }

        return $options['className'];
    }

    /**
     * Set the app namespace
     *
     * @param string $appNamespace The app namespace, defaults to "TestApp".
     * @return string|null The previous app namespace or null if not set.
     */
    public static function setAppNamespace(string $appNamespace = 'TestApp'): ?string
    {
        $previous = Configure::read('App.namespace');
        Configure::write('App.namespace', $appNamespace);

        return $previous;
    }

    /**
     * Adds a fixture to this test case.
     *
     * Examples:
     * - core.Tags
     * - app.MyRecords
     * - plugin.MyPluginName.MyModelName
     *
     * Use this method inside your test cases' {@link getFixtures()} method
     * to build up the fixture list.
     *
     * @param string $fixture Fixture
     * @return $this
     */
    protected function addFixture(string $fixture)
    {
        $this->fixtures[] = $fixture;

        return $this;
    }

    /**
     * Get the fixtures this test should use.
     *
     * @return array<string>
     */
    public function getFixtures(): array
    {
        return $this->fixtures;
    }

    /**
     * @param string $regex A regex to match against the warning message
     * @param \Closure $callable Callable which should trigger the warning
     * @return void
     * @throws \Exception
     */
    public function expectNoticeMessageMatches(string $regex, Closure $callable): void
    {
        $this->expectErrorHandlerMessageMatches($regex, $callable, E_USER_NOTICE);
    }

    /**
     * @param string $regex A regex to match against the deprecation message
     * @param \Closure $callable Callable which should trigger the warning
     * @return void
     * @throws \Exception
     */
    public function expectDeprecationMessageMatches(string $regex, Closure $callable): void
    {
        $this->expectErrorHandlerMessageMatches($regex, $callable, E_USER_DEPRECATED);
    }

    /**
     * @param string $regex A regex to match against the warning message
     * @param \Closure $callable Callable which should trigger the warning
     * @return void
     * @throws \Exception
     */
    public function expectWarningMessageMatches(string $regex, Closure $callable): void
    {
        $this->expectErrorHandlerMessageMatches($regex, $callable, E_USER_WARNING);
    }

    /**
     * @param string $regex A regex to match against the error message
     * @param \Closure $callable Callable which should trigger the warning
     * @return void
     * @throws \Exception
     */
    public function expectErrorMessageMatches(string $regex, Closure $callable): void
    {
        $this->expectErrorHandlerMessageMatches($regex, $callable, E_ERROR | E_USER_ERROR);
    }

    /**
     * @param string $regex A regex to match against the warning message
     * @param \Closure $callable Callable which should trigger the warning
     * @param int $errorLevel The error level to listen to
     * @return void
     * @throws \Exception
     */
    protected function expectErrorHandlerMessageMatches(string $regex, Closure $callable, int $errorLevel): void
    {
        set_error_handler(static function (int $errno, string $errstr): never {
            throw new Exception($errstr, $errno);
        }, $errorLevel);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches($regex);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }
    }
}
