<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BakeUser Entity.
 */
class BakeUser extends Entity {

/**
 * Fields that are excluded from JSON an array versions of the entity.
 *
 * @var array
 */
	protected $_hidden = [
		'password'
	];

}
