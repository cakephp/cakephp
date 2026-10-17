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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

/**
 * Interface for rule providers that can resolve a validation rule's method
 * to an alternate object.
 *
 * Rule providers implementing this interface allow validators to invoke a
 * rule's method on another object, e.g. ORM tables resolving rule methods
 * provided by behaviors. Resolving the provider object before the rule's
 * callable is created ensures the method's signature can be correctly
 * reflected for passing the validation context.
 */
interface RuleProviderResolverInterface
{
    /**
     * Get the object that should be used to invoke the given rule method.
     *
     * @param string $method The name of the rule method.
     * @return object|null Object providing the method, or null to invoke the
     *   method on the provider itself.
     */
    public function resolveRuleProvider(string $method): ?object;
}
