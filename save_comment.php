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

require '../../config.php';

// Get id
if(!isset($_POST['comment_id']) OR !is_numeric($_POST['comment_id']) OR !isset($_POST['post_id']) OR !is_numeric($_POST['post_id']))
{

	header("Location: ".ADMIN_URL."/pages/index.php");
	exit( 0 );
}
else
{
	$comment_id = $_POST['comment_id'];
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require WB_PATH.'/modules/admin.php';

// Validate all fields
if($admin->get_post('title') == '' AND $admin->get_post('comment') == '')
{
	$admin->print_error($MESSAGE['GENERIC']['FILL_IN_ALL'], WB_URL.'/modules/news_img/modify_comment.php?page_id='.$page_id.'&section_id='.$section_id.'comment_id='.$id);
}
else
{
	$title = strip_tags($admin->get_post_escaped('title'));
	$comment = strip_tags($admin->get_post_escaped('comment'));
	$post_id = $admin->get_post('post_id');
}

// Update row
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_comments` SET `title` = '$title', `comment` = '$comment' WHERE `comment_id` = '$comment_id'");

// Check if there is a db error, otherwise say successful
if($database->is_error())
{
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_comment.php?page_id='.$page_id.'&section_id='.$section_id.'&comment_id='.$id);
}
else
{
	$admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id);
}

// Print admin footer
$admin->print_footer();
