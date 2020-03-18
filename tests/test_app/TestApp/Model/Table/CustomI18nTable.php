<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2013, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * Custom I18n table class
 */
class CustomI18nTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('custom_i18n_table');
    }

    public static function defaultConnectionName(): string
    {
        return 'custom_i18n_datasource';
    }
}
