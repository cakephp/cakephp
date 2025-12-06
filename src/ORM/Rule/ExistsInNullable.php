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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\ORM\Association;
use Cake\ORM\Table;

/**
 * ExistsIn rule with allowNullableNulls enabled by default.
 *
 * This rule accepts composite foreign keys where one or more nullable columns are null.
 */
class ExistsInNullable extends ExistsIn
{
    /**
     * Constructor.
     *
     * @param array<string>|string $fields The field or fields to check existence as primary key.
     * @param \Cake\ORM\Table|\Cake\ORM\Association|string $repository The repository where the
     * field will be looked for, or the association name for the repository.
     * @param array<string, mixed> $options The options that modify the rule's behavior.
     */
    public function __construct(array|string $fields, Table|Association|string $repository, array $options = [])
    {
        $options += ['allowNullableNulls' => true];
        parent::__construct($fields, $repository, $options);
    }
}
