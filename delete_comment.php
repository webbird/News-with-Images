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
 * @version         $Id: delete_comment.php 1280 2010-01-29 02:59:35Z Luisehahne $
 * @filesource      $HeadURL: modules/news_img/delete_comment.php $
 * @lastmodified    $Date: 2011-10-06  $ by Silvia Reins
 *
 */

require('../../config.php');

// Get id
if(!isset($_GET['comment_id']) OR !is_numeric($_GET['comment_id'])) {

	header("Location: ".ADMIN_URL."/pages/index.php");
	exit( 0 );
}
else
{
	$comment_id = $_GET['comment_id'];
}

// Get post id
if(!isset($_GET['post_id']) OR !is_numeric($_GET['post_id']))
{

	header("Location: ".ADMIN_URL."/pages/index.php");
	exit( 0 );
}
else
{
	$post_id = $_GET['post_id'];
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require(WB_PATH.'/modules/admin.php');

// Update row
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_comments  WHERE comment_id = '$comment_id'");

// Check if there is a db error, otherwise say successful
if($database->is_error())
{
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id);
}
else
{
	$admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id);
}

// Print admin footer
$admin->print_footer();

?>