<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * BakeArticles Controller
 *
 * @property \App\Model\Table\BakeArticlesTable $BakeArticles
 * @property \Cake\Controller\Component\CsrfComponent $Csrf
 * @property \Cake\Controller\Component\AuthComponent $Auth
 */
class BakeArticlesController extends AppController {

/**
 * Helpers
 *
 * @var array
 */
	public $helpers = ['Html', 'Time'];

/**
 * Components
 *
 * @var array
 */
	public $components = ['Csrf', 'Auth'];

}
