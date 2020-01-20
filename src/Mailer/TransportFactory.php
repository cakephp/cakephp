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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use Cake\Core\StaticConfigTrait;
use InvalidArgumentException;

/**
 * Factory class for generating email transport instances.
 */
class TransportFactory
{
    use StaticConfigTrait;

    /**
     * Transport Registry used for creating and using transport instances.
     *
     * @var \Cake\Mailer\TransportRegistry|null
     */
    protected static $_registry;

    /**
     * An array mapping url schemes to fully qualified Transport class names
     *
     * @var string[]
     * @psalm-var array<string, class-string>
     */
    protected static $_dsnClassMap = [
        'debug' => Transport\DebugTransport::class,
        'mail' => Transport\MailTransport::class,
        'smtp' => Transport\SmtpTransport::class,
    ];

    /**
     * Returns the Transport Registry used for creating and using transport instances.
     *
     * @return \Cake\Mailer\TransportRegistry
     */
    public static function getRegistry(): TransportRegistry
    {
        if (static::$_registry === null) {
            static::$_registry = new TransportRegistry();
        }

        return static::$_registry;
    }

    /**
     * Sets the Transport Registry instance used for creating and using transport instances.
     *
     * Also allows for injecting of a new registry instance.
     *
     * @param \Cake\Mailer\TransportRegistry $registry Injectable registry object.
     * @return void
     */
    public static function setRegistry(TransportRegistry $registry): void
    {
        static::$_registry = $registry;
    }

    /**
     * Finds and builds the instance of the required tranport class.
     *
     * @param string $name Name of the config array that needs a tranport instance built
     * @return void
     * @throws \InvalidArgumentException When a tranport cannot be created.
     */
    protected static function _buildTransport(string $name): void
    {
        if (!isset(static::$_config[$name])) {
            throw new InvalidArgumentException(
                sprintf('The "%s" transport configuration does not exist', $name)
            );
        }

        if (is_array(static::$_config[$name]) && empty(static::$_config[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('Transport config "%s" is invalid, the required `className` option is missing', $name)
            );
        }

        static::getRegistry()->load($name, static::$_config[$name]);
    }

    /**
     * Get transport instance.
     *
     * @param string $name Config name.
     * @return \Cake\Mailer\AbstractTransport
     */
    public static function get(string $name): AbstractTransport
    {
        $registry = static::getRegistry();

        if (isset($registry->{$name})) {
            return $registry->{$name};
        }

        static::_buildTransport($name);

        return $registry->{$name};
    }
}
