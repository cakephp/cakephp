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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View\Form;

use Cake\View\Form\ContextInterface;

class StubContext implements ContextInterface
{
    public function getPrimaryKey(): array
    {
        return [];
    }

    public function isPrimaryKey(string $field): bool
    {
        return false;
    }

    public function isCreate(): bool
    {
        return false;
    }

    public function val(string $field, array $options = []): mixed
    {
        return null;
    }

    public function isRequired(string $field): ?bool
    {
        return null;
    }

    public function getRequiredMessage(string $field): ?string
    {
        return null;
    }

    public function getMaxLength(string $field): ?int
    {
        return null;
    }

    public function fieldNames(): array
    {
        return [];
    }

    public function type(string $field): ?string
    {
        return null;
    }

    public function attributes(string $field): array
    {
        return [];
    }

    public function hasError(string $field): bool
    {
        return false;
    }

    public function error(string $field): array
    {
        return [];
    }
}
