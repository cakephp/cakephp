<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Exception;

use Cake\Core\Exception\CakeException;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use Throwable;

/**
 * Used when a strict save or delete fails
 */
class PersistenceFailedException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected string $_messageTemplate = 'Entity %s failure.';

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\EntityInterface $_entity The entity on which the persistence operation failed
     * @param array<string>|string $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
     * @param int|null $code The code of the error, is also the HTTP status code for the error.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(
        /**
         * The entity on which the persistence operation failed
         */
        protected EntityInterface $_entity,
        array|string $message,
        ?int $code = null,
        ?Throwable $previous = null
    ) {
        if (is_array($message)) {
            $errors = [];
            foreach (Hash::flatten($this->_entity->getErrors()) as $field => $error) {
                $errors[] = $field . ': "' . $error . '"';
            }

            if ($errors) {
                $message[] = implode(', ', $errors);
                $this->_messageTemplate = 'Entity %s failure. Found the following errors (%s).';
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the passed in entity
     */
    public function getEntity(): EntityInterface
    {
        return $this->_entity;
    }
}
