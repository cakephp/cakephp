<?php
App::uses('AppController', 'Controller');
/**
 * Articles Controller
 *
 * @property Article $Article
 * @property AclComponent $Acl
 * @property AuthComponent $Auth
 * @property PaginatorComponent $Paginator
 */
class ArticlesController extends AppController {

/**
 * Helpers
 *
 * @var array
 */
	public $helpers = array('Js', 'Time');

/**
 * Components
 *
 * @var array
 */
	public $components = array('Acl', 'Auth', 'Paginator');

}
