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
use function Cake\I18n\__;

/**
 * Trait EnumLabelTrait
 *
 * Provides a method to get the label for an enum case.
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
        /** @var array<string,string|array{label:string,context:string}> $labels */
        static $labels = [];

        if (isset($labels[$this->name])) {
            return $this->translatedLabel($labels[$this->name]);
        }

        $reflection = new ReflectionClassConstant(static::class, $this->name);
        $enumAttributes = $reflection->getAttributes(Label::class);

        if ($enumAttributes === []) {
            $labels[$this->name] = Inflector::humanize(Inflector::underscore($this->name));
        } else {
            $instance = $enumAttributes[0]->newInstance();
            $labels[$this->name] = [
                'label' => $instance->label,
                'context' => $instance->context,
            ];
        }

        return $this->translatedLabel($labels[$this->name]);
    }

    /**
     * @param string|array{label:string,context:string} $label
     *
     */
    private function translatedLabel(string|array $label): string
    {
        if (is_string($label)) {
            return $label;
        }

        /** @var bool $i18n */
        static $i18n;

        $i18n ??= function_exists('\Cake\I18n\__');

        if (!$i18n) {
            return $label['label'];
        }

        $context = $label['context'] ?? '';

        if ($context !== '') {
            return __x($context, $label['label']);
        }

        return __($label['label']);
    }
}
