<?php
/**
 * CakeRequest
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Hash', 'Utility');

/**
 * A class that helps wrap Request information and particulars about a single request.
 * Provides methods commonly used to introspect on the request headers and request body.
 *
 * Has both an Array and Object interface. You can access framework parameters using indexes:
 *
 * `$request['controller']` or `$request->controller`.
 *
 * @package       Cake.Network
 */
class CakeRequest implements ArrayAccess {

/**
 * Array of parameters parsed from the url.
 *
 * @var array
 */
	public $params = array(
		'plugin' => null,
		'controller' => null,
		'action' => null,
		'named' => array(),
		'pass' => array(),
	);

/**
 * Array of POST data. Will contain form data as well as uploaded files.
 * Inputs prefixed with 'data' will have the data prefix removed. If there is
 * overlap between an input prefixed with data and one without, the 'data' prefixed
 * value will take precedence.
 *
 * @var array
 */
	public $data = array();

/**
 * Array of querystring arguments
 *
 * @var array
 */
	public $query = array();

/**
 * The url string used for the request.
 *
 * @var string
 */
	public $url;

/**
 * Base url path.
 *
 * @var string
 */
	public $base = false;

/**
 * webroot path segment for the request.
 *
 * @var string
 */
	public $webroot = '/';

/**
 * The full address to the current request
 *
 * @var string
 */
	public $here = null;

/**
 * The built in detectors used with `is()` can be modified with `addDetector()`.
 *
 * There are several ways to specify a detector, see CakeRequest::addDetector() for the
 * various formats and ways to define detectors.
 *
 * @var array
 */
	protected $_detectors = array(
		'get' => array('env' => 'REQUEST_METHOD', 'value' => 'GET'),
		'post' => array('env' => 'REQUEST_METHOD', 'value' => 'POST'),
		'put' => array('env' => 'REQUEST_METHOD', 'value' => 'PUT'),
		'delete' => array('env' => 'REQUEST_METHOD', 'value' => 'DELETE'),
		'head' => array('env' => 'REQUEST_METHOD', 'value' => 'HEAD'),
		'options' => array('env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'),
		'ssl' => array('env' => 'HTTPS', 'value' => 1),
		'ajax' => array('env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'),
		'flash' => array('env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'),
		'mobile' => array('env' => 'HTTP_USER_AGENT', 'options' => array(
			'Android', 'AvantGo', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod', 'iPhone', 'iPad',
			'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini', 'Opera Mobi', 'PalmOS', 'PalmSource',
			'portalmmm', 'Plucker', 'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\\.Browser',
			'webOS', 'Windows CE', 'Windows Phone OS', 'Xiino'
		)),
		'requested' => array('param' => 'requested', 'value' => 1)
	);

/**
 * Copy of php://input. Since this stream can only be read once in most SAPI's
 * keep a copy of it so users don't need to know about that detail.
 *
 * @var string
 */
	protected $_input = '';

/**
 * Registered SLDs domain names
 *
 * @var array
 */
	protected $_slds = array(
		//.ac
		'.com.ac', '.net.ac', '.gov.ac', '.org.ac', '.mil.ac',
		//.ae
		'.co.ae', '.net.ae', '.gov.ae', '.ac.ae', '.sch.ae', '.org.ae', '.mil.ae', '.pro.ae', '.name.ae',
		//.af
		'.com.af', '.edu.af', '.gov.af', '.net.af', '.org.af',
		//.al
		'.com.al', '.edu.al', '.gov.al', '.mil.al', '.net.al', '.org.al',
		//.ao
		'.ed.ao', '.gv.ao', '.og.ao', '.co.ao', '.pb.ao', '.it.ao',
		//.ar
		'.com.ar', '.edu.ar', '.gob.ar', '.gov.ar', '.gov.ar', '.int.ar', '.mil.ar', '.net.ar', '.org.ar', '.tur.ar',
		//.at
		'.gv.at', '.ac.at', '.co.at', '.or.at',
		//.au
		'.com.au', '.net.au', '.org.au', '.edu.au', '.gov.au', '.csiro.au', '.asn.au', '.id.au',
		//.ba
		'.org.ba', '.net.ba', '.edu.ba', '.gov.ba', '.mil.ba', '.unsa.ba', '.untz.ba', '.unmo.ba', '.unbi.ba',
		'.unze.ba', '.co.ba', '.com.ba', '.rs.ba',
		//.bb
		'.co.bb', '.com.bb', '.net.bb', '.org.bb', '.gov.bb', '.edu.bb', '.info.bb', '.store.bb', '.tv.bb', '.biz.bb',
		//.bh
		'.com.bh', '.info.bh', '.cc.bh', '.edu.bh', '.biz.bh', '.net.bh', '.org.bh', '.gov.bh',
		//.bn
		'.com.bn', '.edu.bn', '.gov.bn', '.net.bn', '.org.bn',
		//.bo
		'.com.bo', '.net.bo', '.org.bo', '.tv.bo', '.mil.bo', '.int.bo', '.gob.bo', '.gov.bo', '.edu.bo',
		//.br
		'.adm.br', '.adv.br', '.agr.br', '.am.br', '.arq.br', '.art.br', '.ato.br', '.b.br', '.bio.br', '.blog.br',
		'.bmd.br', '.cim.br', '.cng.br', '.cnt.br', '.com.br', '.coop.br', '.ecn.br', '.edu.br', '.eng.br', '.esp.br',
		'.etc.br', '.eti.br', '.far.br', '.flog.br', '.fm.br', '.fnd.br', '.fot.br', '.fst.br', '.g12.br', '.ggf.br',
		'.gov.br', '.imb.br', '.ind.br', '.inf.br', '.jor.br', '.jus.br', '.lel.br', '.mat.br', '.med.br', '.mil.br',
		'.mus.br', '.net.br', '.nom.br', '.not.br', '.ntr.br', '.odo.br', '.org.br', '.ppg.br', '.pro.br', '.psc.br',
		'.psi.br', '.qsl.br', '.rec.br', '.slg.br', '.srv.br', '.tmp.br', '.trd.br', '.tur.br', '.tv.br', '.vet.br',
		'.vlog.br', '.wiki.br', '.zlg.br',
		//.bs
		'.com.bs', '.net.bs', '.org.bs', '.edu.bs', '.gov.bs',
		//.bz
		'com.bz', 'edu.bz', 'gov.bz', 'net.bz', 'org.bz',
		//.ca
		'.ab.ca', '.bc.ca', '.mb.ca', '.nb.ca', '.nf.ca', '.nl.ca', '.ns.ca', '.nt.ca', '.nu.ca', '.on.ca', '.pe.ca',
		'.qc.ca', '.sk.ca', '.yk.ca',
		//.ck
		'.co.ck', '.org.ck', '.edu.ck', '.gov.ck', '.net.ck', '.gen.ck', '.biz.ck', '.info.ck',
		//.cn
		'.ac.cn', '.com.cn', '.edu.cn', '.gov.cn', '.mil.cn', '.net.cn', '.org.cn', '.ah.cn', '.bj.cn', '.cq.cn',
		'.fj.cn', '.gd.cn', '.gs.cn', '.gz.cn', '.gx.cn', '.ha.cn', '.hb.cn', '.he.cn', '.hi.cn', '.hl.cn', '.hn.cn',
		'.jl.cn', '.js.cn', '.jx.cn', '.ln.cn', '.nm.cn', '.nx.cn', '.qh.cn', '.sc.cn', '.sd.cn', '.sh.cn', '.sn.cn',
		'.sx.cn', '.tj.cn', '.tw.cn', '.xj.cn', '.xz.cn', '.yn.cn', '.zj.cn',
		//.co
		'.com.co', '.org.co', '.edu.co', '.gov.co', '.net.co', '.mil.co', '.nom.co',
		//.cr
		'.ac.cr', '.co.cr', '.ed.cr', '.fi.cr', '.go.cr', '.or.cr', '.sa.cr', '.cr',
		//.cy
		'.ac.cy', '.net.cy', '.gov.cy', '.org.cy', '.pro.cy', '.name.cy', '.ekloges.cy', '.tm.cy', '.ltd.cy', '.biz.cy',
		'.press.cy', '.parliament.cy', '.com.cy',
		//.do
		'.edu.do', '.gob.do', '.gov.do', '.com.do', '.sld.do', '.org.do', '.net.do', '.web.do', '.mil.do', '.art.do',
		//.dz
		'.com.dz', '.org.dz', '.net.dz', '.gov.dz', '.edu.dz', '.asso.dz', '.pol.dz', '.art.dz',
		//.ec
		'.com.ec', '.info.ec', '.net.ec', '.fin.ec', '.med.ec', '.pro.ec', '.org.ec', '.edu.ec', '.gov.ec', '.mil.ec',
		//.eg
		'.com.eg', '.edu.eg', '.eun.eg', '.gov.eg', '.mil.eg', '.name.eg', '.net.eg', '.org.eg', '.sci.eg',
		//.er
		'.com.er', '.edu.er', '.gov.er', '.mil.er', '.net.er', '.org.er', '.ind.er', '.rochest.er', '.w.er',
		//.es
		'.com.es', '.nom.es', '.org.es', '.gob.es', '.edu.es',
		//.et
		'.com.et', '.gov.et', '.org.et', '.edu.et', '.net.et', '.biz.et', '.name.et', '.info.et',
		//.fj
		'.ac.fj', '.biz.fj', '.com.fj', '.info.fj', '.mil.fj', '.name.fj', '.net.fj', '.org.fj', '.pro.fj',
		//.fk
		'.co.fk', '.org.fk', '.gov.fk', '.ac.fk', '.nom.fk', '.net.fk',
		//.fr
		'.fr', '.tm.fr', '.asso.fr', '.nom.fr', '.prd.fr', '.presse.fr', '.com.fr', '.gouv.fr',
		//.gg
		'.co.gg', '.net.gg', '.org.gg',
		//.gh
		'.com.gh', '.edu.gh', '.gov.gh', '.org.gh', '.mil.gh',
		//.gn
		'.com.gn', '.ac.gn', '.gov.gn', '.org.gn', '.net.gn',
		//.gr
		'.com.gr', '.edu.gr', '.net.gr', '.org.gr', '.gov.gr', '.mil.gr',
		//.gt
		'.com.gt', '.edu.gt', '.net.gt', '.gob.gt', '.org.gt', '.mil.gt', '.ind.gt',
		//.gu
		'.com.gu', '.net.gu', '.gov.gu', '.org.gu', '.edu.gu',
		//.hk
		'.com.hk', '.edu.hk', '.gov.hk', '.idv.hk', '.net.hk', '.org.hk',
		//.id
		'.ac.id', '.co.id', '.net.id', '.or.id', '.web.id', '.sch.id', '.mil.id', '.go.id', '.war.net.id',
		//.il
		'.ac.il', '.co.il', '.org.il', '.net.il', '.k12.il', '.gov.il', '.muni.il', '.idf.il',
		//.in
		'.in', '.co.in', '.firm.in', '.net.in', '.org.in', '.gen.in', '.ind.in', '.ac.in', '.edu.in', '.res.in',
		'.ernet.in', '.gov.in', '.mil.in', '.nic.in', '.nic.in',
		//.iq
		'.iq', '.gov.iq', '.edu.iq', '.com.iq', '.mil.iq', '.org.iq', '.net.iq',
		//.ir
		'.ir', '.ac.ir', '.co.ir', '.gov.ir', '.id.ir', '.net.ir', '.org.ir', '.sch.ir', '.dnssec.ir',
		//.it
		'.gov.it', '.edu.it',
		//.je
		'.co.je', '.net.je', '.org.je',
		//.jo
		'.com.jo', '.net.jo', '.gov.jo', '.edu.jo', '.org.jo', '.mil.jo', '.name.jo', '.sch.jo',
		//.jp
		'.ac.jp', '.ad.jp', '.co.jp', '.ed.jp', '.go.jp', '.gr.jp', '.lg.jp', '.ne.jp', '.or.jp',
		//.ke
		'.co.ke', '.or.ke', '.ne.ke', '.go.ke', '.ac.ke', '.sc.ke', '.me.ke', '.mobi.ke', '.info.ke',
		//.kh
		'.per.kh', '.com.kh', '.edu.kh', '.gov.kh', '.mil.kh', '.net.kh', '.org.kh',
		//.ki
		'.com.ki', '.biz.ki', '.de.ki', '.net.ki', '.info.ki', '.org.ki', '.gov.ki', '.edu.ki', '.mob.ki', '.tel.ki',
		//.km
		'.km', '.com.km', '.coop.km', '.asso.km', '.nom.km', '.presse.km', '.tm.km', '.medecin.km', '.notaires.km',
		'.pharmaciens.km', '.veterinaire.km', '.edu.km', '.gouv.km', '.mil.km',
		//.kn
		'.net.kn', '.org.kn', '.edu.kn', '.gov.kn',
		//.kr
		'.kr', '.co.kr', '.ne.kr', '.or.kr', '.re.kr', '.pe.kr', '.go.kr', '.mil.kr', '.ac.kr', '.hs.kr', '.ms.kr',
		'.es.kr', '.sc.kr', '.kg.kr', '.seoul.kr', '.busan.kr', '.daegu.kr', '.incheon.kr', '.gwangju.kr',
		'.daejeon.kr', '.ulsan.kr', '.gyeonggi.kr', '.gangwon.kr', '.chungbuk.kr', '.chungnam.kr', '.jeonbuk.kr',
		'.jeonnam.kr', '.gyeongbuk.kr', '.gyeongnam.kr', '.jeju.kr',
		//.kw
		'.edu.kw', '.com.kw', '.net.kw', '.org.kw', '.gov.kw',
		//.ky
		'.com.ky', '.org.ky', '.net.ky', '.edu.ky', '.gov.ky',
		//.kz
		'.com.kz', '.edu.kz', '.gov.kz', '.mil.kz', '.net.kz', '.org.kz',
		//.lb
		'.com.lb', '.edu.lb', '.gov.lb', '.net.lb', '.org.lb',
		//.lk
		'.gov.lk', '.sch.lk', '.net.lk', '.int.lk', '.com.lk', '.org.lk', '.edu.lk', '.ngo.lk', '.soc.lk', '.web.lk',
		 '.ltd.lk', '.assn.lk', '.grp.lk', '.hotel.lk',
		//.lr
		'.com.lr', '.edu.lr', '.gov.lr', '.org.lr', '.net.lr',
		//.lv
		'.com.lv', '.edu.lv', '.gov.lv', '.org.lv', '.mil.lv', '.id.lv', '.net.lv', '.asn.lv', '.conf.lv',
		//.ly
		'.com.ly', '.net.ly', '.gov.ly', '.plc.ly', '.edu.ly', '.sch.ly', '.med.ly', '.org.ly', '.id.ly',
		//.ma
		'.ma', '.net.ma', '.ac.ma', '.org.ma', '.gov.ma', '.press.ma', '.co.ma',
		//.mc
		'.tm.mc', '.asso.mc',
		//.me
		'.co.me', '.net.me', '.org.me', '.edu.me', '.ac.me', '.gov.me', '.its.me', '.priv.me',
		//.mg
		'.org.mg', '.nom.mg', '.gov.mg', '.prd.mg', '.tm.mg', '.edu.mg', '.mil.mg', '.com.mg',
		//.mk
		'.com.mk', '.org.mk', '.net.mk', '.edu.mk', '.gov.mk', '.inf.mk', '.name.mk', '.pro.mk',
		//.ml
		'.com.ml', '.net.ml', '.org.ml', '.edu.ml', '.gov.ml', '.presse.ml',
		//.mn
		'.gov.mn', '.edu.mn', '.org.mn',
		//.mo
		'.com.mo', '.edu.mo', '.gov.mo', '.net.mo', '.org.mo',
		//.mt
		'.com.mt', '.org.mt', '.net.mt', '.edu.mt', '.gov.mt',
		//.mv
		'.aero.mv', '.biz.mv', '.com.mv', '.coop.mv', '.edu.mv', '.gov.mv', '.info.mv', '.int.mv', '.mil.mv',
		'.museum.mv', '.name.mv', '.net.mv', '.org.mv', '.pro.mv',
		//.mw
		'.ac.mw', '.co.mw', '.com.mw', '.coop.mw', '.edu.mw', '.gov.mw', '.int.mw', '.museum.mw', '.net.mw', '.org.mw',
		//.mx
		'.com.mx', '.net.mx', '.org.mx', '.edu.mx', '.gob.mx',
		//.my
		'.com.my', '.net.my', '.org.my', '.gov.my', '.edu.my', '.sch.my', '.mil.my', '.name.my',
		//.nf
		'.com.nf', '.net.nf', '.arts.nf', '.store.nf', '.web.nf', '.firm.nf', '.info.nf', '.other.nf', '.per.nf',
		'.rec.nf',
		//.ng
		'.com.ng', '.org.ng', '.gov.ng', '.edu.ng', '.net.ng', '.sch.ng', '.name.ng', '.mobi.ng', '.biz.ng', '.mil.ng',
		//.ni
		'.gob.ni', '.co.ni', '.com.ni', '.ac.ni', '.edu.ni', '.org.ni', '.nom.ni', '.net.ni', '.mil.ni',
		//.np
		'.com.np', '.edu.np', '.gov.np', '.org.np', '.mil.np', '.net.np',
		//.nr
		'.edu.nr', '.gov.nr', '.biz.nr', '.info.nr', '.net.nr', '.org.nr', '.com.nr',
		//.om
		'.com.om', '.co.om', '.edu.om', '.ac.om', '.sch.om', '.gov.om', '.net.om', '.org.om', '.mil.om', '.museum.om',
		'.biz.om', '.pro.om', '.med.om',
		//.pe
		'.edu.pe', '.gob.pe', '.nom.pe', '.mil.pe', '.sld.pe', '.org.pe', '.com.pe', '.net.pe',
		//.ph
		'.com.ph', '.net.ph', '.org.ph', '.mil.ph', '.ngo.ph', '.i.ph', '.gov.ph', '.edu.ph',
		//.pk
		'.com.pk', '.net.pk', '.edu.pk', '.org.pk', '.fam.pk', '.biz.pk', '.web.pk', '.gov.pk', '.gob.pk', '.gok.pk',
		'.gon.pk', '.gop.pk', '.gos.pk',
		//.pl
		'.pwr.pl', '.com.pl', '.biz.pl', '.net.pl', '.art.pl', '.edu.pl', '.org.pl', '.ngo.pl', '.gov.pl', '.info.pl',
		 '.mil.pl', '.waw.pl', '.warszawa.pl', '.wroc.pl', '.wroclaw.pl', '.krakow.pl', '.katowice.pl', '.poznan.pl',
		 '.lodz.pl', '.gda.pl', '.gdansk.pl', '.slupsk.pl', '.radom.pl', '.szczecin.pl', '.lublin.pl', '.bialystok.pl',
		 '.olsztyn.pl', '.torun.pl', '.gorzow.pl', '.zgora.pl',
		//.pr
		'.biz.pr', '.com.pr', '.edu.pr', '.gov.pr', '.info.pr', '.isla.pr', '.name.pr', '.net.pr', '.org.pr', '.pro.pr',
		'.est.pr', '.prof.pr', '.ac.pr',
		//.ps
		'.com.ps', '.net.ps', '.org.ps', '.edu.ps', '.gov.ps', '.plo.ps', '.sec.ps',
		//.pw
		'.co.pw', '.ne.pw', '.or.pw', '.ed.pw', '.go.pw', '.belau.pw',
		//.ro
		'.arts.ro', '.com.ro', '.firm.ro', '.info.ro', '.nom.ro', '.nt.ro', '.org.ro', '.rec.ro', '.store.ro',
		'.tm.ro', '.www.ro',
		//.rs
		'.co.rs', '.org.rs', '.edu.rs', '.ac.rs', '.gov.rs', '.in.rs',
		//.sb
		'.com.sb', '.net.sb', '.edu.sb', '.org.sb', '.gov.sb',
		//.sc
		'.com.sc', '.net.sc', '.edu.sc', '.gov.sc', '.org.sc',
		//.sh
		'.co.sh', '.com.sh', '.org.sh', '.gov.sh', '.edu.sh', '.net.sh', '.nom.sh',
		//.sl
		'.com.sl', '.net.sl', '.org.sl', '.edu.sl', '.gov.sl',
		//.st
		'.gov.st', '.saotome.st', '.principe.st', '.consulado.st', '.embaixada.st', '.org.st', '.edu.st', '.net.st',
		'.com.st', '.store.st', '.mil.st', '.co.st',
		//.sv
		'.edu.sv', '.gob.sv', '.com.sv', '.org.sv', '.red.sv',
		//.sz
		'.co.sz', '.ac.sz', '.org.sz',
		//.tr
		'.com.tr', '.gen.tr', '.org.tr', '.biz.tr', '.info.tr', '.av.tr', '.dr.tr', '.pol.tr', '.bel.tr', '.tsk.tr',
		'.bbs.tr', '.k12.tr', '.edu.tr', '.name.tr', '.net.tr', '.gov.tr', '.web.tr', '.tel.tr', '.tv.tr',
		//.tt
		'.co.tt', '.com.tt', '.org.tt', '.net.tt', '.biz.tt', '.info.tt', '.pro.tt', '.int.tt', '.coop.tt', '.jobs.tt',
		'.mobi.tt', '.travel.tt', '.museum.tt', '.aero.tt', '.cat.tt', '.tel.tt', '.name.tt', '.mil.tt', '.edu.tt',
		'.gov.tt',
		//.tw
		'.edu.tw', '.gov.tw', '.mil.tw', '.com.tw', '.net.tw', '.org.tw', '.idv.tw', '.game.tw', '.ebiz.tw', '.club.tw',
		//.mu
		'.com.mu', '.gov.mu', '.net.mu', '.org.mu', '.ac.mu', '.co.mu', '.or.mu',
		//.mz
		'.ac.mz', '.co.mz', '.edu.mz', '.org.mz', '.gov.mz',
		//.na
		'.com.na', '.co.na',
		//.nz
		'.ac.nz', '.co.nz', '.cri.nz', '.geek.nz', '.gen.nz', '.govt.nz', '.health.nz', '.iwi.nz', '.maori.nz',
		'.mil.nz', '.net.nz', '.org.nz', '.parliament.nz', '.school.nz',
		//.pa
		'.abo.pa', '.ac.pa', '.com.pa', '.edu.pa', '.gob.pa', '.ing.pa', '.med.pa', '.net.pa', '.nom.pa',
		'.org.pa', '.sld.pa',
		//.pt
		'.com.pt', '.edu.pt', '.gov.pt', '.int.pt', '.net.pt', '.nome.pt', '.org.pt', '.publ.pt',
		//.py
		'.com.py', '.edu.py', '.gov.py', '.mil.py', '.net.py', '.org.py',
		//.qa
		'.com.qa', '.edu.qa', '.gov.qa', '.mil.qa', '.net.qa', '.org.qa',
		//.re
		'.asso.re', '.com.re', '.nom.re',
		//.ru
		'.ac.ru', '.adygeya.ru', '.altai.ru', '.amur.ru', '.arkhangelsk.ru', '.astrakhan.ru', '.bashkiria.ru',
		'.belgorod.ru', '.bir.ru', '.bryansk.ru', '.buryatia.ru', '.cbg.ru', '.chel.ru', '.chelyabinsk.ru',
		'.chita.ru', '.chita.ru', '.chukotka.ru', '.chuvashia.ru', '.com.ru', '.dagestan.ru', '.e-burg.ru',
		'.edu.ru', '.gov.ru', '.grozny.ru', '.int.ru', '.irkutsk.ru', '.ivanovo.ru', '.izhevsk.ru', '.jar.ru',
		'.joshkar-ola.ru', '.kalmykia.ru', '.kaluga.ru', '.kamchatka.ru', '.karelia.ru', '.kazan.ru', '.kchr.ru',
		'.kemerovo.ru', '.khabarovsk.ru', '.khakassia.ru', '.khv.ru', '.kirov.ru', '.koenig.ru', '.komi.ru',
		'.kostroma.ru', '.kranoyarsk.ru', '.kuban.ru', '.kurgan.ru', '.kursk.ru', '.lipetsk.ru', '.magadan.ru',
		'.mari.ru', '.mari-el.ru', '.marine.ru', '.mil.ru', '.mordovia.ru', '.mosreg.ru', '.msk.ru', '.murmansk.ru',
		'.nalchik.ru', '.net.ru', '.nnov.ru', '.nov.ru', '.novosibirsk.ru', '.nsk.ru', '.omsk.ru', '.orenburg.ru',
		'.org.ru', '.oryol.ru', '.penza.ru', '.perm.ru', '.pp.ru', '.pskov.ru', '.ptz.ru', '.rnd.ru', '.ryazan.ru',
		'.sakhalin.ru', '.samara.ru', '.saratov.ru', '.simbirsk.ru', '.smolensk.ru', '.spb.ru', '.stavropol.ru',
		'.stv.ru', '.surgut.ru', '.tambov.ru', '.tatarstan.ru', '.tom.ru', '.tomsk.ru', '.tsaritsyn.ru', '.tsk.ru',
		'.tula.ru', '.tuva.ru', '.tver.ru', '.tyumen.ru', '.udm.ru', '.udmurtia.ru', '.ulan-ude.ru', '.vladikavkaz.ru',
		'.vladimir.ru', '.vladivostok.ru', '.volgograd.ru', '.vologda.ru', '.voronezh.ru', '.vrn.ru', '.vyatka.ru',
		'.yakutia.ru', '.yamal.ru', '.yekaterinburg.ru', '.yuzhno-sakhalinsk.ru',
		//.rw
		'.ac.rw', '.co.rw', '.com.rw', '.edu.rw', '.gouv.rw', '.gov.rw', '.int.rw', '.mil.rw', '.net.rw',
		//.sa
		'.com.sa', '.edu.sa', '.gov.sa', '.med.sa', '.net.sa', '.org.sa', '.pub.sa', '.sch.sa',
		//.sd
		'.com.sd', '.edu.sd', '.gov.sd', '.info.sd', '.med.sd', '.net.sd', '.org.sd', '.tv.sd',
		//.se
		'.a.se', '.ac.se', '.b.se', '.bd.se', '.c.se', '.d.se', '.e.se', '.f.se', '.g.se', '.h.se', '.i.se',
		'.k.se', '.l.se', '.m.se', '.n.se', '.o.se', '.org.se', '.p.se', '.parti.se', '.pp.se', '.press.se',
		'.r.se', '.s.se', '.t.se', '.tm.se', '.u.se', '.w.se', '.x.se', '.y.se', '.z.se',
		//.sg
		'.com.sg', '.edu.sg', '.gov.sg', '.idn.sg', '.net.sg', '.org.sg', '.per.sg',
		//.sn
		'.art.sn', '.com.sn', '.edu.sn', '.gouv.sn', '.org.sn', '.perso.sn', '.univ.sn',
		//.sy
		'.com.sy', '.edu.sy', '.gov.sy', '.mil.sy', '.net.sy', '.news.sy', '.org.sy',
		//.th
		'.ac.th', '.co.th', '.go.th', '.in.th', '.mi.th', '.net.th', '.or.th',
		//.tj
		'.ac.tj', '.biz.tj', '.co.tj', '.com.tj', '.edu.tj', '.go.tj', '.gov.tj', '.info.tj', '.int.tj', '.mil.tj',
		'.name.tj', '.net.tj', '.nic.tj', '.org.tj', '.test.tj', '.web.tj',
		//.tn
		'.agrinet.tn', '.com.tn', '.defense.tn', '.edunet.tn', '.ens.tn', '.fin.tn', '.gov.tn', '.ind.tn', '.info.tn',
		'.intl.tn', '.mincom.tn', '.nat.tn', '.net.tn', '.org.tn', '.perso.tn', '.rnrt.tn', '.rns.tn',
		'.rnu.tn', '.tourism.tn',
		//.tz
		'.ac.tz', '.co.tz', '.go.tz', '.ne.tz', '.or.tz',
		//.ua
		'.biz.ua', '.cherkassy.ua', '.chernigov.ua', '.chernovtsy.ua', '.ck.ua', '.cn.ua', '.co.ua', '.com.ua',
		'.crimea.ua', '.cv.ua', '.dn.ua', '.dnepropetrovsk.ua', '.donetsk.ua', '.dp.ua', '.edu.ua', '.gov.ua',
		'.if.ua', '.in.ua', '.ivano-frankivsk.ua', '.kh.ua', '.kharkov.ua', '.kherson.ua', '.khmelnitskiy.ua',
		'.kiev.ua', '.kirovograd.ua', '.km.ua', '.kr.ua', '.ks.ua', '.kv.ua', '.lg.ua', '.lugansk.ua', '.lutsk.ua',
		'.lviv.ua', '.me.ua', '.mk.ua', '.net.ua', '.nikolaev.ua', '.od.ua', '.odessa.ua', '.org.ua', '.pl.ua',
		'.poltava.ua', '.pp.ua', '.rovno.ua', '.rv.ua', '.sebastopol.ua', '.sumy.ua', '.te.ua', '.ternopil.ua',
		'.uzhgorod.ua', '.vinnica.ua', '.vn.ua', '.zaporizhzhe.ua', '.zhitomir.ua', '.zp.ua', '.zt.ua',
		//.ug
		'.ac.ug', '.co.ug', '.go.ug', '.ne.ug', '.or.ug', '.org.ug', '.sc.ug',
		//.uk
		'.ac.uk', '.bl.uk', '.british-library.uk', '.co.uk', '.cym.uk', '.gov.uk', '.govt.uk', '.icnet.uk', '.jet.uk',
		'.lea.uk', '.ltd.uk', '.me.uk', '.mil.uk', '.mod.uk', '.mod.uk', '.national-library-scotland.uk', '.nel.uk',
		'.net.uk', '.nhs.uk', '.nhs.uk', '.nic.uk', '.nls.uk', '.org.uk', '.orgn.uk', '.parliament.uk',
		'.parliament.uk', '.plc.uk', '.police.uk', '.sch.uk', '.scot.uk', '.soc.uk',
		//.us
		'.dni.us', '.fed.us', '.isa.us', '.kids.us', '.nsn.us',
		//.uy
		'.com.uy', '.edu.uy', '.gub.uy', '.mil.uy', '.net.uy', '.org.uy',
		//.ve
		'.co.ve', '.com.ve', '.edu.ve', '.gob.ve', '.info.ve', '.mil.ve', '.net.ve', '.org.ve', '.web.ve',
		//.vi
		'.co.vi', '.com.vi', '.k12.vi', '.net.vi', '.org.vi',
		//.vn
		'.ac.vn', '.biz.vn', '.com.vn', '.edu.vn', '.gov.vn', '.health.vn', '.info.vn', '.int.vn', '.name.vn',
		'.net.vn', '.org.vn', '.pro.vn',
		//.ye
		'.co.ye', '.com.ye', '.gov.ye', '.ltd.ye', '.me.ye', '.net.ye', '.org.ye', '.plc.ye',
		//.yu
		'.ac.yu', '.co.yu', '.edu.yu', '.gov.yu', '.org.yu',
		//.za
		'.ac.za', '.agric.za', '.alt.za', '.bourse.za', '.city.za', '.co.za', '.cybernet.za', '.db.za',
		'.ecape.school.za', '.edu.za', '.fs.school.za', '.gov.za', '.gp.school.za', '.grondar.za', '.iaccess.za',
		'.imt.za', '.inca.za', '.kzn.school.za', '.landesign.za', '.law.za', '.lp.school.za', '.mil.za',
		'.mpm.school.za', '.ncape.school.za', '.net.za', '.ngo.za', '.nis.za', '.nom.za', '.nw.school.za',
		'.olivetti.za', '.org.za', '.pix.za', '.school.za', '.tm.za', '.wcape.school.za', '.web.za',
		//.zm
		'.ac.zm', '.co.zm', '.com.zm', '.edu.zm', '.gov.zm', '.net.zm', '.org.zm', '.sch.zm',
	);

/**
 * Constructor
 *
 * @param string $url Trimmed url string to use. Should not contain the application base path.
 * @param boolean $parseEnvironment Set to false to not auto parse the environment. ie. GET, POST and FILES.
 */
	public function __construct($url = null, $parseEnvironment = true) {
		$this->_base();
		if (empty($url)) {
			$url = $this->_url();
		}
		if ($url[0] === '/') {
			$url = substr($url, 1);
		}
		$this->url = $url;

		if ($parseEnvironment) {
			$this->_processPost();
			$this->_processGet();
			$this->_processFiles();
		}
		$this->here = $this->base . '/' . $this->url;
	}

/**
 * process the post data and set what is there into the object.
 * processed data is available at `$this->data`
 *
 * Will merge POST vars prefixed with `data`, and ones without
 * into a single array. Variables prefixed with `data` will overwrite those without.
 *
 * If you have mixed POST values be careful not to make any top level keys numeric
 * containing arrays. Hash::merge() is used to merge data, and it has possibly
 * unexpected behavior in this situation.
 *
 * @return void
 */
	protected function _processPost() {
		if ($_POST) {
			$this->data = $_POST;
		} elseif (
			($this->is('put') || $this->is('delete')) &&
			strpos(env('CONTENT_TYPE'), 'application/x-www-form-urlencoded') === 0
		) {
				$data = $this->_readInput();
				parse_str($data, $this->data);
		}
		if (ini_get('magic_quotes_gpc') === '1') {
			$this->data = stripslashes_deep($this->data);
		}
		if (env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
			$this->data['_method'] = env('HTTP_X_HTTP_METHOD_OVERRIDE');
		}
		$isArray = is_array($this->data);
		if ($isArray && isset($this->data['_method'])) {
			if (!empty($_SERVER)) {
				$_SERVER['REQUEST_METHOD'] = $this->data['_method'];
			} else {
				$_ENV['REQUEST_METHOD'] = $this->data['_method'];
			}
			unset($this->data['_method']);
		}
		if ($isArray && isset($this->data['data'])) {
			$data = $this->data['data'];
			if (count($this->data) <= 1) {
				$this->data = $data;
			} else {
				unset($this->data['data']);
				$this->data = Hash::merge($this->data, $data);
			}
		}
	}

/**
 * Process the GET parameters and move things into the object.
 *
 * @return void
 */
	protected function _processGet() {
		if (ini_get('magic_quotes_gpc') === '1') {
			$query = stripslashes_deep($_GET);
		} else {
			$query = $_GET;
		}

		unset($query['/' . str_replace('.', '_', urldecode($this->url))]);
		if (strpos($this->url, '?') !== false) {
			list(, $querystr) = explode('?', $this->url);
			parse_str($querystr, $queryArgs);
			$query += $queryArgs;
		}
		if (isset($this->params['url'])) {
			$query = array_merge($this->params['url'], $query);
		}
		$this->query = $query;
	}

/**
 * Get the request uri. Looks in PATH_INFO first, as this is the exact value we need prepared
 * by PHP. Following that, REQUEST_URI, PHP_SELF, HTTP_X_REWRITE_URL and argv are checked in that order.
 * Each of these server variables have the base path, and query strings stripped off
 *
 * @return string URI The CakePHP request path that is being accessed.
 */
	protected function _url() {
		if (!empty($_SERVER['PATH_INFO'])) {
			return $_SERVER['PATH_INFO'];
		} elseif (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '://') === false) {
			$uri = $_SERVER['REQUEST_URI'];
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$qPosition = strpos($_SERVER['REQUEST_URI'], '?');
			if ($qPosition !== false && strpos($_SERVER['REQUEST_URI'], '://') > $qPosition) {
				$uri = $_SERVER['REQUEST_URI'];
			} else {
				$uri = substr($_SERVER['REQUEST_URI'], strlen(FULL_BASE_URL));
			}
		} elseif (isset($_SERVER['PHP_SELF']) && isset($_SERVER['SCRIPT_NAME'])) {
			$uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
		} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			$uri = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif ($var = env('argv')) {
			$uri = $var[0];
		}

		$base = $this->base;

		if (strlen($base) > 0 && strpos($uri, $base) === 0) {
			$uri = substr($uri, strlen($base));
		}
		if (strpos($uri, '?') !== false) {
			list($uri) = explode('?', $uri, 2);
		}
		if (empty($uri) || $uri === '/' || $uri === '//' || $uri === '/index.php') {
			return '/';
		}
		return $uri;
	}

/**
 * Returns a base URL and sets the proper webroot
 *
 * @return string Base URL
 */
	protected function _base() {
		$dir = $webroot = null;
		$config = Configure::read('App');
		extract($config);

		if (!isset($base)) {
			$base = $this->base;
		}
		if ($base !== false) {
			$this->webroot = $base . '/';
			return $this->base = $base;
		}

		if (!$baseUrl) {
			$base = dirname(env('PHP_SELF'));

			if ($webroot === 'webroot' && $webroot === basename($base)) {
				$base = dirname($base);
			}
			if ($dir === 'app' && $dir === basename($base)) {
				$base = dirname($base);
			}

			if ($base === DS || $base === '.') {
				$base = '';
			}

			$this->webroot = $base . '/';
			return $this->base = $base;
		}

		$file = '/' . basename($baseUrl);
		$base = dirname($baseUrl);

		if ($base === DS || $base === '.') {
			$base = '';
		}
		$this->webroot = $base . '/';

		$docRoot = env('DOCUMENT_ROOT');
		$docRootContainsWebroot = strpos($docRoot, $dir . DS . $webroot);

		if (!empty($base) || !$docRootContainsWebroot) {
			if (strpos($this->webroot, '/' . $dir . '/') === false) {
				$this->webroot .= $dir . '/';
			}
			if (strpos($this->webroot, '/' . $webroot . '/') === false) {
				$this->webroot .= $webroot . '/';
			}
		}
		return $this->base = $base . $file;
	}

/**
 * Process $_FILES and move things into the object.
 *
 * @return void
 */
	protected function _processFiles() {
		if (isset($_FILES) && is_array($_FILES)) {
			foreach ($_FILES as $name => $data) {
				if ($name !== 'data') {
					$this->params['form'][$name] = $data;
				}
			}
		}

		if (isset($_FILES['data'])) {
			foreach ($_FILES['data'] as $key => $data) {
				$this->_processFileData('', $data, $key);
			}
		}
	}

/**
 * Recursively walks the FILES array restructuring the data
 * into something sane and useable.
 *
 * @param string $path The dot separated path to insert $data into.
 * @param array $data The data to traverse/insert.
 * @param string $field The terminal field name, which is the top level key in $_FILES.
 * @return void
 */
	protected function _processFileData($path, $data, $field) {
		foreach ($data as $key => $fields) {
			$newPath = $key;
			if (!empty($path)) {
				$newPath = $path . '.' . $key;
			}
			if (is_array($fields)) {
				$this->_processFileData($newPath, $fields, $field);
			} else {
				$newPath .= '.' . $field;
				$this->data = Hash::insert($this->data, $newPath, $fields);
			}
		}
	}

/**
 * Get the IP the client is using, or says they are using.
 *
 * @param boolean $safe Use safe = false when you think the user might manipulate their HTTP_CLIENT_IP
 *   header. Setting $safe = false will will also look at HTTP_X_FORWARDED_FOR
 * @return string The client IP.
 */
	public function clientIp($safe = true) {
		if (!$safe && env('HTTP_X_FORWARDED_FOR')) {
			$ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
		} else {
			if (env('HTTP_CLIENT_IP')) {
				$ipaddr = env('HTTP_CLIENT_IP');
			} else {
				$ipaddr = env('REMOTE_ADDR');
			}
		}

		if (env('HTTP_CLIENTADDRESS')) {
			$tmpipaddr = env('HTTP_CLIENTADDRESS');

			if (!empty($tmpipaddr)) {
				$ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
			}
		}
		return trim($ipaddr);
	}

/**
 * Returns the referer that referred this request.
 *
 * @param boolean $local Attempt to return a local address. Local addresses do not contain hostnames.
 * @return string The referring address for this request.
 */
	public function referer($local = false) {
		$ref = env('HTTP_REFERER');
		$forwarded = env('HTTP_X_FORWARDED_HOST');
		if ($forwarded) {
			$ref = $forwarded;
		}

		$base = '';
		if (defined('FULL_BASE_URL')) {
			$base = FULL_BASE_URL . $this->webroot;
		}
		if (!empty($ref) && !empty($base)) {
			if ($local && strpos($ref, $base) === 0) {
				$ref = substr($ref, strlen($base));
				if ($ref[0] !== '/') {
					$ref = '/' . $ref;
				}
				return $ref;
			} elseif (!$local) {
				return $ref;
			}
		}
		return '/';
	}

/**
 * Missing method handler, handles wrapping older style isAjax() type methods
 *
 * @param string $name The method called
 * @param array $params Array of parameters for the method call
 * @return mixed
 * @throws CakeException when an invalid method is called.
 */
	public function __call($name, $params) {
		if (strpos($name, 'is') === 0) {
			$type = strtolower(substr($name, 2));
			return $this->is($type);
		}
		throw new CakeException(__d('cake_dev', 'Method %s does not exist', $name));
	}

/**
 * Magic get method allows access to parsed routing parameters directly on the object.
 *
 * Allows access to `$this->params['controller']` via `$this->controller`
 *
 * @param string $name The property being accessed.
 * @return mixed Either the value of the parameter or null.
 */
	public function __get($name) {
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
		return null;
	}

/**
 * Magic isset method allows isset/empty checks
 * on routing parameters.
 *
 * @param string $name The property being accessed.
 * @return bool Existence
 */
	public function __isset($name) {
		return isset($this->params[$name]);
	}

/**
 * Check whether or not a Request is a certain type. Uses the built in detection rules
 * as well as additional rules defined with CakeRequest::addDetector(). Any detector can be called
 * as `is($type)` or `is$Type()`.
 *
 * @param string $type The type of request you want to check.
 * @return boolean Whether or not the request is the type you are checking.
 */
	public function is($type) {
		$type = strtolower($type);
		if (!isset($this->_detectors[$type])) {
			return false;
		}
		$detect = $this->_detectors[$type];
		if (isset($detect['env'])) {
			if (isset($detect['value'])) {
				return env($detect['env']) == $detect['value'];
			}
			if (isset($detect['pattern'])) {
				return (bool)preg_match($detect['pattern'], env($detect['env']));
			}
			if (isset($detect['options'])) {
				$pattern = '/' . implode('|', $detect['options']) . '/i';
				return (bool)preg_match($pattern, env($detect['env']));
			}
		}
		if (isset($detect['param'])) {
			$key = $detect['param'];
			$value = $detect['value'];
			return isset($this->params[$key]) ? $this->params[$key] == $value : false;
		}
		if (isset($detect['callback']) && is_callable($detect['callback'])) {
			return call_user_func($detect['callback'], $this);
		}
		return false;
	}

/**
 * Add a new detector to the list of detectors that a request can use.
 * There are several different formats and types of detectors that can be set.
 *
 * ### Environment value comparison
 *
 * An environment value comparison, compares a value fetched from `env()` to a known value
 * the environment value is equality checked against the provided value.
 *
 * e.g `addDetector('post', array('env' => 'REQUEST_METHOD', 'value' => 'POST'))`
 *
 * ### Pattern value comparison
 *
 * Pattern value comparison allows you to compare a value fetched from `env()` to a regular expression.
 *
 * e.g `addDetector('iphone', array('env' => 'HTTP_USER_AGENT', 'pattern' => '/iPhone/i'));`
 *
 * ### Option based comparison
 *
 * Option based comparisons use a list of options to create a regular expression. Subsequent calls
 * to add an already defined options detector will merge the options.
 *
 * e.g `addDetector('mobile', array('env' => 'HTTP_USER_AGENT', 'options' => array('Fennec')));`
 *
 * ### Callback detectors
 *
 * Callback detectors allow you to provide a 'callback' type to handle the check. The callback will
 * receive the request object as its only parameter.
 *
 * e.g `addDetector('custom', array('callback' => array('SomeClass', 'somemethod')));`
 *
 * ### Request parameter detectors
 *
 * Allows for custom detectors on the request parameters.
 *
 * e.g `addDetector('post', array('param' => 'requested', 'value' => 1)`
 *
 * @param string $name The name of the detector.
 * @param array $options  The options for the detector definition. See above.
 * @return void
 */
	public function addDetector($name, $options) {
		$name = strtolower($name);
		if (isset($this->_detectors[$name]) && isset($options['options'])) {
			$options = Hash::merge($this->_detectors[$name], $options);
		}
		$this->_detectors[$name] = $options;
	}

/**
 * Add parameters to the request's parsed parameter set. This will overwrite any existing parameters.
 * This modifies the parameters available through `$request->params`.
 *
 * @param array $params Array of parameters to merge in
 * @return The current object, you can chain this method.
 */
	public function addParams($params) {
		$this->params = array_merge($this->params, (array)$params);
		return $this;
	}

/**
 * Add paths to the requests' paths vars. This will overwrite any existing paths.
 * Provides an easy way to modify, here, webroot and base.
 *
 * @param array $paths Array of paths to merge in
 * @return CakeRequest the current object, you can chain this method.
 */
	public function addPaths($paths) {
		foreach (array('webroot', 'here', 'base') as $element) {
			if (isset($paths[$element])) {
				$this->{$element} = $paths[$element];
			}
		}
		return $this;
	}

/**
 * Get the value of the current requests url. Will include named parameters and querystring arguments.
 *
 * @param boolean $base Include the base path, set to false to trim the base path off.
 * @return string the current request url including query string args.
 */
	public function here($base = true) {
		$url = $this->here;
		if (!empty($this->query)) {
			$url .= '?' . http_build_query($this->query, null, '&');
		}
		if (!$base) {
			$url = preg_replace('/^' . preg_quote($this->base, '/') . '/', '', $url, 1);
		}
		return $url;
	}

/**
 * Read an HTTP header from the Request information.
 *
 * @param string $name Name of the header you want.
 * @return mixed Either false on no header being set or the value of the header.
 */
	public static function header($name) {
		$name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		if (!empty($_SERVER[$name])) {
			return $_SERVER[$name];
		}
		return false;
	}

/**
 * Get the HTTP method used for this request.
 * There are a few ways to specify a method.
 *
 * - If your client supports it you can use native HTTP methods.
 * - You can set the HTTP-X-Method-Override header.
 * - You can submit an input with the name `_method`
 *
 * Any of these 3 approaches can be used to set the HTTP method used
 * by CakePHP internally, and will effect the result of this method.
 *
 * @return string The name of the HTTP method used.
 */
	public function method() {
		return env('REQUEST_METHOD');
	}

/**
 * Get the host that the request was handled on.
 *
 * @return string
 */
	public function host() {
		return env('HTTP_HOST');
	}

/**
 * Get the domain name and include $tldLength segments of the tld.
 *
 * @param integer $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
 *   While `example.co.uk` contains 2.
 * @return string Domain name without subdomains.
 */
	public function domain($tldLength = 1) {
		$segments = explode('.', $this->host());
		$domain = array_slice($segments, -1 * ($tldLength + 1));
		$domain = implode('.', $domain);

		if (in_array('.' . $domain, $this->_slds)) {
			return $this->domain($tldLength + 1);
		}

		return $domain;
	}

/**
 * Get the subdomains for a host.
 *
 * @param integer $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
 *   While `example.co.uk` contains 2.
 * @return array of subdomains.
 */
	public function subdomains($tldLength = 1) {
		$segments = explode('.', $this->host());
		$tldLength = count(explode('.', $this->domain())) - 1;
		return array_slice($segments, 0, -1 * ($tldLength + 1));
	}

/**
 * Find out which content types the client accepts or check if they accept a
 * particular type of content.
 *
 * #### Get all types:
 *
 * `$this->request->accepts();`
 *
 * #### Check for a single type:
 *
 * `$this->request->accepts('application/json');`
 *
 * This method will order the returned content types by the preference values indicated
 * by the client.
 *
 * @param string $type The content type to check for. Leave null to get all types a client accepts.
 * @return mixed Either an array of all the types the client accepts or a boolean if they accept the
 *   provided type.
 */
	public function accepts($type = null) {
		$raw = $this->parseAccept();
		$accept = array();
		foreach ($raw as $types) {
			$accept = array_merge($accept, $types);
		}
		if ($type === null) {
			return $accept;
		}
		return in_array($type, $accept);
	}

/**
 * Parse the HTTP_ACCEPT header and return a sorted array with content types
 * as the keys, and pref values as the values.
 *
 * Generally you want to use CakeRequest::accept() to get a simple list
 * of the accepted content types.
 *
 * @return array An array of prefValue => array(content/types)
 */
	public function parseAccept() {
		return $this->_parseAcceptWithQualifier($this->header('accept'));
	}

/**
 * Get the languages accepted by the client, or check if a specific language is accepted.
 *
 * Get the list of accepted languages:
 *
 * {{{ CakeRequest::acceptLanguage(); }}}
 *
 * Check if a specific language is accepted:
 *
 * {{{ CakeRequest::acceptLanguage('es-es'); }}}
 *
 * @param string $language The language to test.
 * @return If a $language is provided, a boolean. Otherwise the array of accepted languages.
 */
	public static function acceptLanguage($language = null) {
		$raw = self::_parseAcceptWithQualifier(self::header('Accept-Language'));
		$accept = array();
		foreach ($raw as $languages) {
			foreach ($languages as &$lang) {
				if (strpos($lang, '_')) {
					$lang = str_replace('_', '-', $lang);
				}
				$lang = strtolower($lang);
			}
			$accept = array_merge($accept, $languages);
		}
		if ($language === null) {
			return $accept;
		}
		return in_array(strtolower($language), $accept);
	}

/**
 * Parse Accept* headers with qualifier options
 *
 * @param string $header
 * @return array
 */
	protected static function _parseAcceptWithQualifier($header) {
		$accept = array();
		$header = explode(',', $header);
		foreach (array_filter($header) as $value) {
			$prefPos = strpos($value, ';');
			if ($prefPos !== false) {
				$prefValue = substr($value, strpos($value, '=') + 1);
				$value = trim(substr($value, 0, $prefPos));
			} else {
				$prefValue = '1.0';
				$value = trim($value);
			}
			if (!isset($accept[$prefValue])) {
				$accept[$prefValue] = array();
			}
			if ($prefValue) {
				$accept[$prefValue][] = $value;
			}
		}
		krsort($accept);
		return $accept;
	}

/**
 * Provides a read accessor for `$this->query`. Allows you
 * to use a syntax similar to `CakeSession` for reading url query data.
 *
 * @param string $name Query string variable name
 * @return mixed The value being read
 */
	public function query($name) {
		return Hash::get($this->query, $name);
	}

/**
 * Provides a read/write accessor for `$this->data`. Allows you
 * to use a syntax similar to `CakeSession` for reading post data.
 *
 * ## Reading values.
 *
 * `$request->data('Post.title');`
 *
 * When reading values you will get `null` for keys/values that do not exist.
 *
 * ## Writing values
 *
 * `$request->data('Post.title', 'New post!');`
 *
 * You can write to any value, even paths/keys that do not exist, and the arrays
 * will be created for you.
 *
 * @param string $name,... Dot separated name of the value to read/write
 * @return mixed Either the value being read, or this so you can chain consecutive writes.
 */
	public function data($name) {
		$args = func_get_args();
		if (count($args) == 2) {
			$this->data = Hash::insert($this->data, $name, $args[1]);
			return $this;
		}
		return Hash::get($this->data, $name);
	}

/**
 * Read data from `php://input`. Useful when interacting with XML or JSON
 * request body content.
 *
 * Getting input with a decoding function:
 *
 * `$this->request->input('json_decode');`
 *
 * Getting input using a decoding function, and additional params:
 *
 * `$this->request->input('Xml::build', array('return' => 'DOMDocument'));`
 *
 * Any additional parameters are applied to the callback in the order they are given.
 *
 * @param string $callback A decoding callback that will convert the string data to another
 *     representation. Leave empty to access the raw input data. You can also
 *     supply additional parameters for the decoding callback using var args, see above.
 * @return The decoded/processed request data.
 */
	public function input($callback = null) {
		$input = $this->_readInput();
		$args = func_get_args();
		if (!empty($args)) {
			$callback = array_shift($args);
			array_unshift($args, $input);
			return call_user_func_array($callback, $args);
		}
		return $input;
	}

/**
 * Only allow certain HTTP request methods, if the request method does not match
 * a 405 error will be shown and the required "Allow" response header will be set.
 *
 * Example:
 *
 * $this->request->onlyAllow('post', 'delete');
 * or
 * $this->request->onlyAllow(array('post', 'delete'));
 *
 * If the request would be GET, response header "Allow: POST, DELETE" will be set
 * and a 405 error will be returned
 *
 * @param string|array $methods Allowed HTTP request methods
 * @return boolean true
 * @throws MethodNotAllowedException
 */
	public function onlyAllow($methods) {
		if (!is_array($methods)) {
			$methods = func_get_args();
		}
		foreach ($methods as $method) {
			if ($this->is($method)) {
				return true;
			}
		}
		$allowed = strtoupper(implode(', ', $methods));
		$e = new MethodNotAllowedException();
		$e->responseHeader('Allow', $allowed);
		throw $e;
	}

/**
 * Read data from php://input, mocked in tests.
 *
 * @return string contents of php://input
 */
	protected function _readInput() {
		if (empty($this->_input)) {
			$fh = fopen('php://input', 'r');
			$content = stream_get_contents($fh);
			fclose($fh);
			$this->_input = $content;
		}
		return $this->_input;
	}

/**
 * Array access read implementation
 *
 * @param string $name Name of the key being accessed.
 * @return mixed
 */
	public function offsetGet($name) {
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
		if ($name === 'url') {
			return $this->query;
		}
		if ($name === 'data') {
			return $this->data;
		}
		return null;
	}

/**
 * Array access write implementation
 *
 * @param string $name Name of the key being written
 * @param mixed $value The value being written.
 * @return void
 */
	public function offsetSet($name, $value) {
		$this->params[$name] = $value;
	}

/**
 * Array access isset() implementation
 *
 * @param string $name thing to check.
 * @return boolean
 */
	public function offsetExists($name) {
		return isset($this->params[$name]);
	}

/**
 * Array access unset() implementation
 *
 * @param string $name Name to unset.
 * @return void
 */
	public function offsetUnset($name) {
		unset($this->params[$name]);
	}

}
