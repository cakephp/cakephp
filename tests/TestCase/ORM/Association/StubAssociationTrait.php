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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Closure;

trait StubAssociationTrait
{
    public function type(): string
    {
        return 'stub';
    }

    public function eagerLoader(array $options): Closure
    {
        return function ($results) {
            return $results;
        };
    }

    public function cascadeDelete(EntityInterface $entity, array $options = []): bool
    {
        return true;
    }

    public function isOwningSide(Table $side): bool
    {
        return true;
    }

    public function saveAssociated(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        return $entity;
    }
}
