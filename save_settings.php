<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WebsiteBaker Project
 * @copyright       2004-2009, Ryan Djurovich
 * @copyright       2009-2010, Website Baker Org. e.V.
 * @link			http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 4.3.4 and higher
 * @version         $Id: save_settings.php 1280 2010-01-29 02:59:35Z Luisehahne $
 * @filesource      $HeadURL: modules/news_img/save_settings.php $
 * @lastmodified    $Date: 2011-10-06  $ by Silvia Reins
 *
 */

require('../../config.php');

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require(WB_PATH.'/modules/admin.php');

// This code removes any <?php tags and adds slashes
$friendly = array('&lt;', '&gt;', '?php');
$raw = array('<', '>', '');
$header = $admin->add_slashes(str_replace($friendly, $raw, $_POST['header']));
$post_loop = $admin->add_slashes(str_replace($friendly, $raw, $_POST['post_loop']));
$footer = $admin->add_slashes(str_replace($friendly, $raw, $_POST['footer']));
$post_header = $admin->add_slashes(str_replace($friendly, $raw, $_POST['post_header']));
$post_footer = $admin->add_slashes(str_replace($friendly, $raw, $_POST['post_footer']));
$comments_header = $admin->add_slashes(str_replace($friendly, $raw, $_POST['comments_header']));
$comments_loop = $admin->add_slashes(str_replace($friendly, $raw, $_POST['comments_loop']));
$comments_footer = $admin->add_slashes(str_replace($friendly, $raw, $_POST['comments_footer']));
$comments_page = $admin->add_slashes(str_replace($friendly, $raw, $_POST['comments_page']));
$commenting = $admin->add_slashes($_POST['commenting']);
$posts_per_page = $admin->add_slashes($_POST['posts_per_page']);
$use_captcha = $admin->add_slashes($_POST['use_captcha']);

$resize = '';
$resize_preview = '';
$crop = 'N';
if(extension_loaded('gd') AND function_exists('imageCreateFromJpeg')) {
    $width = $_POST['resize_width'];
    $height = $_POST['resize_height'];
    $crop = (isset($_POST['crop_preview']) ? $_POST['crop_preview'] : 'N');
    $resize = $_POST['resize'];
    if(is_numeric($width) && is_numeric($height)) {
        if($height>0 && $width>0) {
            $resize_preview = $width.'x'.$height;
        }
    }
    if($crop=='on') {
        $crop = 'Y';
    } else {
        $crop = 'N';
    }
}

if($posts_per_page=='') {
    $posts_per_page = 0; // unlimited
}

// Update settings
$database->query("UPDATE ".TABLE_PREFIX."mod_news_img_settings SET header = '$header', post_loop = '$post_loop', footer = '$footer', posts_per_page = '$posts_per_page', post_header = '$post_header', post_footer = '$post_footer', comments_header = '$comments_header', comments_loop = '$comments_loop', comments_footer = '$comments_footer', comments_page = '$comments_page', commenting = '$commenting', resize = '$resize', resize_preview = '$resize_preview', crop_preview = '$crop', use_captcha = '$use_captcha' WHERE section_id = '$section_id'");

// Check if there is a db error, otherwise say successful
if($database->is_error()) {
	$admin->print_error($database->get_error(), ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
} else {
	$admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
}

// Print admin footer
$admin->print_footer();

?>