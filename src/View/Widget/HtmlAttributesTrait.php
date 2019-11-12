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

use Cake\View\Form\ContextInterface;

trait HtmlAttributesTrait
{
    protected function setRequired(array $data, ContextInterface $context, string $fieldName): array
    {
        if (
            !isset($data['required'])
            && empty($data['disabled'])
            && $context->isRequired($fieldName)
        ) {
            $data['required'] = true;
        }

        return $data;
    }

    protected function setMaxLength(array $data, ContextInterface $context, string $fieldName): array
    {
        $maxLength = $context->getMaxLength($fieldName);
        if ($maxLength !== null) {
            $data['maxlength'] = min($maxLength, 100000);
        }

        return $data;
    }
}
