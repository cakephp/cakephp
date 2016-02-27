<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventManager;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Exception;
use PHPUnit_Framework_TestCase;

/**
 * Cake TestCase class
 *
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * The class responsible for managing the creation, loading and removing of fixtures
     *
     * @var \Cake\TestSuite\Fixture\FixtureManager
     */
    public $fixtureManager = null;

    /**
     * By default, all fixtures attached to this class will be truncated and reloaded after each test.
     * Set this to false to handle manually
     *
     * @var array
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
     * Path settings to restore at the end of the test.
     *
     * @var array
     */
    protected $_pathRestore = [];

    /**
     * Overrides SimpleTestCase::skipIf to provide a boolean return value
     *
     * @param bool $shouldSkip Whether or not the test should be skipped.
     * @param string $message The message to display.
     * @return bool
     */
    public function skipIf($shouldSkip, $message = '')
    {
        if ($shouldSkip) {
            $this->markTestSkipped($message);
        }
        return $shouldSkip;
    }

    /**
     * Setup the test case, backup the static object values so they can be restored.
     * Specifically backs up the contents of Configure and paths in App if they have
     * not already been backed up.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        if (empty($this->_configure)) {
            $this->_configure = Configure::read();
        }
        if (class_exists('Cake\Routing\Router', false)) {
            Router::reload();
        }

        EventManager::instance(new EventManager());
    }

    /**
     * teardown any static object changes and restore them.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        if (!empty($this->_configure)) {
            Configure::clear();
            Configure::write($this->_configure);
        }
    }

    /**
     * Chooses which fixtures to load for a given test
     *
     * Each parameter is a model name that corresponds to a fixture, i.e. 'Posts', 'Authors', etc.
     *
     * @return void
     * @see \Cake\TestSuite\TestCase::$autoFixtures
     * @throws \Exception when no fixture manager is available.
     */
    public function loadFixtures()
    {
        if (empty($this->fixtureManager)) {
            throw new Exception('No fixture manager to load the test fixture');
        }
        $args = func_get_args();
        foreach ($args as $class) {
            $this->fixtureManager->loadSingle($class, null, $this->dropTables);
        }
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
    public function assertTextNotEquals($expected, $result, $message = '')
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
    public function assertTextEquals($expected, $result, $message = '')
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
    public function assertTextStartsWith($prefix, $string, $message = '')
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
    public function assertTextStartsNotWith($prefix, $string, $message = '')
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
    public function assertTextEndsWith($suffix, $string, $message = '')
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
    public function assertTextEndsNotWith($suffix, $string, $message = '')
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
    public function assertTextContains($needle, $haystack, $message = '', $ignoreCase = false)
    {
        $needle = str_replace(["\r\n", "\r"], "\n", $needle);
        $haystack = str_replace(["\r\n", "\r"], "\n", $haystack);
        $this->assertContains($needle, $haystack, $message, $ignoreCase);
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
    public function assertTextNotContains($needle, $haystack, $message = '', $ignoreCase = false)
    {
        $needle = str_replace(["\r\n", "\r"], "\n", $needle);
        $haystack = str_replace(["\r\n", "\r"], "\n", $haystack);
        $this->assertNotContains($needle, $haystack, $message, $ignoreCase);
    }

    /**
     * Asserts HTML tags.
     *
     * @param string $string An HTML/XHTML/XML string
     * @param array $expected An array, see above
     * @param bool $fullDebug Whether or not more verbose output should be used.
     * @return void
     * @deprecated 3.0. Use assertHtml() instead.
     */
    public function assertTags($string, $expected, $fullDebug = false)
    {
        trigger_error(
            'assertTags() is deprecated, use assertHtml() instead.',
            E_USER_DEPRECATED
        );
        static::assertHtml($expected, $string, $fullDebug);
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
    public function assertHtml($expected, $string, $fullDebug = false)
    {
        $regex = [];
        $normalized = [];
        foreach ((array)$expected as $key => $val) {
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
            if (is_string($tags) && $tags{0} === '<') {
                $tags = [substr($tags, 1) => []];
            } elseif (is_string($tags)) {
                $tagsTrimmed = preg_replace('/\s+/m', '', $tags);

                if (preg_match('/^\*?\//', $tags, $match) && $tagsTrimmed !== '//') {
                    $prefix = [null, null];

                    if ($match[0] === '*/') {
                        $prefix = ['Anything, ', '.*?'];
                    }
                    $regex[] = [
                        sprintf('%sClose %s tag', $prefix[0], substr($tags, strlen($match[0]))),
                        sprintf('%s<[\s]*\/[\s]*%s[\s]*>[\n\r]*', $prefix[1], substr($tags, strlen($match[0]))),
                        $i,
                    ];
                    continue;
                }
                if (!empty($tags) && preg_match('/^preg\:\/(.+)\/$/i', $tags, $matches)) {
                    $tags = $matches[1];
                    $type = 'Regex matches';
                } else {
                    $tags = preg_quote($tags, '/');
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
                    if (is_numeric($attr) && preg_match('/^preg\:\/(.+)\/$/i', $val, $matches)) {
                        $attrs[] = $matches[1];
                        $explanations[] = sprintf('Regex "%s" matches', $matches[1]);
                        continue;
                    }

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

            list($description, $expressions, $itemNum) = $assertion;
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
                $this->assertRegExp($expression, $string, sprintf('Item #%d / regex #%d failed: %s', $itemNum, $i, $description));
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
     * @param array $regex Full regexp from `assertHtml`
     * @return string
     */
    protected function _assertAttributes($assertions, $string, $fullDebug = false, $regex = '')
    {
        $asserts = $assertions['attrs'];
        $explains = $assertions['explains'];
        do {
            $matches = false;
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
    protected function _normalizePath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

// @codingStandardsIgnoreStart

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
        static::assertTrue((($expected <= $upper) && ($expected >= $lower)), $message);
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
        static::assertTrue((($expected > $upper) || ($expected < $lower)), $message);
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

// @codingStandardsIgnoreEnd

    /**
     * Mock a model, maintain fixtures and table association
     *
     * @param string $alias The model to get a mock for.
     * @param mixed $methods The list of methods to mock
     * @param array $options The config data for the mock's constructor.
     * @throws \Cake\ORM\Exception\MissingTableClassException
     * @return \Cake\ORM\Table|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockForModel($alias, array $methods = [], array $options = [])
    {
        if (empty($options['className'])) {
            $class = Inflector::camelize($alias);
            $className = App::className($class, 'Model/Table', 'Table');
            if (!$className) {
                throw new MissingTableClassException([$alias]);
            }
            $options['className'] = $className;
        }

        $connectionName = $options['className']::defaultConnectionName();
        $connection = ConnectionManager::get($connectionName);

        list(, $baseClass) = pluginSplit($alias);
        $options += ['alias' => $baseClass, 'connection' => $connection];
        $options += TableRegistry::config($alias);

        $mock = $this->getMock($options['className'], $methods, [$options]);

        if (empty($options['entityClass']) && $mock->entityClass() === '\Cake\ORM\Entity') {
            $parts = explode('\\', $options['className']);
            $entityAlias = Inflector::singularize(substr(array_pop($parts), 0, -5));
            $entityClass = implode('\\', array_slice($parts, 0, -1)) . '\Entity\\' . $entityAlias;
            if (class_exists($entityClass)) {
                $mock->entityClass($entityClass);
            }
        }

        TableRegistry::set($baseClass, $mock);
        return $mock;
    }
}
