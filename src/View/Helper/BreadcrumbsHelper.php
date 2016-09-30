<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\View\Helper;
use LogicException;

/**
 * BreadcrumbsHelper to register and display a breadcrumb trail for your views
 */
class BreadcrumbsHelper extends Helper
{
    /**
     * The crumbs list.
     *
     * @var array
     */
    protected $crumbs = [];

    /**
     * Add a crumb to the trail.
     *
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function add($title, $link = null, array $options = [])
    {
        $this->crumbs[] = ['title' => $title, 'link' => $link, 'options' => $options];

        return $this;
    }

    /**
     * Prepend a crumb to the start of the queue.
     *
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function prepend($title, $link = null, array $options = [])
    {
        array_unshift($this->crumbs, ['title' => $title, 'link' => $link, 'options' => $options]);

        return $this;
    }

    /**
     * Insert a crumb at a specific index.
     *
     * If the index already exists, the new crumb will be inserted,
     * and the existing element will be shifted one index greater.
     *
     * @param int $index The index to insert at.
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertAt($index, $title, $link = null, array $options = [])
    {
        array_splice($this->crumbs, $index, 0, [['title' => $title, 'link' => $link, 'options' => $options]]);

        return $this;
    }

    /**
     * Insert a crumb before the first matching crumb with the specified title.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one before
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertBefore($matchingTitle, $title, $link = null, array $options = [])
    {
        $found = false;
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['title'] === $matchingTitle) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($key, $title, $link, $options);
        }
        throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
    }

    /**
     * Insert a crumb after the first matching crumb with the specified title.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one after
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertAfter($matchingTitle, $title, $link = null, array $options = [])
    {
        $found = false;
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['title'] === $matchingTitle) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($key + 1, $title, $link, $options);
        }
        throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
    }

    /**
     * Returns the crumbs list
     *
     * @return array
     */
    public function getCrumbs()
    {
       return $this->crumbs;
    }
}
