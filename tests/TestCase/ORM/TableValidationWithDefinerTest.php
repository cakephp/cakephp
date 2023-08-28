<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

class TableValidationWithDefinerTest extends TestCase
{
    /**
     * Tests that it is possible to define custom validator methods
     */
    public function testValidationWithDefinerTest(): void
    {
        $table = new ValidationWithDefinerTable();
        $other = $table->getValidator('forOtherStuff');
        $this->assertNotSame($other, $table->getValidator());
        $this->assertSame($table, $other->getProvider('table'));
    }
}

// phpcs:disable
class ValidationWithDefinerTable extends Table
{
    public function validationForOtherStuff($validator)
    {
        return $validator;
    }
}
// phpcs:enable
