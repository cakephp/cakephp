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
 * @since         5.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;

/**
 * Normalizes string input before it is validated and marshalled into entities.
 *
 * - Trims leading and trailing whitespace from string columns.
 * - Converts blank strings to `null` for nullable string columns. Non-nullable
 *   columns keep the empty string.
 *
 * The normalization happens in `Model.beforeMarshal`, so validation rules like
 * `notBlank` see the normalized value.
 *
 * ```
 * $this->addBehavior('Normalize');
 *
 * // Only trim, keep blank strings and never touch the password column
 * $this->addBehavior('Normalize', [
 *     'emptyToNull' => false,
 *     'exclude' => ['password'],
 * ]);
 * ```
 *
 * The normalization can be disabled for a single call using the `normalize`
 * marshaller option: `$table->newEntity($data, ['normalize' => false])`.
 */
class NormalizeBehavior extends Behavior
{
    /**
     * Default config
     *
     * - `trim` - Whether to trim string values. Defaults to `true`.
     * - `trimCharacters` - The characters to trim, see `trim()`. Defaults to PHP's default.
     * - `emptyToNull` - Whether to convert blank strings to `null` for nullable
     *   columns. Defaults to `true`.
     * - `types` - The column types to normalize.
     * - `fields` - A list of fields to normalize, regardless of their type. `null`
     *   (the default) normalizes all columns of the configured `types`. Blank strings
     *   are only converted to `null` for nullable columns.
     * - `exclude` - A list of fields to never normalize, e.g. passwords.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'implementedFinders' => [],
        'implementedMethods' => [],
        'trim' => true,
        'trimCharacters' => " \n\r\t\v\0",
        'emptyToNull' => true,
        'types' => ['string', 'char', 'text', 'citext'],
        'fields' => null,
        'exclude' => [],
    ];

    /**
     * Normalize the request data.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The beforeMarshal event.
     * @param \ArrayObject<string, mixed> $data The data to marshal.
     * @param \ArrayObject<string, mixed> $options The marshaller options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (($options['normalize'] ?? true) === false) {
            return;
        }

        foreach ($this->normalizedFields() as $field => $nullable) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                continue;
            }

            $value = $data[$field];
            if ($this->_config['trim']) {
                $value = trim($value, $this->_config['trimCharacters']);
            }
            if ($value === '' && $nullable && $this->_config['emptyToNull']) {
                $value = null;
            }
            $data[$field] = $value;
        }
    }

    /**
     * Get the fields to normalize, mapped to whether the column is nullable.
     *
     * @return array<string, bool>
     */
    protected function normalizedFields(): array
    {
        $schema = $this->_table->getSchema();
        $fields = $this->_config['fields'];
        $exclude = (array)$this->_config['exclude'];

        if ($fields === null) {
            $types = (array)$this->_config['types'];
            $fields = array_filter(
                $schema->columns(),
                fn(string $column): bool => in_array($schema->getColumnType($column), $types, true),
            );
        }

        $result = [];
        foreach ((array)$fields as $field) {
            if (in_array($field, $exclude, true)) {
                continue;
            }
            $result[$field] = $schema->hasColumn($field) && $schema->isNullable($field);
        }

        return $result;
    }
}
