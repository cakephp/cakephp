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
namespace Cake\Controller\Attribute;

use Attribute;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\ClassDeclaration;

/**
 * Declares a controller component or a lazy dependency of another component.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class UseComponent extends ClassDeclaration
{
    /**
     * Stores the component class, optional alias, and construction options.
     *
     * @param class-string<\Cake\Controller\Component> $className Target implementation class.
     * @param string|null $alias Explicit registry alias.
     * @param array<string, mixed> $config Construction options.
     */
    public function __construct(string $className, ?string $alias = null, array $config = [])
    {
        parent::__construct($className, $alias, $config);
    }

    /**
     * Returns the owner types that can consume this declaration.
     *
     * @return list<class-string<\Cake\Controller\Controller|\Cake\Controller\Component>>
     */
    public function getOwnerClasses(): array
    {
        return [Controller::class, Component::class];
    }

    /**
     * Returns the registry domain used to detect duplicate aliases.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return 'component';
    }

    /**
     * Returns the conventional suffix removed when inferring an alias.
     *
     * @return string
     */
    protected function getSuffix(): string
    {
        return 'Component';
    }
}
