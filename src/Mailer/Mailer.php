<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use ArrayAccess;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventListenerInterface;
use Cake\Mailer\Exception\MissingActionException;
use Cake\Utility\Inflector;

/**
 * Mailer base class.
 *
 * Mailer classes let you encapsulate related Email logic into a reusable
 * and testable class.
 *
 * ## Defining Messages
 *
 * Mailers make it easy for you to define methods that handle email formatting
 * logic. For example:
 *
 * ```
 * class UserMailer extends Mailer
 * {
 *   public function resetPassword($user)
 *   {
 *     $this->subject = 'Reset Password';
 *     $this->to = $user->email;
 *     $this->set(['token' => $user->token]);
 *   }
 * }
 * ```
 *
 * Is a trivial example but shows how a mailer could be declared.
 *
 * ## Sending Messages
 *
 * After you have defined some messages you will want to send them:
 *
 * ```
 * $mailer = new UserMailer();
 * $mailer->send('resetPassword', $user);
 * ```
 *
 * ## Event Listener
 *
 * Mailers can also subscribe to application event allowing you to
 * decouple email delivery from your application code. By re-declaring the
 * `implementedEvents()` method you can define event handlers that can
 * convert events into email. For example, if your application had a user
 * registation event:
 *
 * ```
 * public function implementedEvents()
 * {
 *   return [
 *     'Model.afterSave' => 'onRegistration',
 *   ];
 * }
 *
 * public function onRegistration(Event $event, Entity $entity, ArrayObject $options)
 * {
 *     if ($entity->isNew()) {
 *          $this->send('welcome', [$entity]);
 *      }
 *  }
 * ```
 *
 * The onRegistration method converts the application event into a mailer method.
 * Our mailer could either be registered in the application bootstrap, or
 * in the Table class' initialize() hook.
 */
abstract class Mailer implements ArrayAccess, EventListenerInterface
{
    use ModelAwareTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    static public $name;

    /**
     * Layout.
     *
     * @var string
     */
    public $layout;

    /**
     * Email instance.
     *
     * @var \Cake\Mailer\Email
     */
    protected $_email;

    /**
     * Constructor.
     *
     * @param \Cake\Mailer\Email|null $email Email instance.
     */
    public function __construct(Email $email = null)
    {
        if ($email === null) {
            $email = new Email();
        }

        if ($this->layout === null) {
            $this->layout = Inflector::underscore($this->getName());
        }

        $this->_email = $email;
    }

    /**
     * Returns the mailer's name.
     *
     * @return string
     */
    public function getName()
    {
        if (!static::$name) {
            static::$name = str_replace(
                'Mailer',
                '',
                join('', array_slice(explode('\\', get_class($this)), -1))
            );
        }
        return static::$name;
    }

    /**
     * Sets layout to use. Defaults to configured layout template if a custom layout
     * could not be found.
     *
     * @param string $layout Name of the layout to use.
     * @return $this object.
     */
    public function layout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Sets headers.
     *
     * @param array $headers Headers to set.
     * @return $this object.
     */
    public function setHeaders(array $headers)
    {
        $this->_email->setHeaders($headers);
        return $this;
    }

    /**
     * Adds headers.
     *
     * @param array $headers Headers to set.
     * @return $this object.
     */
    public function addHeaders(array $headers)
    {
        $this->_email->addHeaders($headers);
        return $this;
    }

    /**
     * Sets attachments.
     *
     * @param string|array $attachments String with the filename or array with filenames
     * @return $this object.
     * @throws \InvalidArgumentException
     */
    public function attachments($attachments)
    {
        $this->_email->attachments($attachments);
        return $this;
    }

    /**
     * Sets email view vars.
     *
     * @param string|array $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this object.
     */
    public function set($key, $value = null)
    {
        $this->_email->viewVars(is_string($key) ? [$key => $value] : $key);
        return $this;
    }

    /**
     * Sends email.
     *
     * @param string $action The name of the mailer action to trigger.
     * @param array $args Arguments to pass to the triggered mailer action.
     * @param array $headers Headers to set.
     * @return array
     * @throws \Cake\Mailer\Exception\MissingActionException
     * @throws \BadMethodCallException
     */
    public function send($action, $args = [], $headers = [])
    {
        if (!method_exists($this, $action)) {
            throw new MissingActionException([
                'mailer' => $this->getName() . 'Mailer',
                'action' => $action,
            ]);
        }

        $this->setHeaders($headers);

        call_user_func_array([$this, $action], $args);

        $result = $this->_email
            ->profile((array)$this)
            ->send();

        $this->reset();
        return $result;
    }

    /**
     * Resets email instance to original config.
     *
     * @return $this object.
     */
    public function reset()
    {
        $this->_email->reset();
        return $this;
    }

    /**
     * Checks if the property exists.
     *
     * @param string $offset Property name.
     * @return bool True if it exists.
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset) ||
            method_exists($this->_email, $offset);
    }

    /**
     * Gets the property value if it exists.
     *
     * @param string $offset Property name.
     * @return mixed Value.
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        if (isset($this->{$offset})) {
            return $this->{$offset};
        }

        return call_user_func([$this->_email, $offset]);
    }

    /**
     * Sets property's value.
     *
     * @param string $offset Property name.
     * @param mixed $value Value.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * Unset a property.
     *
     * @param string $offset Property name.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }

    /**
     * Implemented events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
