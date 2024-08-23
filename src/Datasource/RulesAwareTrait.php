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
 * @since         3.0.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use ArrayObject;
use Cake\Event\EventDispatcherInterface;

/**
 * A trait that allows a class to build and apply application.
 * rules.
 *
 * If the implementing class also implements EventAwareTrait, then
 * events will be emitted when rules are checked.
 *
 * The implementing class is expected to define the `RULES_CLASS` constant
 * if they need to customize which class is used for rules objects.
 */
trait RulesAwareTrait
{
    /**
     * The domain rules to be applied to entities saved by this table
     *
     * @var \Cake\Datasource\RulesChecker|null
     */
    protected ?RulesChecker $_rulesChecker = null;

    /**
     * Returns whether the passed entity complies with all the rules stored in
     * the rules checker.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param string $operation The operation being run. Either 'create', 'update' or 'delete'.
     * @param \ArrayObject<string, mixed>|array|null $options The options To be passed to the rules.
     * @return bool
     */
    public function checkRules(
        EntityInterface $entity,
        string $operation = RulesChecker::CREATE,
        ArrayObject|array|null $options = null
    ): bool {
        $rules = $this->rulesChecker();
        $options = $options ?: new ArrayObject();
        $options = is_array($options) ? new ArrayObject($options) : $options;
        $hasEvents = ($this instanceof EventDispatcherInterface);

        if ($hasEvents) {
            $event = $this->dispatchEvent(
                'Model.beforeRules',
                compact('entity', 'options', 'operation')
            );
            if ($event->isStopped()) {
                return $event->getResult();
            }
        }

        $result = $rules->check($entity, $operation, $options->getArrayCopy());

        if ($hasEvents) {
            $event = $this->dispatchEvent(
                'Model.afterRules',
                compact('entity', 'options', 'result', 'operation')
            );

            if ($event->isStopped()) {
                return $event->getResult();
            }
        }

        return $result;
    }

    /**
     * Returns the RulesChecker for this instance.
     *
     * A RulesChecker object is used to test an entity for validity
     * on rules that may involve complex logic or data that
     * needs to be fetched from relevant datasources.
     *
     * @see \Cake\Datasource\RulesChecker
     * @return \Cake\Datasource\RulesChecker
     */
    public function rulesChecker(): RulesChecker
    {
        if ($this->_rulesChecker !== null) {
            return $this->_rulesChecker;
        }
        /** @var class-string<\Cake\Datasource\RulesChecker> $class */
        $class = defined('static::RULES_CLASS') ? static::RULES_CLASS : RulesChecker::class;
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore-next-line
         */
        $this->_rulesChecker = $this->buildRules(new $class(['repository' => $this]));
        $this->dispatchEvent('Model.buildRules', ['rules' => $this->_rulesChecker]);

        return $this->_rulesChecker;
    }

    /**
     * Returns a RulesChecker object after modifying the one that was supplied.
     *
     * Subclasses should override this method in order to initialize the rules to be applied to
     * entities saved by this instance.
     *
     * @param \Cake\Datasource\RulesChecker $rules The rules object to be modified.
     * @return \Cake\Datasource\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }
}
