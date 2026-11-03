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
namespace Cake\View\Attribute;

use Attribute;
use Cake\Core\ClassDeclaration;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Declares a view helper or a lazy dependency of another helper.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class UseHelper extends ClassDeclaration
{
    /**
     * Stores the helper class, optional alias, and construction options.
     *
     * @param class-string<\Cake\View\Helper<\Cake\View\View>> $className Target implementation class.
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
     * @return list<class-string<\Cake\View\View|\Cake\View\Helper<\Cake\View\View>>>
     */
    public function getOwnerClasses(): array
    {
        return [View::class, Helper::class];
    }

    /**
     * Returns the registry domain used to detect duplicate aliases.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return 'helper';
    }

    /**
     * Returns the conventional suffix removed when inferring an alias.
     *
     * @return string
     */
    protected function getSuffix(): string
    {
        return 'Helper';
    }
}
