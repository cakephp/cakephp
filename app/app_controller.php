<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: AppController
  * Enter description here...
  * 
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.app
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.app
  * @since Cake v 0.2.9
  *
  */
class AppController extends Controller {

/**
  * Enter description here...
  *
  * @param unknown_type $tags
  * @param unknown_type $active
  * @return unknown
  */
	function tags_as_links ($tags, $active=array()) {
		$tags = is_array($tags)? $tags: Tag::split_tags($tags);

		$links = array();
		foreach ($tags as $tag) {

			if (in_array($tag, $active))
				$url_tags = $this->array_except($active, $tag);
			else 
				$url_tags = array_merge($active, array($tag));

			$url = '/memes/with_tags/'.$this->tags_to_url($url_tags);

			$links[] = $this->link_to($tag, $url, in_array($tag, $active)? array('class'=>'active_tag'): null);
		}

		return join(' ', $links);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $array
  * @param unknown_type $except
  * @return unknown
  */
	function array_except ($array, $except) {
		if (!is_array($except)) $except = array($except);
		$out = array();

		foreach ($array as $k=>$v) {
			if (!in_array($v, $except))
				$out[$k] = $v;
		}

		return $out;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tags
  * @return unknown
  */
	function tags_to_url ($tags) {
		$out = array();
		foreach ($tags as $tag)
			$out[] = urlencode($tag);

		return join('+', $out);
	}

}

?>