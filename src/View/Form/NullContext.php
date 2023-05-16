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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use function Cake\Core\deprecationWarning;

/**
 * Provides a context provider that does nothing.
 *
 * This context provider simply fulfils the interface requirements
 * that FormHelper has.
 */
class NullContext implements ContextInterface
{
    /**
     * Constructor.
     *
     * @param array $context Context info.
     */
    public function __construct(array $context)
    {
    }

    /**
     * Get the fields used in the context as a primary key.
     *
     * @return array<string>
     * @deprecated 4.0.0 Renamed to {@link getPrimaryKey()}.
     */
    public function primaryKey(): array
    {
        deprecationWarning('`NullContext::primaryKey()` is deprecated. Use `NullContext::getPrimaryKey()`.');

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKey(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isPrimaryKey(string $field): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isCreate(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function val(string $field, array $options = [])
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isRequired(string $field): ?bool
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredMessage(string $field): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMaxLength(string $field): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function fieldNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function type(string $field): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function attributes(string $field): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function hasError(string $field): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function error(string $field): array
    {
        return [];
    }
}
