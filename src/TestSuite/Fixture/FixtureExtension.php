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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\TestSuite\TestCase;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;
use ReflectionClass;

final class FixtureExtension implements AfterLastTestHook, AfterTestHook, BeforeTestHook
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureManager
     */
    protected $manager;

    /**
     * Constructed according to the <extensions> section in phpunit.xml.
     *
     * @param \Cake\TestSuite\Fixture\FixtureManager $manager Fixture manager
     * @param array $options Options defined in extension definition
     */
    public function __construct(FixtureManager $manager, array $options = [])
    {
        $this->manager = $manager;

        if (!empty($options['debug'])) {
            $this->manager->setDebug(true);
        }
    }

    /**
     * Runs after the last tests in the test suite.
     *
     * @return void
     */
    public function executeAfterLastTest(): void
    {
        $this->manager->shutDown();
    }

    /**
     * Runs before each test in test case.
     *
     * @param string $test The test name
     * @return void
     */
    public function executeBeforeTest(string $test): void
    {
        $className = substr($test, 0, strpos($test, ':'));
        $className::$fixtureManager = $this->manager;

        $class = new ReflectionClass($className);
        if ($class->isSubclassOf(TestCase::class)) {
            $this->manager->fixturize($className);
            $this->manager->load($className);
        }
    }

    /**
     * Runs after each test in test case.
     *
     * @param string $test The test name
     * @param float $time Test execution time
     * @return void
     */
    public function executeAfterTest(string $test, float $time): void
    {
        $className = substr($test, 0, strpos($test, ':'));
        $className::$fixtureManager = null;

        $class = new ReflectionClass($className);
        if ($class->isSubclassOf(TestCase::class)) {
            $this->manager->unload($className);
        }
    }
}
