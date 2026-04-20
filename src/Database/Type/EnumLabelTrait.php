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
namespace Cake\Database\Type;

use Cake\Database\Type\Attribute\Label;
use Cake\Utility\Inflector;
use ReflectionClassConstant;

/**
 * Trait EnumLabelTrait
 *
 * Provides a method to get a display label for cases of backed enums
 * base on the case name or a `Label` attribute if it is defined.
 */
trait EnumLabelTrait
{
    /**
     * Returns the label for the enum.
     *
     * If the enum case has a `Label` attribute, it will return the label defined in the attribute.
     * Otherwise, it will return a humanized version of the enum case name.
     *
     * @return string
     */
    public function label(): string
    {
        /** @var array<string, string> $labels */
        static $labels = [];

        if (isset($labels[$this->value])) {
            return $labels[$this->value];
        }

        $reflection = new ReflectionClassConstant(static::class, $this->name);
        $enumAttributes = $reflection->getAttributes(Label::class);

        if ($enumAttributes === []) {
            return $labels[$this->value] = Inflector::humanize(Inflector::underscore($this->name));
        }

        return $labels[$this->value] = $enumAttributes[0]->newInstance()->label;
    }
}
