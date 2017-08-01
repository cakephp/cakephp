<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @param array $options The options for the original delete.
     * @return bool Success.
     */
    public function cascadeDelete(Association $association, EntityInterface $entity, array $options = [])
    {
        if (!$association->getDependent()) {
            return true;
        }
        $table = $association->getTarget();
        $foreignKey = (array)$association->getForeignKey();
        $bindingKey = (array)$association->getBindingKey();
        $conditions = array_combine($foreignKey, $entity->extract($bindingKey));

        if ($association->getCascadeCallbacks()) {
            foreach ($association->find()->where($conditions)->all()->toList() as $related) {
                $table->delete($related, $options);
            }

            return true;
        }
        $conditions = array_merge($conditions, $association->getConditions());

        return (bool)$table->deleteAll($conditions);
    }
}
