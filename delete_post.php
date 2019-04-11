<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WebsiteBaker Project
 * @copyright       2004-2009, Ryan Djurovich
 * @copyright       2009-2010, Website Baker Org. e.V.
 * @link			      http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 4.3.4 and higher
 * @version         $Id: delete_post.php 1280 2010-01-29 02:59:35Z Luisehahne $
 * @filesource      $HeadURL: modules/news_img/delete_post.php $
 * @lastmodified    $Date: 2011-10-06  $ by Silvia Reins
 *
 */

require('../../config.php');

// Get id
if(!isset($_GET['post_id']) OR !is_numeric($_GET['post_id'])) {
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit(0);
} else {
	$post_id = $_GET['post_id'];
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require(WB_PATH.'/modules/admin.php');

// Get post details
$query_details = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_news_img_posts WHERE post_id = '$post_id'");
if($query_details->numRows() > 0) {
	$get_details = $query_details->fetchRow();
} else {
	$admin->print_error($TEXT['NOT_FOUND'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Unlink post access file
if(is_writable(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION)) {
	unlink(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION);
}

//get and remove all images created by posts in section
$query_img = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_news_img_img WHERE post_id = ".$post_id);
    if($query_img->numRows() > 0) {
        while($result = $query_img->fetchRow()) {
            if(is_writable(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/'.$result['bildname'])) {
                unlink(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/'.$result['bildname']);
                unlink(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/thumb/thumb_'.$result['bildname']);
            }
            $database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_img WHERE post_id = ".$post_id);
        }
}

// Delete post
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_posts WHERE post_id = '$post_id' LIMIT 1");
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_comments WHERE post_id = '$post_id'");

// Clean up ordering
require(WB_PATH.'/framework/class.order.php');
$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
$order->clean($section_id); 

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&post_id='.$post_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();

?>