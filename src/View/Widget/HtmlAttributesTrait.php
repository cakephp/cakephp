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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Database\Schema\TableSchema;
use Cake\View\Form\ContextInterface;

/**
 * Trait with helper methods to set default HTML attributes for widgets.
 *
 * @internal
 */
trait HtmlAttributesTrait
{
    /**
     * Set value for "required" attribute if applicable.
     *
     * @param array $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array Updated data array.
     */
    protected function setRequired(array $data, ContextInterface $context, string $fieldName): array
    {
        if (
            empty($data['disabled'])
            && (
                (isset($data['type'])
                    && $data['type'] !== 'hidden'
                )
                || !isset($data['type'])
            )
            && $context->isRequired($fieldName)
        ) {
            $data['required'] = true;
        }

        return $data;
    }

    /**
     * Set value for "maxlength" attribute if applicable.
     *
     * @param array $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array Updated data array.
     */
    protected function setMaxLength(array $data, ContextInterface $context, string $fieldName): array
    {
        $maxLength = $context->getMaxLength($fieldName);
        if ($maxLength !== null) {
            $data['maxlength'] = min($maxLength, 100000);
        }

        return $data;
    }

    /**
     * Set value for "step" attribute if applicable.
     *
     * @param array $data Data array
     * @param \Cake\View\Form\ContextInterface $context Context instance.
     * @param string $fieldName Field name.
     * @return array Updated data array.
     */
    protected function setStep(array $data, ContextInterface $context, string $fieldName): array
    {
        $dbType = $context->type($fieldName);
        $fieldDef = $context->attributes($fieldName);

        $fractionalTypes = [
            TableSchema::TYPE_DATETIME_FRACTIONAL,
            TableSchema::TYPE_TIMESTAMP_FRACTIONAL,
        ];

        if ($data['type'] === 'number') {
            if ($dbType === 'decimal' && isset($fieldDef['precision'])) {
                $decimalPlaces = $fieldDef['precision'];
                $data['step'] = sprintf('%.' . $decimalPlaces . 'F', pow(10, -1 * $decimalPlaces));
            } elseif ($dbType === 'float') {
                $data['step'] = 'any';
            }
        } elseif (in_array($dbType, $fractionalTypes, true)) {
            $data['step'] = '0.001';
        }

        return $data;
    }
}
