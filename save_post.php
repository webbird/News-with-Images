<?php
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

require_once '../../config.php';

require_once WB_PATH."/include/jscalendar/jscalendar-functions.php";

#var_dump($_POST);
#var_dump($_FILES);

//change thumbnail size here!
$thumbsize = 220;

// 2014-04-10 by BlackBird Webprogrammierung:
//            resize images on upload
$imagemaxwidth  = 900;     // max width in pixel
$imagemaxheight = 900;     // max height in pixel
$imagemaxsize   = 4096000; // max file size in byte

$file_dir = WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/';
$thumb_dir = WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/thumb/';

// Get id
if(!isset($_POST['post_id']) OR !is_numeric($_POST['post_id']))
{
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit( 0 );
}
else
{
	$id = $_POST['post_id'];
	$post_id = $id;
}

function byte_convert($bytes)
{
	$symbol = array(' bytes', ' KB', ' MB', ' GB', ' TB');
	$exp = 0;
	$converted_value = 0;
	if ($bytes > 0)
	{
		$exp = floor( log($bytes) / log(1024));
		$converted_value = ($bytes / pow( 1024, floor($exp)));
	}
	return sprintf('%.2f '.$symbol[$exp], $converted_value);
}   // end function byte_convert()

function create_file($filename, $filetime=NULL )
{
    global $page_id, $section_id, $post_id;

	// We need to create a new file
	// First, delete old file if it exists
	if(file_exists(WB_PATH.PAGES_DIRECTORY.$filename.PAGE_EXTENSION))
    {
        $filetime = isset($filetime) ? $filetime :  filemtime($filename);
		unlink(WB_PATH.PAGES_DIRECTORY.$filename.PAGE_EXTENSION);
	}
    else {
        $filetime = isset($filetime) ? $filetime : time();
    }
	// The depth of the page directory in the directory hierarchy
	// '/pages' is at depth 1
	$pages_dir_depth = count(explode('/',PAGES_DIRECTORY))-1;
	// Work-out how many ../'s we need to get to the index page
	$index_location = '../';
	for($i = 0; $i < $pages_dir_depth; $i++)
    {
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
	if($handle = fopen($filename, 'w+'))
    {
    	fwrite($handle, $content);
    	fclose($handle);
        if($filetime)
        {
            touch($filename, $filetime);
        }
    	change_mode($filename);
    }

}

//Image resize
function image_resize($src, $dst, $width, $height, $crop=0){
  //var_dump($src);
  if(!list($w, $h) = getimagesize($src)) return "Nicht unterst&uuml;tzter Bildtyp!";

  $type = strtolower(substr(strrchr($src,"."),1));
  if($type == 'jpeg') $type = 'jpg';
  switch($type){
    case 'bmp': $img = imagecreatefromwbmp($src); break;
    case 'gif': $img = imagecreatefromgif($src); break;
    case 'jpg': $img = imagecreatefromjpeg($src); break;
    case 'png': $img = imagecreatefrompng($src); break;
    default : return "Nicht unterst&uuml;tzter Bildtyp!";
  }

  // resize
  if($crop){
    if($w < $width or $h < $height) return "Bild ist zu klein!";
    $ratio = max($width/$w, $height/$h);
    $h = $height / $ratio;
    $x = ($w - $width / $ratio) / 2;
    $w = $width / $ratio;
  }
  else{
    if($w < $width and $h < $height) return "Bild ist zu klein!";
    $ratio = min($width/$w, $height/$h);
    $width = $w * $ratio;
    $height = $h * $ratio;
    $x = 0;
  }

  $new = imagecreatetruecolor($width, $height);

  // preserve transparency
  if($type == "gif" or $type == "png"){
    imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
    imagealphablending($new, false);
    imagesavealpha($new, true);
  }

  imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

  switch($type){
    case 'bmp': imagewbmp($new, $dst); break;
    case 'gif': imagegif($new, $dst); break;
    case 'jpg': imagejpeg($new, $dst); break;
    case 'png': imagepng($new, $dst); break;
  }
  return true;
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require WB_PATH.'/modules/admin.php';

// Validate all fields
if($admin->get_post('title') == '' AND $admin->get_post('url') == '')
{
	$admin->print_error($MESSAGE['GENERIC']['FILL_IN_ALL'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
}
else
{
	$title = $admin->get_post_escaped('title');
	$short = $admin->get_post_escaped('short');
	$long = $admin->get_post_escaped('long');
	$image = $admin->get_post_escaped('image');
	$commenting = $admin->get_post_escaped('commenting');
	$active = $admin->get_post_escaped('active');
	$old_link = $admin->get_post_escaped('link');
	$group_id = $admin->get_post_escaped('group');
}

// Get page link URL
$query_page = $database->query("SELECT `level`,`link` FROM `".TABLE_PREFIX."pages` WHERE `page_id` = '$page_id'");
$page = $query_page->fetchRow();
$page_level = $page['level'];
$page_link = $page['link'];

// Include WB functions file
require_once(WB_PATH.'/framework/functions.php');

// Work-out what the link should be
$post_link = '/posts/'.page_filename($title).PAGE_SPACER.$post_id;

// Make sure the post link is set and exists
// Make news post access files dir
make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');
$file_create_time = '';
if(!is_writable(WB_PATH.PAGES_DIRECTORY.'/posts/'))
{
	$admin->print_error($MESSAGE['PAGES']['CANNOT_CREATE_ACCESS_FILE']);
}
elseif(($old_link != $post_link) OR !file_exists(WB_PATH.PAGES_DIRECTORY.$post_link.PAGE_EXTENSION))
{
	// We need to create a new file
	// First, delete old file if it exists
	if(file_exists(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION))
    {
        $file_create_time = filemtime(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION);
		unlink(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION);
	}

    // Specify the filename
    $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
    create_file($filename, $file_create_time);
}


// get publisedwhen and publisheduntil
$publishedwhen = jscalendar_to_timestamp($admin->get_post_escaped('publishdate'));
if($publishedwhen == '' || $publishedwhen < 1)
	$publishedwhen=0;
$publisheduntil = jscalendar_to_timestamp($admin->get_post_escaped('enddate'), $publishedwhen);
if($publisheduntil == '' || $publisheduntil < 1)
	$publisheduntil=0;


//post images
if (isset($_FILES["foto"])) {
    // 2014-04-10 by BlackBird Webprogrammierung:
    //            image position (order)
    if(!defined('ORDERING_CLASS_LOADED')) {
        require WB_PATH.'/framework/class.order.php';
    }
    
    foreach($_FILES as $picture) {
        if(!isset($picture['name']) || !is_array($picture['name'])) {
            continue;
        }
        for ($i=0; $i<sizeof($picture['name']); $i++)
        //wenn nur vorschaubild hochgeladen wird und alle galeriefotos leer sind.....    
        if(isset($picture['name'][$i]) && $picture['name'][$i] && (strlen($picture['name'][$i]) > 3)) {
            //change special characters
            $bildname = strtr($picture['name'][$i], " �����������", "_aouaouaeecs") ;
            //small characters
            $bildname = strtolower("$bildname") ;

            // 2014-04-10 by BlackBird Webprogrammierung:
            //            if file exists, find new name by adding a number
            if (file_exists($file_dir.$bildname)) {
              $num = 1;
              $f_name = pathinfo($file_dir.$bildname,PATHINFO_FILENAME);
              $suffix = pathinfo($file_dir.$bildname,PATHINFO_EXTENSION);
              while(file_exists($file_dir.$f_name.'_'.$num.'.'.$suffix)) {
                $num++;
              }
              $bildname = $f_name.'_'.$num.'.'.$suffix;
            }

            // check
            if  ( $picture['size'][$i] > $imagemaxsize) {
              $error = 'Datei ist zu gro�. Sie darf max. '.byte_convert($imagemaxsize).' sein!' ;
            }
            elseif ( strlen($bildname) > '256') {
              $error = 'Der Dateiname darf 256 Zeichen nicht &uuml;berschreiten!' ;
            }
            else {
              // copy in folder
              move_uploaded_file($picture['tmp_name'][$i], $file_dir.$bildname);

              // 2014-04-10 by BlackBird Webprogrammierung:
              //            resize image
              if(list($w, $h) = getimagesize($file_dir.$bildname))
                  if($w>$imagemaxwidth || $h>$imagemaxheight)
                      //make_thumb_custom($file_dir.$bildname, $file_dir.$bildname, $imagemaxlength);
                      image_resize($file_dir.$bildname, $file_dir.$bildname, $imagemaxwidth, $imagemaxheight);

              //resize (create thumb)
              if (true !== ($pic_error = @image_resize($file_dir.$bildname, $thumb_dir.'thumb_'.$bildname, $thumbsize, $thumbsize, 1))) {
                echo $pic_error;
                unlink($bildname);
              }
              else {

                // 2014-04-10 by BlackBird Webprogrammierung:
                //            image position
                $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
                $position = $order->get_new($post_id);
         
                // DB insert
                $database->query("INSERT INTO ".TABLE_PREFIX."mod_news_img_img (bildname, post_id, position) VALUES ('".$bildname."', ".$post_id.", ".$position.')');
                // Say that a new record has been added, then redirect to modify page
               /* if($database->is_error()) {
                        $admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id);
                } else {
                        $admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id);
                }    */
              }
            }    
        }
    }
}

//vorschaubild
if (isset($_FILES["postfoto"]) && $_FILES["postfoto"]["name"] != "") {

    foreach($_FILES as $postpicture) {
        if($postpicture['name'] && !is_array($postpicture['name'])) {
            //change special characters
            $postbildname = strtr($postpicture['name'], " �����������", "_aouaouaeecs") ;
            //small characters
            $postbildname = strtolower("$postbildname") ;

            // 2014-04-10 by BlackBird Webprogrammierung:
            //            if file exists, find new name by adding a number
            if (file_exists($file_dir.'post_'.$post_id.'_'.$postbildname)) {
              $num = 1;
              $f_name = pathinfo($postbildname,PATHINFO_FILENAME);
              $suffix = pathinfo($postbildname,PATHINFO_EXTENSION);
              while(file_exists($file_dir.'post_'.$post_id.'_'.$f_name.'_'.$num.'.'.$suffix)) {
                $num++;
              }
              $postbildname = $f_name.'_'.$num.'.'.$suffix;
            }

            // check
            if  ( $postpicture['size'] > '2048000') {
              $error = 'Datei ist zu gro�. Sie darf max. 2 MB sein!' ;
            }
            elseif ( strlen($postbildname) > '256') {
              $error = 'Der Dateiname darf 256 Zeichen nicht &uuml;berschreiten!' ;
            }
            else {
              // copy in folder
              move_uploaded_file($postpicture['tmp_name'], $thumb_dir.$postbildname);
              //resize
              $query_content = $database->query("SELECT `resize_preview`, `crop_preview` FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
              $fetch_content = $query_content->fetchRow();
              if(substr_count($fetch_content['resize_preview'],'x')>0) {
                  list($previewwidth,$previewheight) = explode('x',$fetch_content['resize_preview'],2);
                  $crop = ( $fetch_content['crop_preview'] == 'Y' ) ? 1 : 0;
                  if (true !== ($pic_error = @image_resize($thumb_dir.$postbildname, $file_dir.'post_'.$post_id.'_'.$postbildname, $previewwidth, $previewheight, $crop))) {
                    echo $pic_error;
                    unlink($postbildname);
                  }
                  else {

                      $image = 'post_'.$post_id.'_'.$postbildname;
                      unlink($thumb_dir.$postbildname);
                  }

              }
            }    
        }
    }
//input file nur bei leerem db-feld
} elseif (!isset($_FILES["postfoto"])) {
    $image = $_POST['previewimage'];
}
  
// strip HTML from title
$title = strip_tags($title);
     
// Update row
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `group_id` = '$group_id', `title` = '$title', `link` = '$post_link', `content_short` = '$short', `content_long` = '$long', `image` = '$image', `commenting` = '$commenting', `active` = '$active', `published_when` = '$publishedwhen', `published_until` = '$publisheduntil', `posted_when` = '".time()."', `posted_by` = '".$admin->get_user_id()."' WHERE `post_id` = '$post_id'");

//update Bildbeschreibungen der tabelle mod_news_img_img
$query_img = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `post_id` = ".$post_id);
if($query_img->numRows() > 0) {

  while($row = $query_img->fetchRow()) {
      $row_id = $row['id'];
     // var_dump($row_id);
      //var_dump($_POST['bildbeschreibung'][$row_id]);
      $bildbeschreibung = isset($_POST['bildbeschreibung'][$row_id])
                        ? $_POST['bildbeschreibung'][$row_id]
                        : '';
     $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_img` SET `bildbeschreibung` = '$bildbeschreibung' WHERE id = '$row_id'");
  }
}



//   exit;
// Check if there is a db error, otherwise say successful
if($database->is_error())
{
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
}
else
{
	//$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    $admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
}

// Print admin footer
$admin->print_footer();
