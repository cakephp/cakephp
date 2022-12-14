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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

/**
 * Fixture strategy that truncates all fixture ables at the end of test.
 */
class TruncateFixtureStrategy implements FixtureStrategyInterface
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureHelper
     */
    protected FixtureHelper $helper;

    /**
     * @var array<\Cake\Datasource\FixtureInterface>
     */
    protected array $fixtures = [];

    /**
     * Initialize strategy.
     */
    public function __construct()
    {
        $this->helper = new FixtureHelper();
    }

    /**
     * @inheritDoc
     */
    public function setupTest(array $fixtureNames): void
    {
        $this->fixtures = $this->helper->loadFixtures($fixtureNames);
        $this->helper->insert($this->fixtures);
    }

    /**
     * @inheritDoc
     */
    public function teardownTest(): void
    {
        $this->helper->truncate($this->fixtures);
    }
}
