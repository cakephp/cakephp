<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Cache
  * Description:
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Enter description here...
  *
  */
uses('model');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Cache extends Model {

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $id = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $data = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $for_caching = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $use_table = 'cache';

/**
  * Enter description here...
  *
  * @param unknown_type $id
  */
	function __construct ($id) {
		$this->id = (md5($id));
		parent::__construct($this->id);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $id
  * @return unknown
  */
	function id ($id=null) {
		if (!$id) return $this->id;
		return ($this->id = $id);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $content
  * @param unknown_type $keep_for
  * @return unknown
  */
	function remember ($content, $keep_for=CACHE_PAGES_FOR) {
		$data = addslashes($this->for_caching.$content);
		$expire = date("Y-m-d H:i:s",time()+($keep_for>0? $keep_for: 999999999));
		return $this->query("REPLACE {$this->use_table} (id,data,expire) VALUES ('{$this->id}', '{$data}', '{$expire}')");
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function restore () {
		if (empty($this->data['data']))
			return $this->find("id='{$this->id}' AND expire>NOW()");
		
		return $this->data['data'];
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function has () {
		return is_array($this->data = $this->find("id='{$this->id}' AND expire>NOW()"));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $string
  */
	function append ($string) {
		$this->for_caching .= $string;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function clear () {
		return $this->query("DELETE FROM {$this->use_table}");
	}
}

?>