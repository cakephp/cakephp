<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * BakeArticles Controller
 *
 * @property \App\Model\Table\BakeArticlesTable $BakeArticles
 * @property \Cake\Controller\Component\CsrfComponent $Csrf
 * @property \Cake\Controller\Component\AuthComponent $Auth
 * @property \Company\TestPluginThree\Controller\Component\SomethingComponent $Something
 * @property \TestPlugin\Controller\Component\OtherComponent $Other
 * @property \App\Controller\Component\AppleComponent $Apple
 * @property \App\Controller\Component\NonExistentComponent $NonExistent
 */
class BakeArticlesController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = ['Csrf', 'Auth', 'Company/TestPluginThree.Something', 'TestPlugin.Other', 'Apple', 'NonExistent'];

}
