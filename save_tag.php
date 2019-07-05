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
$admin_header = FALSE;
require WB_PATH.'/modules/admin.php';
if (!$admin->checkFTAN()){
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (FTAN) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
} else {
    $admin->print_header();
}

require_once __DIR__.'/functions.inc.php';

// Validate all fields
if($admin->get_post('new_tag') == '' && $admin->get_post('tag_id') == '')
{
	$admin->print_error($MESSAGE['GENERIC']['FILL_IN_ALL'], WB_URL.'/modules/news_img/modify.php?page_id='.$page_id.'&section_id='.$section_id);
    $admin->print_footer();
    exit();
}
else
{
    if($admin->get_post('tag_id') != '') {
        $tag_id = intval($admin->get_post('tag_id'));
        $tag = $admin->get_post('tag');
    } else {
        $tag_id = null;
        $tag = $admin->get_post('new_tag');
    }
	$tag = $database->escapeString($tag);
	$tag = strip_tags($tag);
}

// Update row
if(empty($tag_id)) {
    $database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_tags` ( `section_id`, `tag` ) VALUES ($section_id,'$tag')");
} else {
    $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_tags` SET `tag`='$tag' WHERE `tag_id`=$tag_id");
}

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify.php?page_id='.$page_id.'&section_id='.$section_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();

