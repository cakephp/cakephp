<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.config.inflections
 * @since        CakePHP v .0.10.x.x
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

$pluralUninflectedHerd = array(
# DON'T INFLECT IN CLASSICAL MODE, OTHERWISE NORMAL INFLECTION
'wildebeest', 'swine', 'eland', 'bison', 'buffalo','elk', 'moose', 'rhinoceros',);

$pluralUninflecteds =array(
# PAIRS OR GROUPS SUBSUMED TO A SINGULAR...
'breeches', 'britches', 'clippers', 'gallows', 'hijinks',
'headquarters', 'pliers', 'scissors', 'testes', 'herpes',
'pincers', 'shears', 'proceedings', 'trousers',
# UNASSIMILATED LATIN 4th DECLENSION
'cantus', 'coitus', 'nexus',
# RECENT IMPORTS...
'contretemps', 'corps', 'debris',
'.*ois', 'siemens',
# DISEASES
'.*measles', 'mumps',
# MISCELLANEOUS OTHERS...
'diabetes', 'jackanapes', 'series', 'species', 'rabies',
'chassis', 'innings', 'news', 'mews',);
        
$pluralUninflected = array(
# SOME FISH AND HERD ANIMALS
'.*fish', 'tuna', 'salmon', 'mackerel', 'trout',
'bream', 'sea[- ]bass', 'carp', 'cod', 'flounder', 'whiting', 
'.*deer', '.*sheep', 
# ALL NATIONALS ENDING IN -ese
'Portuguese', 'Amoyese', 'Borghese', 'Congoese', 'Faroese',
'Foochowese', 'Genevese', 'Genoese', 'Gilbertese', 'Hottentotese',
'Kiplingese', 'Kongoese', 'Lucchese', 'Maltese', 'Nankingese',
'Niasese', 'Pekingese', 'Piedmontese', 'Pistoiese', 'Sarawakese',
'Shavese', 'Vermontese', 'Wenchowese', 'Yengeese',
'.*[nrlm]ese',
# DISEASES
'.*pox',
# OTHER ODDITIES
'graffiti', 'djinn');
	       
$pluralIrregulars = array(
'corpus' => 'corpuses|corpora',
'opus'   => 'opuses|opera',
'genus'  => 'genera',
'mythos' => 'mythoi',
'penis'  => 'penises|penes',
'testis' => 'testes',
'atlas'  => 'atlases|atlantes',);
              
$pluralIrregular =array(
'child'          => 'children',
'brother'        => 'brothers|brethren',
'loaf'           => 'loaves',
'hoof'           => 'hoofs|hooves',
'beef'           => 'beefs|beeves',
'money'          => 'monies',
'mongoose'       => 'mongooses|',
'ox'             => 'oxen',
'cow'            => 'cows|kine',
'soliloquy'      => 'soliloquies|',
'graffito'       => 'graffiti',
'prima donna'    => 'prima donnas|prime donne',
'octopus'        => 'octopuses|octopodes',
'genie'          => 'genies|genii',
'ganglion'       => 'ganglions|ganglia',
'trilby'         => 'trilbys',
'turf'           => 'turfs|turves',
'numen'          => 'numina',
'occiput'        => 'occiputs|occipita',);

?>        	  