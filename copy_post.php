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


// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php')) {
	// no module language file exists for the language set by the user, include default module language file EN.php
	require_once WB_PATH .'/modules/news_img/languages/EN.php';
} else {
	// a module language file exists for the language defined by the user, load it
	require_once WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php';
}

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

require_once __DIR__.'/functions.inc.php';

$imageErrorMessage ='';
$file_dir  = WB_PATH.MEDIA_DIRECTORY.'/news_img/'.$post_id.'/';
$thumb_dir = WB_PATH.MEDIA_DIRECTORY.'/news_img/'.$post_id.'/thumb/';


// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require WB_PATH.'/modules/admin.php';

$pid = intval($admin->get_post_escaped('pid'));

if($pid != 0){

$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '$pid'");
$fetch_content = $query_content->fetchRow();

$title = $fetch_content['title'];
$short = $fetch_content['content_short'];
$long = $fetch_content['content_long'];
$block2 = $fetch_content['content_block2'];
$image = $fetch_content['image'];
$active = $fetch_content['active'];
$group_id = $fetch_content['group_id'];
$publishedwhen =  $fetch_content['published_when'];
$publisheduntil =  $fetch_content['published_until'];


if(!is_dir($file_dir)) {
    mod_news_img_makedir($file_dir);
}
mod_news_img_copy(WB_PATH.MEDIA_DIRECTORY.'/news_img/'.$pid,$file_dir);


// Update row
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `page_id` = '$page_id', `section_id` = '$section_id', `group_id` = '$group_id', `title` = '$title', `content_short` = '$short', `content_long` = '$long', `content_block2` = '$block2', `image` = '$image', `active` = '$active', `published_when` = '$publishedwhen', `published_until` = '$publisheduntil', `posted_when` = '".time()."', `posted_by` = '".$admin->get_user_id()."' WHERE `post_id` = '$post_id'");
} 

if(!($database->is_error()){
    //update table images
   $database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_img` (`imgname`, `imgdescription`, `post_id`, `position`) SELECT `imgname`, `imgdescription`, '".$post_id."', `position` FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `post_id` = '".$pid."'");
}

//   exit;
// Check if there is a db error, otherwise say successful
if($database->is_error())
{
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
}
else
{
	if ($imageErrorMessage!='') {		
		$admin->print_error($MOD_NEWS_IMG['GENERIC_IMAGE_ERROR'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
	} else {
		if (isset($_POST['savegoback']) && $_POST['savegoback']=='1') {
			$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
		} else {
			$admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$id);
		}
	}
}

// Print admin footer
$admin->print_footer();
