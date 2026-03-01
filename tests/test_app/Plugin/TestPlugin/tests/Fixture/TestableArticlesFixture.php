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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * Test fixture for verifying plugin alias detection.
 */
class TestableArticlesFixture extends TestFixture
{
    /**
     * Skip schema reflection for testing purposes.
     */
    protected function _schemaFromReflection(): void
    {
        $this->_schema = new TableSchema('articles', []);
    }

    /**
     * Expose the protected _aliasFromClass method for testing.
     */
    public function getAliasFromClass(): string
    {
        return $this->_aliasFromClass();
    }
}
