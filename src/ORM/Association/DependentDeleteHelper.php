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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;

/**
 * Helper class for cascading deletes in associations.
 *
 * @internal
 */
class DependentDeleteHelper
{
    /**
     * Cascade a delete to remove dependent records.
     *
     * This method does nothing if the association is not dependent.
     *
     * @param \Cake\ORM\Association $association The association callbacks are being cascaded on.
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array<string, mixed> $options The options for the original delete.
     * @return bool Success.
     */
    public function cascadeDelete(Association $association, EntityInterface $entity, array $options = []): bool
    {
        if (!$association->getDependent()) {
            return true;
        }
        $table = $association->getTarget();
        /** @var callable $callable */
        $callable = $association->aliasField(...);
        $foreignKey = array_map($callable, (array)$association->getForeignKey());
        $bindingKey = (array)$association->getBindingKey();
        $bindingValue = $entity->extract($bindingKey);
        if (in_array(null, $bindingValue, true)) {
            return true;
        }
        $conditions = array_combine($foreignKey, $bindingValue);

        if ($association->getCascadeCallbacks()) {
            foreach ($association->find()->where($conditions)->all()->toList() as $related) {
                $success = $table->delete($related, $options);
                if (!$success) {
                    return false;
                }
            }

            return true;
        }

        $association->deleteAll($conditions);

        return true;
    }
}
