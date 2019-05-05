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


require_once __DIR__.'/functions.inc.php';

// Get id
if((!isset($_GET['post_id']) OR !is_numeric($_GET['post_id']))AND(!isset($_POST['manage_posts']))) {
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit(0);
} 

$posts=array();
if (isset($_GET['post_id'])){    
    $posts = array($_GET['post_id']);
} else {
    if(isset($_POST['manage_posts'])&&is_array($_POST['manage_posts'])) 
        $posts=$_POST['manage_posts'];
} 

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require WB_PATH.'/modules/admin.php';

// Include the ordering class
require WB_PATH.'/framework/class.order.php';

//store this one for later use
$mod_nwi_file_base=$mod_nwi_file_dir; 

foreach($posts as $post_id) {
    // Get post details
    $query_details = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '$post_id'");
    if($query_details->numRows() > 0) {
	    $get_details = $query_details->fetchRow();
    } else {
	    $admin->print_error($TEXT['NOT_FOUND'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    }

    // Unlink post access file
    if(is_writable(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION)) {
	    unlink(WB_PATH.PAGES_DIRECTORY.$get_details['link'].PAGE_EXTENSION);
    }

    // delete images
    $mod_nwi_file_dir .= "$post_id";
    rm_full_dir($mod_nwi_file_dir);
    $database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `post_id` = ".$post_id);

    // Delete post
    $database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '$post_id' LIMIT 1");

    // Clean up ordering
    $order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
    $order->clean($section_id); 

    if($database->is_error()) break;
}

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&post_id='.$post_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
