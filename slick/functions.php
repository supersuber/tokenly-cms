<?php
//a collection of general functions to be used with anything


/**
*
* shortens a message to the desired number of characters
*
*/
function shortenMsg($msg, $length, $end = '...'){

	$msgLength = strlen($msg);
	$message = $msg;
	if($msgLength > $length){
		$message = substr($msg, 0, $length);
		$message .= $end;
	}

	return $message;
}

/**
* formats dates to specified format
*
*/
function formatDate($date)
{
	if(!is_int($date)){
		$date = strtotime($date);
	}
	return date(DATE_FORMAT, $date);

}


/**
 *  takes a float and scientific notation and returns a more readable version
 * 
 * */
function convertFloat($float)
{
	if($float === 0){
		return 0;
	}
	$str = rtrim(sprintf('%.8F', $float), '0');
	$checkLast = substr($str, -1);
	if($checkLast == '.'){
		$str = str_replace('.', '', $str);
	}
	return $str;
}


/**
 *  takes a string, and a count of items (usually an array)
 *  decides if string should be a plural or not
 *  just add an s to the end... make it smarter later
 * 
 * */
function pluralize($str, $itemCount, $andZero = false)
{
	if($itemCount > 1 OR ($andZero AND $itemCount == 0)){
		$str = $str.'s';
	}
	return $str;
}


/**
 *  just check if a post has been made
 * 
 * */
function posted()
{
	if(isset($_POST) AND count($_POST) > 0){
		return true;
	}
	else{
		return false;
	}
	
}

/**
 *  Turn a string into a URL friendly string
 * 
 * */
function genURL($str)
{
    $url = strtolower(trim($str));
    $url = preg_replace("/[^a-zA-Z0-9[:space:]\/s-]/", "", $url);
    $url = preg_replace("/(-| |\/)+/","-",$url);
    return $url;
}


/**
 *  Convert plain links to real links, email addresses to mailtos
 * 
 * 
 * */
function autolink($text) {

  $pattern = '/(((http[s]?:\/\/(.+(:.+)?@)?)|(www\.))[a-z0-9](([-a-z0-9]+\.)*\.[a-z]{2,})?\/?[a-z0-9.,_\/~#&=:;%+!?-]+)/is';
  $text = preg_replace($pattern, ' <a href="$1" rel="nofollow" target="_blank">$1</a>', $text);
  // fix URLs without protocols
  $text = preg_replace('/href="www/', 'href="http://www', $text);
  return $text;
}

/**
 *  generate a random salt
 * 
 * */
function createSalt()
{
	$length = mt_rand(15, 50);
	$salt = openssl_random_pseudo_bytes($length);
	$salt = bin2hex($salt);
	
	return $salt;
	
}

/**
 *  Generate a salt/pass hash combo.
 * 
 * */
function genPassSalt($str)
{
	$salt = createSalt();
	$pass = hash('sha256', $salt.$str);
	
	return array('hash' => $pass, 'salt' => $salt);
}


function decode_sessionData($data) {
    if(  strlen( $data) == 0)
    {
        return array();
    }
    
    // match all the session keys and offsets
    preg_match_all('/(^|;|\})([a-zA-Z0-9_]+)\|/i', $data, $matchesarray, PREG_OFFSET_CAPTURE);

    $returnArray = array();

    $lastOffset = null;
    $currentKey = '';
    foreach ( $matchesarray[2] as $value )
    {
        $offset = $value[1];
        if(!is_null( $lastOffset))
        {
            $valueText = substr($data, $lastOffset, $offset - $lastOffset );
            $returnArray[$currentKey] = unserialize($valueText);
        }
        $currentKey = $value[0];

        $lastOffset = $offset + strlen( $currentKey )+1;
    }

    $valueText = substr($data, $lastOffset );
    $returnArray[$currentKey] = unserialize($valueText);
    
    return $returnArray;
}

function get_mime($filepath) {
	$finfo = new finfo(FILEINFO_MIME);
	$output = $finfo->file($filepath);
    $output = explode("; ",$output);
    if ( is_array($output) ) {
        $output = strtolower($output[0]);
    }
    return $output;
}

function get_string_between($string, $start, $end){
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);   
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}

function debug($var)
{
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

function timestamp()
{
	return date('Y-m-d H:i:s');
}

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        @$sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

function boolToText($num, $yes = 'yes', $no = 'no')
{
	if($num === true || intval($num) === 1){
		return $yes;
	}
	else{
		return $no;
	}
}

function shorten($text, $length = 200, $url = '', $more = '', $moreClass = ''){
$short = mb_substr($text, 0, $length);

if($short != $text) {
    $lastspace = strrpos($short, ' ');
    $short = substr($short , 0, $lastspace);
	
	$short .= ' ...';
    if($more != ''){
         $short .= ' <a href="'.$url.'" class="'.$moreClass.'">'.$more.'</a> ';
    } // end if more is blank

   
} // end if content != short

$short = str_replace("’","'", $short);
$short = stripslashes($short);
$short = nl2br($short);

return $short;

} // end short function


function get_object_vars_all($obj) {
  $objArr = substr(str_replace(get_class($obj)."::__set_state(","",var_export($obj,true)),0,-1);
  eval("\$values = $objArr;");
  return $values;
}

function mention($str, $message, $userId, $itemId = 0, $type = '', $notifyData = array(), $whitelist = false)
{
	$match = preg_match_all('/\B\@([\w\-]+)/', $str, $matches);
	$model = new \Core\Model;
	$tca = new \App\Tokenly\TCA_Model;

	$profileModule = $model->get('modules', 'user-profile', array(), 'slug');
	$getSite = $model->get('sites', $_SERVER['HTTP_HOST'], array(), 'domain');
	$thisUser = $model->get('users', $userId, array('userId', 'username', 'slug'));
	
	$success = false;
	foreach($matches[1] as $user){
		$user = strtolower(trim($user));

		$getUser = $model->get('users', $user, array('userId', 'username'), 'slug');
		if($getUser AND $getUser['userId'] != $userId){
			if($whitelist AND !in_array($getUser['userId'], $whitelist)){
				continue;
			}

			$replace = $thisUser['username'];
			$checkTCA = $tca->checkItemAccess($getUser['userId'], $profileModule['moduleId'], $thisUser['userId'], 'user-profile');
			if($checkTCA){
				$replace = '<a href="'.$getSite['url'].'/profile/user/'.$thisUser['slug'].'">'.$replace.'</a>';
			}
			$notifyData['username'] = $replace;
			$message = str_replace('%username%', $replace, $message);
			$notify = \App\Meta_Model::notifyUser($getUser['userId'], $message, $itemId, $type, false, $notifyData);
			if($notify){
				$success = true;
			}
		}
	}
	if($success){
		return true;
	}
	
	return false;
	
	
}

function markdown($str)
{
	require_once(SITE_PATH.'/resources/Parsedown.php');
	$parsedown = new Parsedown();
	$parse =  $parsedown->parse($str);
	
	/*** @mention mod ***/
	$match = preg_match_all('/\B\@([\w\-]+)/', $str, $matches);
	$model = new \Core\Model;
	
	$getSite = $model->get('sites', $_SERVER['HTTP_HOST'], array(), 'domain');
	
	$success = false;
	foreach($matches[1] as $user){
		$orig = '@'.$user;
		$user = strtolower(trim($user));
		//$user = substr($user, 1);
		
		$getUser = $model->get('users', $user, array('userId', 'username', 'slug'), 'slug');
		if($getUser){
			$parse = str_replace($orig, '<a href="'.$getSite['url'].'/profile/user/'.$getUser['slug'].'">'.$orig.'</a>', $parse);
		}
	}
	
	/*** end @mention mod ***/
	
	return $parse;
}


function parseRawInput()
{
	// Fetch content and determine boundary
	$raw_data = file_get_contents('php://input');
	
	$boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));
	if(trim($boundary) == ''){
		return array();
	}

	// Fetch each part

	$parts = array_slice(explode($boundary, $raw_data), 1);
	$data = array();

	foreach ($parts as $part) {
		// If this is the last part, break
		if ($part == "--\r\n") break; 

		// Separate content from headers
		$part = ltrim($part, "\r\n");
		
		list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);
		
		// Parse the headers list
		$raw_headers = explode("\r\n", $raw_headers);
		$headers = array();
		foreach ($raw_headers as $header) {
			list($name, $value) = explode(':', $header);
			$headers[strtolower($name)] = ltrim($value, ' '); 
		} 

		// Parse the Content-Disposition to get the field name, etc.
		if (isset($headers['content-disposition'])) {
			$filename = null;
			preg_match(
				'/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/', 
				$headers['content-disposition'], 
				$matches
			);
			list(, $type, $name) = $matches;
			isset($matches[4]) and $filename = $matches[4]; 

			// handle your fields here
			switch ($name) {
				// this is a file upload
				case 'userfile':
					 file_put_contents($filename, $body);
					 break;

				// default for all other files is to populate $data
				default: 
					 $data[$name] = substr($body, 0, strlen($body) - 2);
					 break;
			} 
		}

	}
	
	return $data;
		
}

function checkRequiredFields($data, $fields = array())
{
	$req = array();
	foreach($data as $k => $v){
		$req[$k] = false;
		foreach($fields as $fieldK => $field){
			if(is_numeric($fieldK)){
				if($k == $field){
					$req[$k] = true;
				}
			}
			else{
				if($fieldK == $k AND $field){
					$req[$k] = true;
				}
			}
		}
	}
	$useData = array();
	foreach($req as $key => $required){
		if(!isset($data[$key]) OR trim($data[$key]) == ''){
			if($required){
				throw new \Exception(ucfirst($key).' required');
			}
			else{
				$useData[$key] = '';
			}
		}
		else{
			$useData[$key] = $data[$key];
		}
	}
	return $useData;
}

function arrayToCSV($data)
{
	$output = array();
	foreach($data as $row){
		if(is_array($row)){
			foreach($row as $rk => $rv){
				$row[$rk] = '"'.str_replace('"','',$rv).'"';
			}
		}
		else{
			$row = array($row);
		}
		$row = join(',',$row);
		$output[] = $row;
	}
	$output = join("\n", $output);
	return trim($output);
}

function isExternalLink($link)
{
	$link = trim($link);
	if(substr($link, 0, 7) == 'http://' OR substr($link, 0, 8) == 'https://'){
		return true;
	}
	return false;
}

function extract_row(&$data, $vals, $returnEmpty = false, $cache_key = false)
{
	
	if($cache_key){
		$rowLock = md5(json_encode($vals).intval($returnEmpty).$cache_key);
		if(isset(\App\Meta_Model::$metaCache[$rowLock])){
			return \App\Meta_Model::$metaCache[$rowLock];
		}
	}
	
	$output = array();
	foreach($data as $dk => $row){
		$found = true;
		foreach($vals as $key => $val){
			if(!isset($row[$key]) OR $row[$key] != $val){
				$found = false;
				break;
			}
		}
		if($found){
			if($cache_key){
				unset($data[$dk]);
			}
			$output[] = $row;
		}
	}
		
	if(!$returnEmpty AND count($output) == 0){
		if($cache_key){
			\App\Meta_Model::$metaCache[$rowLock] = false;
		}
		return false;
	}
	
	if($cache_key){
		\App\Meta_Model::$metaCache[$rowLock] = $output;
	}	
	
	return $output;
}

function encrypt_string($string)
{
	if(!defined('ENCRYPT_KEY')){
		return false;
	}
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(ENCRYPT_KEY), $string, MCRYPT_MODE_CBC, md5(md5(ENCRYPT_KEY))));
}

function decrypt_string($string)
{
	if(!defined('ENCRYPT_KEY')){
		return false;
	}
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(ENCRYPT_KEY), base64_decode($string), MCRYPT_MODE_CBC, md5(md5(ENCRYPT_KEY))), "\0");
	
}

function replaceNonSGML($string)
{
	//taken from a stackoverflow post
	$string = utf8_encode($string);
	$chr_map = array(
	   // Windows codepage 1252
	   "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
	   "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
	   "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
	   "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
	   "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
	   "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
	   "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
	   "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

	   // Regular Unicode     // U+0022 quotation mark (")
							  // U+0027 apostrophe     (')
	   "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
	   "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
	   "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
	   "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
	   "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
	   "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
	   "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
	   "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
	   "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
	   "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
	   "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
	   "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
	);
	$chr = array_keys  ($chr_map); // but: for efficiency you should
	$rpl = array_values($chr_map); // pre-calculate these two arrays
	$string = str_replace($chr, $rpl, html_entity_decode($string, ENT_QUOTES, "UTF-8"));	

	//part 2
	$entities = array(
		"\x80" => "'",    # 128 -> euro sign, U+20AC NEW
		"\x82" => '&sbquo;',   # 130 -> single low-9 quotation mark, U+201A NEW
		"\x83" => '&fnof;',    # 131 -> latin small f with hook = function = florin, U+0192 ISOtech
		"\x84" => '&bdquo;',   # 132 -> double low-9 quotation mark, U+201E NEW
		"\x85" => '&hellip;',  # 133 -> horizontal ellipsis = three dot leader, U+2026 ISOpub
		"\x86" => '&dagger;',  # 134 -> dagger, U+2020 ISOpub
		"\x87" => '&Dagger;',  # 135 -> double dagger, U+2021 ISOpub
		"\x88" => '&circ;',    # 136 -> modifier letter circumflex accent, U+02C6 ISOpub
		"\x89" => '&permil;',  # 137 -> per mille sign, U+2030 ISOtech
		"\x8A" => '&Scaron;',  # 138 -> latin capital letter S with caron, U+0160 ISOlat2
		"\x8B" => '&lsaquo;',  # 139 -> single left-pointing angle quotation mark, U+2039 ISO proposed
		"\x8C" => '&OElig;',   # 140 -> latin capital ligature OE, U+0152 ISOlat2
		"\x8E" => '&#381;',    # 142 -> U+017D
		"\x91" => '&lsquo;',   # 145 -> left single quotation mark, U+2018 ISOnum
		"\x92" => '&rsquo;',   # 146 -> right single quotation mark, U+2019 ISOnum
		"\x93" => '&ldquo;',   # 147 -> left double quotation mark, U+201C ISOnum
		"\x94" => '&rdquo;',   # 148 -> right double quotation mark, U+201D ISOnum
		"\x95" => '&bull;',    # 149 -> bullet = black small circle, U+2022 ISOpub
		"\x96" => '&ndash;',   # 150 -> en dash, U+2013 ISOpub
		"\x97" => '&mdash;',   # 151 -> em dash, U+2014 ISOpub
		"\x98" => '&tilde;',   # 152 -> small tilde, U+02DC ISOdia
		"\x99" => '',   # 153 -> trade mark sign, U+2122 ISOnum
		"\x9A" => '&scaron;',  # 154 -> latin small letter s with caron, U+0161 ISOlat2
		"\x9B" => '&rsaquo;',  # 155 -> single right-pointing angle quotation mark, U+203A ISO proposed
		"\x9C" => '&oelig;',   # 156 -> latin small ligature oe, U+0153 ISOlat2
		"\x9E" => '&#382;',    # 158 -> U+017E
		"\x9F" => '&Yuml;',    # 159 -> latin capital letter Y with diaeresis, U+0178 ISOlat2
	);
	
	foreach($entities as $k => $v){
		$string = str_replace($k, $v, $string);
	}
	
	$string =  trim(mb_convert_encoding($string, 'UTF-8'));
	$string = preg_replace('/[^(\x20-\x7F)]*/','', $string);

	return $string;
}

function gcd($a, $b)
{
  while ( $b != 0)
  {
	 $remainder = $a % $b;
	 $a = $b;
	 $b = $remainder;
  }
  return abs ($a);
}

function getRatio($num1, $num2)
{
	$gcd = gcd($num1,$num2);
	return array($num1/$gcd,$num2/$gcd);
}
	
function currentSite()
{
	$model = new \Core\Model;
	$get = $model->get('sites', $_SERVER['HTTP_HOST'], array(), 'domain');
	if($get){
		$get['apps'] = $model->fetchAll('SELECT a.* FROM site_apps s LEFT JOIN apps a ON a.appId = s.appId WHERE s.siteId = :siteId', array(':siteId' => $get['siteId']));
	}
	return $get;
}

function linkify_username($username)
{
	$slug = genURL($username);
	$model = new \Core\Model;
	$get = $model->get('users', $slug, array('username', 'slug'), 'slug');
	if(!$get){
		return $username;
	}
	$profApp = $model->get('apps', 'profile', array(), 'slug');
	$userModule = $model->get('modules', 'user-profile', array(), 'slug');
	$site = currentSite();
	$url = $site['url'].'/'.$profApp['url'].'/'.$userModule['url'].'/'.$get['slug'];
	return '<a href="'.$url.'" target="_blank">'.$get['username'].'</a>';
}

function dd($var)
{
	debug($var);
	die();
}

/**
 * generates a quick link to an app/module. format: "app_slug.module_slug"
 * 
 * */
function route($route, $path = '')
{
	$full_path = '';
	$site = currentSite();
	$model = new \Core\Model;
	$expRoute = explode('.', $route);
	$getApp = $model->get('apps', $expRoute[0], array(), 'slug');
	if(!$getApp){
		return false;
	}
	$full_path = $getApp['url'];
	if(isset($expRoute[1])){
		$getModule = $model->get('modules', $expRoute[1], array(), 'slug');
		if($getModule){
			$full_path .= '/'.$getModule['url'];
		}
	}
	$full_path .= $path;
	return $site['url'].'/'.$full_path;
}

function app_enabled($slugs, $searchType = 'slug', $fields = false)
{
	if(!is_array($fields)){
		$fields = array('moduleId');
	}
	$exp = explode('.', $slugs);
	$model = new \App\Meta_Model;
	$getApp = $model->get('apps', $exp[0], array(), $searchType);
	if(!$getApp OR $getApp['active'] == 0){
		return false;
	}
	if(isset($exp[1])){
		$getModule = $model->getAll('modules', array('appId' => $getApp['appId'], $searchType => $exp[1], 'active' => 1), $fields);
		if(count($getModule) == 0){
			return false;
		}
		return $getModule[0];
	}
	$getApp['meta'] = $model->appMeta($getApp['appId']);
	return $getApp;
}

function get_app($slugs, $searchType = 'slug')
{
	return app_enabled($slugs, $searchType, array());
}

function app_class($slugs, $type = 'controller', $construct = true)
{
	$exp = explode('.', $slugs);
	$model = new \Core\Model;
	$getApp = $model->get('apps', $exp[0], array(), 'slug');
	if(!$getApp OR $getApp['active'] == 0){
		return false;
	}
	$class_name = '\\App\\'.$getApp['location'];
	if(isset($exp[1])){
		$getModule = $model->getAll('modules', array('appId' => $getApp['appId'], 'slug' => $exp[1], 'active' => 1), array('moduleId','slug','location'));
		if(count($getModule) == 0){
			return false;
		}
		$class_name .= '\\'.$getModule[0]['location'];
		$class_name .= '_'.ucfirst($type);
	}
	else{
		$class_name .= '\\'.ucfirst($type);
	}
	
	if(!$construct){
		return $class_name;
	}
	return new $class_name();
}

function app_setting($slug, $setting)
{
	$model = new \App\Meta_Model;
	$getApp = $model->get('apps', $slug, array(), 'slug');
	if(!$getApp OR $getApp['active'] == 0){
		return false;
	}
	return $model->getAppMeta($getApp['appId'], $setting);
}

function app_path($slugs, $class = '')
{
	$model = new \Core\Model;
	$exp = explode('.', $slugs);
	$field = 'appId';
	if(!is_numeric($exp[0])){
		$field = 'slug';
	}
	$getApp = $model->get('apps', $exp[0], array(), $field);
	if(!$getApp OR $getApp['active'] == 0){
		return false;
	}
	$path = FRAMEWORK_PATH.'/App/'.$getApp['location'];
	if(isset($exp[1])){
		$field = 'moduleId';
		if(!is_numeric($exp[1])){
			$field = 'slug';
		}
		$getModule = $model->get('modules', $exp[1], array(), $field);
		if(!$getModule OR $getModule['active'] == 0){
			return false;
		}
		if($getModule['appId'] == $getApp['appId']){
			$path .= '/'.$getModule['location'];
		}
	}
	if($class == ''){
		return $path;
	}
	$path .= '/'.ucfirst($class).'.php';
	return $path;
}


function botdetect()
{
  if(isset($_SERVER['HTTP_USER_AGENT']) AND preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])){
    return true;
  }
  return false;
}

function is_match($pattern, $value) {
    if($pattern == $value){
		return true;
	}
    $pattern = preg_quote($pattern, '#');
    $pattern = str_replace('\*', '.*', $pattern).'\z';
    return (bool)preg_match('#^'.$pattern.'#', $value);
}

function has_key($var, $key)
{
	if(!is_array($var) OR !isset($var[$key]) OR $var[$key] === false OR trim($var[$key]) == ''){
		return false;
	}
	return true;
}

function parse_fileComments($path)
{
	$contents = @file_get_contents($path);
	$start = strpos($contents, '/*') + 2;
	$end = strpos($contents, '*/');
	$substr = substr($contents, $start, ($end - $start));
	$exp = explode("\n", $substr);
	$lines = array();
	foreach($exp as $line){
		if(trim(str_replace('*', '', $line)) == ''){
			continue;
		}
		$expstar = explode('*', $line);
		if(isset($expstar[1])){
			$lines[] = trim($expstar[1]);
		}
		else{
			$lines[] = trim($expstar[0]);
		}
	}
	$info = array();
	foreach($lines as $line){
		$expat = explode('@', $line);
		if(isset($expat[1])){
			$expeq = explode('=', $expat[1]);
			$expval = null;
			if(isset($expeq[1])){
				$expval = trim($expeq[1]);
			}
			$info[trim($expeq[0])] = $expval;
		}
	}
	return $info;
}

function user()
{
	return \App\Account\Home_Model::userInfo();
}

function extract_signature($text, $start = '-----BEGIN BITCOIN SIGNATURE-----', $end = '-----END BITCOIN SIGNATURE-----')
{
	$inputMessage = trim($text);
	if(strpos($inputMessage, $start) !== false){
		//pgp style signed message format, extract the actual signature from it
		$expMsg = explode("\n", $inputMessage);
		foreach($expMsg as $k => $line){
			if($line == $end){
				if(isset($expMsg[$k-1])){
					$inputMessage = trim($expMsg[$k-1]);
				}
			}
		}
	}
	return $inputMessage;
}

function redirect($url)
{
	header('Location: '.$url);
	die();
}

?>
