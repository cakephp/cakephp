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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type\Attribute;

use Attribute;

/**
 * Label attribute for enum cases.
 *
 * This attribute can be used to specify a custom label for an enum case.
 * If the `EnumLabelTrait` is used, the `label()` method will return the label
 * defined in this attribute instead of a humanized version of the case name.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class Label
{
    /**
     * Constructor.
     *
     * @param string $label The label to use for the enum case.
     */
    public function __construct(
        public string $label,
    ) {
    }
}
