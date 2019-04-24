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

// Include WB admin wrapper script
require(WB_PATH.'/modules/admin.php');

// Include the ordering class
require(WB_PATH.'/framework/class.order.php');

// Get new order
$order = new order(TABLE_PREFIX.'mod_news_img_groups', 'position', 'group_id', 'section_id');
$position = $order->get_new($section_id);

// Insert new row into database
$database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_groups` (`section_id`,`page_id`,`position`,`active`) VALUES ('$section_id','$page_id','$position','1')");

// Get the id
$group_id = $database->get_one("SELECT LAST_INSERT_ID()");

// Say that a new record has been added, then redirect to modify page
if($database->is_error()) {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$group_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$group_id);
}

// Print admin footer
$admin->print_footer();
