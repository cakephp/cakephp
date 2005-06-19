<?PHP

require(ROOT.'config'.DS.'paths.php');
require(LIBS.'basics.php');
require(LIBS.'file.php');
require(LIBS.'legacy.php');

function make_clean_css ($path, $name)
{
	require_once(VENDORS.'csspp'.DS.'csspp.php');

	$data = file_get_contents($path);
	$csspp = new csspp();
	$output = $csspp->compress($data);

	$ratio = 100-(round(strlen($output)/strlen($data), 3)*100);
	$output = " /* file: $name, ratio: $ratio% */ " . $output;

	return $output;
}

function write_css_cache ($path, $content)
{
	if (!is_dir(dirname($path)))
		mkdir(dirname($path));
	
	$cache = new File($path);
	return $cache->write($content);
}

if (preg_match('|\.\.|', $url) || !preg_match('|^ccss/(.+)$|i', $url, $regs)) 
	die('Wrong file name.');

$filename = 'css/'.$regs[1];
$filepath = CSS.$regs[1];
$cachepath = CACHE.'css'.DS.str_replace(array('/','\\'), '-', $regs[1]);

if (!file_exists($filepath))
	die('Wrong file name.');


if (file_exists($cachepath))
{
	$templateModified = filemtime($filepath);
	$cacheModified = filemtime($cachepath);
	
	if ($templateModified > $cacheModified)
	{
		$output = make_clean_css ($filepath, $filename);
		write_css_cache ($cachepath, $output);
	}
	else 
	{
		$output = file_get_contents($cachepath);
	}
}
else 
{
	$output = make_clean_css ($filepath, $filename);
	write_css_cache ($cachepath, $output);
}

header("Date: ".date("D, j M Y G:i:s ", $templateModified).'GMT');
header("Content-Type: text/css");
header("Expires: ".date("D, j M Y G:i:s T", time()+DAY));
header("Cache-Control: cache"); // HTTP/1.1
header("Pragma: cache"); // HTTP/1.0
print $output;

?>