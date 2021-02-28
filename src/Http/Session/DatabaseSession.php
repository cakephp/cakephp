<?php
declare(strict_types=1);

/**
 * Database Session save handler. Allows saving session information into a model.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Session;

use Cake\ORM\Locator\LocatorAwareTrait;
use SessionHandlerInterface;

/**
 * DatabaseSession provides methods to be used with Session.
 */
class DatabaseSession implements SessionHandlerInterface
{
    use LocatorAwareTrait;

    /**
     * Reference to the table handling the session data
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Number of seconds to mark the session as expired
     *
     * @var int
     */
    protected $_timeout;

    /**
     * Constructor. Looks at Session configuration information and
     * sets up the session model.
     *
     * @param array $config The configuration for this engine. It requires the 'model'
     * key to be present corresponding to the Table to use for managing the sessions.
     */
    public function __construct(array $config = [])
    {
        if (isset($config['tableLocator'])) {
            $this->setTableLocator($config['tableLocator']);
        }
        $tableLocator = $this->getTableLocator();

        if (empty($config['model'])) {
            $config = $tableLocator->exists('Sessions') ? [] : ['table' => 'sessions'];
            $this->_table = $tableLocator->get('Sessions', $config);
        } else {
            $this->_table = $tableLocator->get($config['model']);
        }

        $this->_timeout = (int)ini_get('session.gc_maxlifetime');
    }

    /**
     * Set the timeout value for sessions.
     *
     * Primarily used in testing.
     *
     * @param int $timeout The timeout duration.
     * @return $this
     */
    public function setTimeout(int $timeout)
    {
        $this->_timeout = $timeout;

        return $this;
    }

    /**
     * Method called on open of a database session.
     *
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool Success
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * Method called on close of a database session.
     *
     * @return bool Success
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Method used to read from a database session.
     *
     * @param string $id ID that uniquely identifies session in database.
     * @return string Session data or empty string if it does not exist.
     */
    public function read($id): string
    {
        /** @var string $pkField */
        $pkField = $this->_table->getPrimaryKey();
        $result = $this->_table
            ->find('all')
            ->select(['data'])
            ->where([$pkField => $id])
            ->disableHydration()
            ->first();

        if (empty($result)) {
            return '';
        }

        if (is_string($result['data'])) {
            return $result['data'];
        }

        $session = stream_get_contents($result['data']);

        if ($session === false) {
            return '';
        }

        return $session;
    }

    /**
     * Helper function called on write for database sessions.
     *
     * @param string $id ID that uniquely identifies session in database.
     * @param string $data The data to be saved.
     * @return bool True for successful write, false otherwise.
     */
    public function write($id, $data): bool
    {
        if (!$id) {
            return false;
        }

        /** @var string $pkField */
        $pkField = $this->_table->getPrimaryKey();
        $session = $this->_table->newEntity([
            $pkField => $id,
            'data' => $data,
            'expires' => time() + $this->_timeout,
        ], ['accessibleFields' => [$pkField => true]]);

        return (bool)$this->_table->save($session);
    }

    /**
     * Method called on the destruction of a database session.
     *
     * @param string $id ID that uniquely identifies session in database.
     * @return bool True for successful delete, false otherwise.
     */
    public function destroy($id): bool
    {
        /** @var string $pkField */
        $pkField = $this->_table->getPrimaryKey();
        $this->_table->deleteAll([$pkField => $id]);

        return true;
    }

    /**
     * Helper function called on gc for database sessions.
     *
     * @param int $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
     * @return bool True on success, false on failure.
     */
    public function gc($maxlifetime): bool
    {
        $this->_table->deleteAll(['expires <' => time()]);

        return true;
    }
}
