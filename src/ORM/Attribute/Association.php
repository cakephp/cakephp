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
namespace Cake\ORM\Attribute;

use Cake\Core\ClassDeclaration;
use Cake\ORM\Table;

/**
 * Describes an association to be registered through the existing table API.
 *
 * @internal
 */
abstract readonly class Association extends ClassDeclaration
{
    /**
     * Returns the table owner type required for association declarations.
     *
     * @return list<class-string<\Cake\ORM\Table>>
     */
    public function getOwnerClasses(): array
    {
        return [Table::class];
    }

    /**
     * Returns the common alias domain for all association kinds.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return 'association';
    }

    /**
     * Returns the conventional table suffix used for alias inference.
     *
     * @return string
     */
    protected function getSuffix(): string
    {
        return 'Table';
    }

    /**
     * Returns the table method that constructs this association kind.
     *
     * @return string
     */
    abstract public function getMethod(): string;
}
