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
 * fist of all include framework to make it available (for interoperability)
 **/

// Include WB functions file
require_once WB_PATH.'/framework/functions.php';

// ========== Tags ==========

/**
 * get existing tags for current section
 * @param  int $section_id
 * @return array
 **/
function mod_nwi_get_tags($section_id=null,$alltags=false) {
    global $database;
    $tags = array();
    $where = "WHERE `section_id`=0";
    if(!empty($section_id)) {
        $section_id = intval($section_id);
        $where .= " OR `section_id` = '$section_id'";
    }
    if($alltags===true) {
        $where = null;
    }
    $query_tags = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_sections` AS t2 " .
        "ON t1.tag_id=t2.tag_id ".
        $where, TABLE_PREFIX, TABLE_PREFIX
    ));
    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while(null!==($t = $query_tags->fetchRow())) {
            $tags[$t['tag_id']] = $t;
        }
    }
    return $tags;
}

/**
 *
 * @access 
 * @return
 **/
function mod_nwi_get_tags_for_post($post_id)
{
    global $database;
    $tags = array();
        $query_tags = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_posts` AS t2 " .
        "ON t1.`tag_id`=t2.`tag_id` ".
        "WHERE t2.`post_id`=$post_id",
        TABLE_PREFIX, TABLE_PREFIX
    ));

    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while(null!==($t = $query_tags->fetchRow())) {
            $tags[$t['tag_id']] = $t['tag'];
        }
    }

    return $tags;
}   // end function mod_nwi_get_tags_for_post()


/**
 *
 * @access public
 * @return
 **/
function mod_nwi_tag_exists($section_id,$tag)
{
    global $database;
    $sql   = sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_sections` AS t2 " .
        "ON `t1`.`tag_id`=`t2`.`tag_id` " .
        "WHERE `tag`='%s' ".
        "AND (`t2`.`section_id`=$section_id OR `t2`.`section_id`=0)",
        TABLE_PREFIX, TABLE_PREFIX, $tag
    );
    $query = $database->query($sql);
    if (!empty($query) && $query->numRows() > 0) {
        return true;
    }
    return false;
}   // end function mod_nwi_tag_exists()

// ========== Images ==========

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

function mod_nwi_img_get($pic_id)
{
    global $database;
    $query_img = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_img` WHERE `id` = %d",
        TABLE_PREFIX,intval($pic_id)
    ));
    if ($query_img->numRows() > 0) {
        return $query_img->fetchRow();
    }
    return array();
}

function mod_nwi_img_get_by_post($post_id=null)
{
    global $database;
    $where = null;
    if(!(empty($post_id))) {
        $where = "WHERE `post_id`=$post_id";
    }
    $query_img = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_img` AS t1 ".
        "LEFT JOIN `%smod_news_img_posts_img` AS t2 ".
        "ON t1.`id`=t2.`pic_id` ".
        "%s ORDER BY `position`,`id` ASC",
        TABLE_PREFIX,TABLE_PREFIX,$where
    ));

    $images = array();
    if ($query_img->numRows() > 0) {
        while ($row = $query_img->fetchRow()) {
            $images[] = $row;
        }
    }
    return $images;
}

function mod_nwi_img_get_by_section($section_id)
{
    global $database;
    $query_img = $database->query(sprintf(
        "SELECT t1.* FROM `%smod_news_img_img` AS t1 " .
        "JOIN `%smod_news_img_posts_img` AS t2 " .       // img to post
        "ON `t1`.`id`=`t2`.`pic_id` ".
        "JOIN `%smod_news_img_posts` AS t3 ".            // post to section
        "ON t2.`post_id`=t3.`post_id` ".
        "WHERE `t3`.`section_id`=%d GROUP BY `picname`",
        TABLE_PREFIX,TABLE_PREFIX,TABLE_PREFIX,$section_id
    ));
    $images = array();
    if ($query_img->numRows() > 0) {
        while ($row = $query_img->fetchRow()) {
            $images[] = $row;
        }
    }
    return $images;
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
