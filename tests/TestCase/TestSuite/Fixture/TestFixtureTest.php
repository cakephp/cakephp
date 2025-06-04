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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Core\Exception\CakeException;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;

class TestFixtureTest extends TestCase
{
    public function testStrictFields(): void
    {
        $fixture = new class extends TestFixture {
            public string $table = 'my_table';
            protected bool $strictFields = true;

            public function init(): void
            {
                parent::init();
                $this->records = [
                    [
                        'non_existent_field' => 'value',
                    ],
                ];
            }

            protected function _schemaFromReflection(): void
            {
                $this->_schema = new TableSchema(
                    'my_table',
                    [],
                );
            }
        };

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Record #0 in fixture has additional fields that do not exist in the schema.' .
            ' Remove the following fields: ["non_existent_field"]');
        $fixture->insert(ConnectionManager::get('test'));
    }
}
