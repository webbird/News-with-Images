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
$update_when_modified = false; // Tells script to update when this page was last updated
require(WB_PATH.'/modules/admin.php');
$tag_id = $admin->checkIDKEY('tag_id', 0, 'GET');
if (!$tag_id){
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (IDKEY) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
}
$tag_id = intval($tag_id);

// remove mappings
$database->query(sprintf(
    "DELETE FROM `%smod_news_img_tags_posts` WHERE `tag_id`=$tag_id",
    TABLE_PREFIX
));

// remove tag
$database->query(sprintf(
    "DELETE FROM `%smod_news_img_tags` WHERE `tag_id`=$tag_id",
    TABLE_PREFIX
));

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();
