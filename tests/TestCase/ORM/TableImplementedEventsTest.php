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
use Cake\ORM\TableEventsTrait;
use Cake\TestSuite\TestCase;

class TableImplementedEventsTest extends TestCase
{
    /**
     * Check that defining methods inside table classes will result in event listeners
     */
    public function testImplementedEvents(): void
    {
        $table = new ImplementedEventsTable();
        $result = $table->implementedEvents();
        $expected = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.buildValidator' => 'buildValidator',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.afterRules' => 'afterRules',
        ];
        $this->assertEquals($expected, $result, 'Events do not match.');
    }

    public function testImplementedEventsWithTableEventsTrait(): void
    {
        $table = new ImplementedAllEventsTable();
        $result = $table->implementedEvents();
        $expected = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.afterMarshal' => 'afterMarshal',
            'Model.buildValidator' => 'buildValidator',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.afterSaveCommit' => 'afterSaveCommit',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.afterDeleteCommit' => 'afterDeleteCommit',
            'Model.beforeRules' => 'beforeRules',
            'Model.afterRules' => 'afterRules',
        ];
        $this->assertEquals($expected, $result, 'Events do not match.');
    }
}

// phpcs:disable
class ImplementedEventsTable extends Table
{
    public function buildValidator(): void {}
    public function beforeMarshal(): void {}
    public function beforeFind(): void {}
    public function beforeSave(): void {}
    public function afterSave(): void {}
    public function beforeDelete(): void {}
    public function afterDelete(): void {}
    public function afterRules(): void {}
}

class ImplementedAllEventsTable extends Table
{
    use TableEventsTrait;
}
// phpcs:enable
