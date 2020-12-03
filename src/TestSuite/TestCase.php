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
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\ORM\Entity;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Cake\TestSuite\Constraint\EventFired;
use Cake\TestSuite\Constraint\EventFiredWith;
use Cake\Utility\Inflector;
use LogicException;
use PHPUnit\Framework\Constraint\DirectoryExists;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Cake TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use LocatorAwareTrait;

    /**
     * The class responsible for managing the creation, loading and removing of fixtures
     *
     * @var \Cake\TestSuite\Fixture\FixtureManager|null
     */
    public $fixtureManager;

    /**
     * Fixtures used by this test case.
     *
     * @var string[]
     */
    protected $fixtures = [];

    /**
     * By default, all fixtures attached to this class will be truncated and reloaded after each test.
     * Set this to false to handle manually
     *
     * @var bool
     */
    public $autoFixtures = true;

    /**
     * Control table create/drops on each test method.
     *
     * If true, tables will still be dropped at the
     * end of each test runner execution.
     *
     * @var bool
     */
    public $dropTables = false;

    /**
     * Configure values to restore at end of test.
     *
     * @var array
     */
    protected $_configure = [];

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern Regex pattern
     * @param string $string String to test
     * @param string $message Message
     * @return void
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @codeCoverageIgnore
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        static::assertThat($string, new RegularExpression($pattern), $message);
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @param string $pattern Regex pattern
     * @param string $string String to test
     * @param string $message Message
     * @return void
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public static function assertDoesNotMatchRegularExpression(
        string $pattern,
        string $string,
        string $message = ''
    ): void {
        static::assertThat(
            $string,
            new LogicalNot(
                new RegularExpression($pattern)
            ),
            $message
        );
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param string $filename Filename
     * @param string $message Message
     * @return void
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @codeCoverageIgnore
     */
    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        static::assertThat($filename, new LogicalNot(new FileExists()), $message);
    }

    /**
     * Asserts that a directory does not exist.
     *
     * @param string $directory Directory
     * @param string $message Message
     * @return void
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @codeCoverageIgnore
     */
    public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
    {
        static::assertThat($directory, new LogicalNot(new DirectoryExists()), $message);
    }

    /**
     * Overrides SimpleTestCase::skipIf to provide a boolean return value
     *
     * @param bool $shouldSkip Whether or not the test should be skipped.
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
     * Helper method for check deprecation methods
     *
     * @param callable $callable callable function that will receive asserts
     * @return void
     */
    public function deprecated(callable $callable): void
    {
        $errorLevel = error_reporting();
        error_reporting(E_ALL ^ E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            error_reporting($errorLevel);
        }
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
        if ($this->_configure) {
            Configure::clear();
            Configure::write($this->_configure);
        }
        $this->getTableLocator()->clear();
        $this->_configure = [];
        $this->_tableLocator = null;
        $this->fixtureManager = null;
    }

    /**
     * Chooses which fixtures to load for a given test
     *
     * Each parameter is a model name that corresponds to a fixture, i.e. 'Posts', 'Authors', etc.
     * Passing no parameters will cause all fixtures on the test case to load.
     *
     * @return void
     * @see \Cake\TestSuite\TestCase::$autoFixtures
     * @throws \RuntimeException when no fixture manager is available.
     */
    public function loadFixtures(): void
    {
        if ($this->autoFixtures) {
            throw new RuntimeException('Cannot use `loadFixtures()` with `$autoFixtures` enabled.');
        }
        if ($this->fixtureManager === null) {
            throw new RuntimeException('No fixture manager to load the test fixture');
        }

        $args = func_get_args();
        foreach ($args as $class) {
            $this->fixtureManager->loadSingle($class, null, $this->dropTables);
        }

        if (empty($args)) {
            $autoFixtures = $this->autoFixtures;
            $this->autoFixtures = true;
            $this->fixtureManager->load($this);
            $this->autoFixtures = $autoFixtures;
        }
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
        $appArgs = $appArgs ?? [rtrim(CONFIG, DIRECTORY_SEPARATOR)];
        /** @psalm-var class-string */
        $className = Configure::read('App.namespace') . '\\Application';
        try {
            $reflect = new ReflectionClass($className);
            /** @var \Cake\Routing\RoutingApplicationInterface $app */
            $app = $reflect->newInstanceArgs($appArgs);
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf('Cannot load "%s" to load routes from.', $className), 0, $e);
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
     * @param array $plugins List of Plugins to load.
     * @return \Cake\Http\BaseApplication
     */
    public function loadPlugins(array $plugins = []): BaseApplication
    {
        /** @var \Cake\Http\BaseApplication $app */
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            ['']
        );

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
     * @param string[] $names A list of plugins you want to remove.
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
        $dataValue,
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
        $this->assertStringEndsNotWith($suffix, $string, $message);
    }

    /**
     * Assert that a string contains another string, ignoring differences in newlines.
     * Helpful for doing cross platform tests of blocks of text.
     *
     * @param string $needle The string to search for.
     * @param string $haystack The string to search through.
     * @param string $message The message to display on failure.
     * @param bool $ignoreCase Whether or not the search should be case-sensitive.
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
     * @param bool $ignoreCase Whether or not the search should be case-sensitive.
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
     * @param bool $fullDebug Whether or not more verbose output should be used.
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
                /** @psalm-suppress InvalidArrayOffset */
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
                if (!empty($tags) && preg_match('/^preg\:\/(.+)\/$/i', $tags, $matches)) {
                    $tags = $matches[1];
                    $type = 'Regex matches';
                } else {
                    $tags = '\s*' . preg_quote($tags, '/');
                    $type = 'Text equals';
                }
                $regex[] = [
                    sprintf('%s "%s"', $type, $tags),
                    $tags,
                    $i,
                ];
                continue;
            }
            foreach ($tags as $tag => $attributes) {
                /** @psalm-suppress PossiblyFalseArgument */
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
                    if (is_numeric($attr) && preg_match('/^preg\:\/(.+)\/$/i', (string)$val, $matches)) {
                        $attrs[] = $matches[1];
                        $explanations[] = sprintf('Regex "%s" matches', $matches[1]);
                        continue;
                    }
                    $val = (string)$val;

                    $quotes = '["\']';
                    if (is_numeric($attr)) {
                        $attr = $val;
                        $val = '.+?';
                        $explanations[] = sprintf('Attribute "%s" present', $attr);
                    } elseif (!empty($val) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
                        $val = str_replace(
                            ['.*', '.+'],
                            ['.*?', '.+?'],
                            $matches[1]
                        );
                        $quotes = $val !== $matches[1] ? '["\']' : '["\']?';

                        $explanations[] = sprintf('Attribute "%s" matches "%s"', $attr, $val);
                    } else {
                        $explanations[] = sprintf('Attribute "%s" == "%s"', $attr, $val);
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
                /** @psalm-suppress PossiblyFalseArgument */
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
                $string = $this->_assertAttributes($assertion, $string, $fullDebug, $regex);
                if ($fullDebug === true && $string === false) {
                    debug($string, true);
                    debug($regex, true);
                }
                continue;
            }

            // If 'attrs' is not present then the array is just a regular int-offset one
            /** @psalm-suppress PossiblyUndefinedArrayOffset */
            [$description, $expressions, $itemNum] = $assertion;
            $expression = '';
            foreach ((array)$expressions as $expression) {
                $expression = sprintf('/^%s/s', $expression);
                if (preg_match($expression, $string, $match)) {
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
                    $string,
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
     * @param array $assertions Assertions to run.
     * @param string $string The HTML string to check.
     * @param bool $fullDebug Whether or not more verbose output should be used.
     * @param array|string $regex Full regexp from `assertHtml`
     * @return string|false
     */
    protected function _assertAttributes(array $assertions, string $string, bool $fullDebug = false, $regex = '')
    {
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
     * @param string[] $methods The list of methods to mock
     * @param array $options The config data for the mock's constructor.
     * @throws \Cake\ORM\Exception\MissingTableClassException
     * @return \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockForModel(string $alias, array $methods = [], array $options = [])
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
     * @param array $options The config data for the mock's constructor.
     * @return string
     * @throws \Cake\ORM\Exception\MissingTableClassException
     * @psalm-return class-string<\Cake\ORM\Table>
     */
    protected function _getTableClassName(string $alias, array $options): string
    {
        if (empty($options['className'])) {
            $class = Inflector::camelize($alias);
            /** @psalm-var class-string<\Cake\ORM\Table>|null */
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
     * Gets fixtures.
     *
     * @return string[]
     */
    public function getFixtures(): array
    {
        return $this->fixtures;
    }
}
