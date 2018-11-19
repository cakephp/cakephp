<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\View;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * TemplateFileEquals
 *
 * @internal
 */
class TemplateFileEquals extends Constraint
{

    /**
     * @var string
     */
    protected $filename;

    /**
     * Constructor
     *
     * @param string $filename Template file name
     */
    public function __construct($filename)
    {
        parent::__construct();

        $this->filename = $filename;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected filename
     * @return bool
     */
    public function matches($other)
    {
        return strpos($this->filename, $other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('equals template file %s', $this->filename);
    }
}
