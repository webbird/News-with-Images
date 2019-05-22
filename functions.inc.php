<?php

require_once __DIR__.'/../../config.php';

if(file_exists(__DIR__.'/config.php')){
    include __DIR__.'/config.php';
}

if(!defined('NWI_USE_SECOND_BLOCK')){
   define('NWI_USE_SECOND_BLOCK',true);
}

// load module language file
$lang = (dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php';
require_once(!file_exists($lang) ? (dirname(__FILE__)) . '/languages/EN.php' : $lang);

$mod_nwi_file_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/';
$mod_nwi_thumb_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/thumb/';

/**
 * fist of all include some fallbacks for framework functions for interoperability
 **/


if(!function_exists('page_filename')){
function page_filename($sStr)
{
    require_once WB_PATH . '/framework/functions-utf8.php';
    $sStr = entities_to_7bit($sStr);
    // Now remove all bad characters
    $aBadChars = array(
        '\'', 
        '"', 
        '`', 
        '!', 
        '@', 
        '#', 
        '$', 
        '%', 
        '^', 
        '&', 
        '*', 
        '=', 
        '+', 
        '|', 
        '/', 
        '\\', 
        ';', 
        ':', 
        ',', 
        '?', 
        '[', 
        ']', 
        '<', 
        '>', 
        '{', // in page_filename only
        '}', // in page_filename only
        '(', // in page_filename only
        ')', // in page_filename only
        
    //   '~', // in media only
    //   '<', // in media only
    //   '>'  // in media only
    );
    $sStr = str_replace($aBadChars, '', $sStr);
    // replace multiple dots in filename to single dot and (multiple) dots at the end of the filename to nothing
    $sStr = preg_replace(array('/\.+/', '/\.+$/'), array('.', ''), $sStr);
    // Now replace spaces with page spcacer
    $sStr = trim($sStr);
    $sStr = preg_replace('/(\s)+/', PAGE_SPACER, $sStr);
    // Now convert to lower-case
    $sStr = strtolower($sStr);
    // If there are any weird language characters, this will protect us against possible problems they could cause
    $sStr = str_replace(array('%2F', '%'), array('/', ''), urlencode($sStr));
    // Finally, return the cleaned string
    return $sStr;
}
}


if(!function_exists('make_dir')){
/**
 * @brief   Function to create directories
 * 
 * @param   string  $dir_name
 * @param   string  $dir_mode
 * @param   bool    $recursive
 * @return  bool
 */
function make_dir($dir_name, $dir_mode = OCTAL_DIR_MODE, $recursive = true)
{
    $retVal = false;
    if (!is_dir($dir_name)) {
        $umask = umask(0);
        $retVal = mkdir($dir_name, $dir_mode, $recursive);
        umask($umask);
    }
    return $retVal;
}
}


if(!function_exists('change_mode')){

/**
 * @brief   Function to chmod files and directories
 * 
 * @param   string  $name
 * @return  bool
 */
function change_mode($name)
{
    if (OPERATING_SYSTEM != 'windows') {
        // Only chmod if os is not windows
        if (is_dir($name)) {
            $mode = OCTAL_DIR_MODE;
        } else {
            $mode = OCTAL_FILE_MODE;
        }
        if (file_exists($name)) {
            $umask = umask(0);
            chmod($name, $mode);
            umask($umask);
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}
}

if(!function_exists('rm_full_dir')){

/**
 * @brief   recursively remove a non empty directory and all its contents
 * 
 * @param   string $sDirPath  Full path to the directory
 * @param   bool   $empty     true if you want the folder just emptied, but not deleted
 *                            false, or just simply leave it out, the given directory 
 *                            will be deleted, as well
 * @return  bool list of ro-dirs
 * @from    http://www.php.net/manual/de/function.rmdir.php#98499
 */
function rm_full_dir($sDirPath, $empty = false)
{

    if (substr($sDirPath, -1) == "/") {
        $sDirPath = substr($sDirPath, 0, -1);
    }
    // If suplied dirname is a file then unlink it
    if (is_file($sDirPath)) {
        $retval = unlink($sDirPath);
        clearstatcache();
        return $retval;
    }
    if (!file_exists($sDirPath) || !is_dir($sDirPath)) {
        return false;
    } elseif (!is_readable($sDirPath)) {
        return false;
    } else {
        $directoryHandle = opendir($sDirPath);
        while ($contents = readdir($directoryHandle)) {
            if ($contents != '.' && $contents != '..') {
                $path = $sDirPath . "/" . $contents;
                if (is_dir($path)) {
                    rm_full_dir($path);
                } else {
                    unlink($path);
                    clearstatcache();
                }
            }
        }
        closedir($directoryHandle);
        if ($empty == false) {
            if (!rmdir($sDirPath)) {
                return false;
            }
        }
        return true;
    }
}
}

/**
 * now our own functions for the module
 **/

function mod_nwi_img_copy($source, $dest){
    if(is_dir($source)) {
        $dir_handle=opendir($source);
        while($file=readdir($dir_handle)){
            if($file!="." && $file!=".."){
                if(is_dir($source."/".$file)){
                    if(!is_dir($dest."/".$file)){
                        mkdir($dest."/".$file);
                    }
                    mod_nwi_img_copy($source."/".$file, $dest."/".$file);
                } else {
                    copy($source."/".$file, $dest."/".$file);
                }
            }
        }
        closedir($dir_handle);
    } else {
        if(file_exists($source))
            copy($source, $dest);
    }
}

function mod_nwi_img_makedir($dir, $with_thumb=true)
{
    if (make_dir($dir)) {
        // Add a index.php file to prevent directory spoofing
        $content = ''.
"<?php

/**
*
* @category        modules
* @package         news_img
* @author          WBCE Community
* @copyright       2004-2009, Ryan Djurovich
* @copyright       2009-2010, Website Baker Org. e.V.
* @copyright       2019-, WBCE Community
* @link            https://www.wbce.org/
* @license         http://www.gnu.org/licenses/gpl.html
* @platform        WBCE
*
*/

header('Location: ../');
?>";
        $handle = fopen($dir.'/index.php', 'w');
        fwrite($handle, $content);
        fclose($handle);
        change_mode($dir.'/index.php', 'file');
    }
    if ($with_thumb) {
        $dir .= '/thumb';
        if (make_dir($dir)) {
            // Add a index.php file to prevent directory spoofing
            $content = ''.
"<?php

/**
*
* @category        modules
* @package         news_img
* @author          WBCE Community
* @copyright       2004-2009, Ryan Djurovich
* @copyright       2009-2010, Website Baker Org. e.V.
* @copyright       2019-, WBCE Community
* @link            https://www.wbce.org/
* @license         http://www.gnu.org/licenses/gpl.html
* @platform        WBCE
*
*/

header('Location: ../');
?>";
            $handle = fopen($dir.'/index.php', 'w');
            fwrite($handle, $content);
            fclose($handle);
            change_mode($dir.'/index.php', 'file');
        }
    }
}

function mod_nwi_byte_convert($bytes)
{
    $symbol = array(' bytes', ' KB', ' MB', ' GB', ' TB');
    $exp = 0;
    $converted_value = 0;
    if ($bytes > 0) {
        $exp = floor(log($bytes) / log(1024));
        $converted_value = ($bytes / pow(1024, floor($exp)));
    }
    return sprintf('%.2f '.$symbol[$exp], $converted_value);
}   // end function mod_nwi_byte_convert()

function mod_nwi_return_bytes($val)
{
    $val  = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val  = intval($val);
    switch ($last) {
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }

    return $val;
}

function mod_nwi_create_file($filename, $filetime=null)
{
    global $page_id, $section_id, $post_id;

    // We need to create a new file
    // First, delete old file if it exists
    if (file_exists(WB_PATH.PAGES_DIRECTORY.$filename.PAGE_EXTENSION)) {
        $filetime = isset($filetime) ? $filetime :  filemtime($filename);
        unlink(WB_PATH.PAGES_DIRECTORY.$filename.PAGE_EXTENSION);
    } else {
        $filetime = isset($filetime) ? $filetime : time();
    }
    // The depth of the page directory in the directory hierarchy
    // '/pages' is at depth 1
    $pages_dir_depth = count(explode('/', PAGES_DIRECTORY))-1;
    // Work-out how many ../'s we need to get to the index page
    $index_location = '../';
    for ($i = 0; $i < $pages_dir_depth; $i++) {
        $index_location .= '../';
    }

    // Write to the filename
    $content = ''.
'<?php
$page_id = '.$page_id.';
$section_id = '.$section_id.';
$post_id = '.$post_id.';

define("POST_SECTION", $section_id);
define("POST_ID", $post_id);
require("'.$index_location.'config.php");
require(WB_PATH."/index.php");
?>';
    if ($handle = fopen($filename, 'w+')) {
        fwrite($handle, $content);
        fclose($handle);
        if ($filetime) {
            touch($filename, $filetime);
        }
        change_mode($filename);
    }
}

/**
 * resize image
 *
 * return values:
 *    true - ok
 *    1    - image is smaller than new size
 *    2    - invalid type (unable to handle)
 *
 * @param $src    - image source
 * @param $dst    - save to
 * @param $width  - new width
 * @param $height - new height
 * @param $crop   - 0=no, 1=yes
 **/
function mod_nwi_image_resize($src, $dst, $width, $height, $crop=0)
{
    //var_dump($src);
    if (!list($w, $h) = getimagesize($src)) {
        return 2;
    }

    $type = strtolower(substr(strrchr($src, "."), 1));
    if ($type == 'jpeg') {
        $type = 'jpg';
    }
    switch ($type) {
        case 'bmp': $img = imagecreatefromwbmp($src); break;
        case 'gif': $img = imagecreatefromgif($src); break;
        case 'jpg': $img = imagecreatefromjpeg($src); break;
        case 'png': $img = imagecreatefrompng($src); break;
        default: return 2;
    }

    // resize
    if ($crop) {
        if ($w < $width or $h < $height) {
            return 1;
        }
        $ratio = max($width/$w, $height/$h);
        $h = $height / $ratio;
        $x = ($w - $width / $ratio) / 2;
        $w = $width / $ratio;
    } else {
        if ($w < $width and $h < $height) {
            return 1;
        }
        $ratio = min($width/$w, $height/$h);
        $width = $w * $ratio;
        $height = $h * $ratio;
        $x = 0;
    }

    $new = imagecreatetruecolor($width, $height);

    // preserve transparency
    if ($type == "gif" or $type == "png") {
        imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
        imagealphablending($new, false);
        imagesavealpha($new, true);
    }

    imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

    switch ($type) {
        case 'bmp': imagewbmp($new, $dst); break;
        case 'gif': imagegif($new, $dst); break;
        case 'jpg': imagejpeg($new, $dst); break;
        case 'png': imagepng($new, $dst); break;
    }
    return true;
}
