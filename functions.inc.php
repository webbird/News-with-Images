<?php

require_once __DIR__.'/../../config.php';

if(file_exists(__DIR__.'/config.php')){
    include __DIR__.'/config.php';
}

// load module language file
$lang = (dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php';
require_once(!file_exists($lang) ? (dirname(__FILE__)) . '/languages/EN.php' : $lang);

$mod_nwi_file_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/';
$mod_nwi_thumb_dir = WB_PATH.MEDIA_DIRECTORY.'/.news_img/thumb/';

// Include WB functions file
require_once WB_PATH.'/framework/functions.php';

// ========== Groups ==========

/**
 *
 * @access 
 * @return
 **/
function mod_nwi_get_group(int $group_id)
{
    global $database;
    $query_content = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_groups` WHERE `group_id`=%d",
        TABLE_PREFIX,$group_id
    ));
    return $query_content->fetchRow();
}   // end function mod_nwi_get_group()


// ========== Tags ==========

/**
 * get existing tags for current section
 * @param  int   $section_id
 * @param  bool  $alltags
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
        while(false!==($t = $query_tags->fetchRow())) {
            $tags[$t['tag_id']] = $t;
        }
    }
    return $tags;
}   // end function mod_nwi_get_tags()

/**
 * get tags for given post
 * @param  int   $post_id
 * @return array
 **/
function mod_nwi_get_tags_for_post($post_id)
{
    global $database;
    $tags = array();
    $query_tags = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_posts` AS t2 " .
        "ON t1.`tag_id`=t2.`tag_id` ".
        "WHERE t2.`post_id`=%d",
        TABLE_PREFIX, TABLE_PREFIX, $post_id
    ));

    if (!empty($query_tags) && $query_tags->numRows() > 0) {
        while(false!==($t = $query_tags->fetchRow())) {
            $tags[$t['tag_id']] = $t['tag'];
        }
    }

    return $tags;
}   // end function mod_nwi_get_tags_for_post()


/**
 * check if tag is valid for given section
 * @param  int    $section_id
 * @param  string $tag
 * @return bool
 **/
function mod_nwi_tag_exists(int $section_id, string $tag)
{
    global $database;
    $sql   = sprintf(
        "SELECT * FROM `%smod_news_img_tags` AS t1 " .
        "JOIN `%smod_news_img_tags_sections` AS t2 " .
        "ON `t1`.`tag_id`=`t2`.`tag_id` " .
        "WHERE `tag`='%s' ".
        "AND (`t2`.`section_id`=%d OR `t2`.`section_id`=0)",
        TABLE_PREFIX, TABLE_PREFIX, $tag, $section_id
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
    if (!empty($query_img) && $query_img->numRows() > 0) {
        while (false!==($row = $query_img->fetchRow())) {
            $images[] = $row;
        }
    }
    return $images;
}

function mod_nwi_img_get_by_section($section_id)
{
    global $database;
    // all gallery images
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
    if (!empty($query_img) && $query_img->numRows() > 0) {
        while (false!==($row = $query_img->fetchRow())) {
            $images[] = $row;
        }
    }
/*

(
    [0] => Array
        (
            [0] => 60
            [id] => 60
            [1] => 2015-11-10_10_49_19_2.png
            [picname] => 2015-11-10_10_49_19_2.png
            [2] =>
            [picdesc] =>
        )
    [1] => Array
        (
            [0] => 12508803_1695055897443354_5488451947572868016_n.jpg
            [image] => 12508803_1695055897443354_5488451947572868016_n.jpg
        )
)
*/

    // all (other) preview images
    $query_preview = $database->query(sprintf(
        "SELECT `image` FROM `%smod_news_img_posts` " .
        "WHERE `image`<>'' AND `section_id`=%d",
        TABLE_PREFIX, $section_id
    ));
    if (!empty($query_preview) && $query_preview->numRows() > 0) {
        while (false!==($row = $query_preview->fetchRow())) {
            $images[] = array(
                'id' => 0,
                'picname' => $row['image'],
                'picdesc' => null
            );
        }
    }
echo "FILE [",__FILE__,"] FUNC [",__FUNCTION__,"] LINE [",__LINE__,"]<br /><textarea style=\"width:100%;height:200px;color:#000;background-color:#fff;\">";
print_r($images);
echo "</textarea><br />";
    return $images;
}

function mod_nwi_img_upload($post_id,$is_preview_image=false)
{
    global $database, $mod_nwi_file_dir;

    // upload.php = 'file'
    // modify_post.php (preview image) = 'postfoto'
    $key = 'file';
    if($is_preview_image) {
        $key = 'postfoto';
    } else {
        $mod_nwi_file_dir .= "$post_id/";
    }
    $mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";
    $imageErrorMessage = null;

    // get section id
    $post_data = mod_nwi_post_get($post_id);
    $section_id = intval($post_data['section_id']);

    // get settings
    $settings = mod_nwi_settings_get($section_id);

    $settings['imgmaxsize'] = intval($settings['imgmaxsize']);
    $iniset = ini_get('upload_max_filesize');
    $iniset = mod_nwi_return_bytes($iniset);

    // preview images size
    $previewwidth = $previewheight = $thumbwidth = $thumbheight = '';
    if (substr_count($settings['resize_preview'], 'x')>0) {
        list($previewwidth, $previewheight) = explode('x', $settings['resize_preview'], 2);
    }
    if (substr_count($settings['imgthumbsize'], 'x')>0) {
        list($thumbwidth, $thumbheight) = explode('x', $settings['imgthumbsize'], 2);
    }

    // gallery images size
    $imagemaxsize  = ($settings['imgmaxsize']>0 && $settings['imgmaxsize'] < $iniset)
        ? $settings['imgmaxsize']
        : $iniset;

    $imagemaxwidth  = $settings['imgmaxwidth'];
    $imagemaxheight = $settings['imgmaxheight'];
    $crop           = ($settings['crop_preview'] == 'Y') ? 1 : 0;

    // make sure the folder exists
    if(!is_dir($mod_nwi_file_dir)) {
        mod_nwi_img_makedir($mod_nwi_file_dir);
    }

    // handle upload
    if(isset($_FILES[$key]) && is_array($_FILES[$key]))
    {
        $picture = $_FILES[$key];
        if (isset($picture['name']) && $picture['name'] && (strlen($picture['name']) > 3))
        {
            $pic_error = '';
            // change special characters
            $imagename = media_filename($picture['name']);
            // all lowercase
            $imagename = strtolower($imagename) ;
            // if file exists, find new name by adding a number
            if (file_exists($mod_nwi_file_dir.$imagename)) {
                $num = 1;
                $f_name = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_FILENAME);
                $suffix = pathinfo($mod_nwi_file_dir.$imagename, PATHINFO_EXTENSION);
                while (file_exists($mod_nwi_file_dir.$f_name.'_'.$num.'.'.$suffix)) {
                    $num++;
                }
                $imagename = $f_name.'_'.$num.'.'.$suffix;
            }
            $filepath = $mod_nwi_file_dir.$imagename;
            // check size limit
            if (empty($picture['size']) || $picture['size'] > $imagemaxsize) {
                $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_LARGER_THAN'].mod_nwi_byte_convert($imagemaxsize).'<br />';
            } elseif (strlen($imagename) > '256') {
                $imageErrorMessage .= $MOD_NEWS_IMG['IMAGE_FILENAME_ERROR'].'<br />';
            } else {
                // move to media folder
                if (true===move_uploaded_file($picture['tmp_name'], $filepath)) {
                    // preview images have a different size (smaller in most cases)
                    if($is_preview_image) {
                        $imagemaxwidth  = $previewwidth;
                        $imagemaxheight = $previewheight;
                    }
                    // resize image (if larger than max width and height)
                    if (list($w, $h) = getimagesize($mod_nwi_file_dir.$imagename)) {
                        if ($w>$imagemaxwidth || $h>$imagemaxheight) {
                            if (true !== ($pic_error = @mod_nwi_image_resize($mod_nwi_file_dir.$imagename, $mod_nwi_file_dir.$imagename, $imagemaxwidth, $imagemaxheight, $crop))) {
                                $imageErrorMessage .= $pic_error.'<br />';
                                @unlink($mod_nwi_file_dir.$imagename); // delete image (cleanup)
                            }
                        }
                    }
                    if($is_preview_image) {
                        $database->query(sprintf(
                            "UPDATE `%smod_news_img_posts` SET `image`='%s' " .
                            "WHERE `post_id`=%d",
                            TABLE_PREFIX, $imagename, $post_id
                        ));
                        if($database->is_error()) {
                            $imageErrorMessage .= $database->get_error()."<br />";
                        }
                    } else {
                        // create thumb
                        if (true !== ($pic_error = @mod_nwi_image_resize($mod_nwi_file_dir.$imagename, $mod_nwi_thumb_dir.$imagename, $thumbwidth, $thumbheight, $crop))) {
                            $imageErrorMessage .= $pic_error.'<br />';
                            @unlink($mod_nwi_file_dir.$imagename); // delete image (cleanup)
                        } else {
                            $pic_id = null;
                            // insert image into image table
                            $database->query(sprintf(
                                "INSERT INTO `%smod_news_img_img` " .
                                "(`picname`) " .
                                "VALUES ('%s')",
                                TABLE_PREFIX,$imagename
                            ));
                            if($database->is_error()) {
                                $imageErrorMessage .= $database->get_error()."<br />";
                            } else {
                                $pic_id = $database->getLastInsertId();
                            }
                            // image position
                            $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
                            $position = $order->get_new($post_id);
                            // connect with current post
                            $database->query(sprintf(
                                "INSERT INTO `%smod_news_img_posts_img` " .
                                "(`post_id`,`pic_id`,`position`) " .
                                "VALUES ('%s',%d,%d)",
                                TABLE_PREFIX,$post_id,$pic_id,$position
                            ));
                            if($database->is_error()) {
                                $imageErrorMessage .= $database->get_error()."<br />";
                            }
                        }
                    }
                } else {
                    $imageErrorMessage .= "Unable to move uploaded image ".$picture['tmp_name']." to ".$mod_nwi_file_dir.$imagename."<br />";
                }
            }
        }
    }
    return $imageErrorMessage;
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

function mod_nwi_post_get($post_id)
{
    global $database;
    $query_content = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_posts` WHERE `post_id`=%d",
        TABLE_PREFIX, $post_id
    ));
    if(!empty($query_content)) {
        return $query_content->fetchRow();
    }
    return array();
}

/**
 *
 * @access 
 * @return
 **/
 function mod_nwi_settings_get($section_id)
{
    global $database;
    $query_content = $database->query(sprintf(
        "SELECT * FROM `%smod_news_img_settings` WHERE `section_id`=%d",
        TABLE_PREFIX,
        $section_id
    ));
    if(!empty($query_content)) {
        return $query_content->fetchRow();
    }
    return array();
}   // end function mod_nwi_settings_get()

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
