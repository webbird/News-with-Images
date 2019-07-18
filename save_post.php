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

global $page_id, $section_id, $post_id;

require_once __DIR__.'/functions.inc.php';
require_once WB_PATH."/include/jscalendar/jscalendar-functions.php";

// Get id
if (!isset($_POST['post_id']) or !is_numeric($_POST['post_id'])) {
    header("Location: ".ADMIN_URL."/pages/index.php");
    exit(0);
} else {
    $id = intval($_POST['post_id']);
    $post_id = $id;
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
$admin_header = false;
// Include WB admin wrapper script
require WB_PATH.'/modules/admin.php';
if (!$admin->checkFTAN()) {
    $admin->print_header();
    $admin->print_error(
        $MESSAGE['GENERIC_SECURITY_ACCESS']
     .' (FTAN) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php'
    );
    $admin->print_footer();
    exit();
} else {
    $admin->print_header();
}

$group = '';
$block2 = '';

// Validate all fields
if ($admin->get_post('title') == '' and $admin->get_post('url') == '') {
    $post_id_key = $admin->getIDKEY($id);
    if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
        $post_id_key = $id;
    }
    $admin->print_error($MESSAGE['GENERIC']['FILL_IN_ALL'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id_key);
} else {
    $title = $database->escapeString($admin->get_post('title'));
    $link = $database->escapeString($admin->get_post('link'));
    $short = $database->escapeString($admin->get_post('short'));
    $long = $database->escapeString($admin->get_post('long'));
    if (NWI_USE_SECOND_BLOCK) {
        $block2 = $database->escapeString($admin->get_post('block2'));
    }
    $image = $database->escapeString($admin->get_post('image'));
    $active = $database->escapeString($admin->get_post('active'));
    $group = $database->escapeString($admin->get_post('group'));

    $tags = $admin->get_post('tags');
    $mediafile = $database->escapeString($admin->get_post('mediafile'));
}

$group_id = 0;
$old_section_id = $section_id;
$old_page_id = $page_id;

if (!empty($group)) {
    $gid_value = urldecode($group);
    $values = unserialize($gid_value);
    if (!isset($values['s']) or  !isset($values['g']) or  !isset($values['p'])) {
        header("Location: ".ADMIN_URL."/pages/index.php");
        exit(0);
    }
    if (intval($values['p'])!=0) {
        $group_id = intval($values['g']);
        $section_id = intval($values['s']);
        $page_id = intval($values['p']);
    }
}

// Get page link URL
$query_page = $database->query("SELECT `level`,`link` FROM `".TABLE_PREFIX."pages` WHERE `page_id` = '$page_id'");
$page = $query_page->fetchRow();
$page_level = $page['level'];
$page_link = $page['link'];

// get old link
$query_post = $database->query("SELECT `link` FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id`='$post_id'");
$post = $query_post->fetchRow();
$old_link = $post['link'];

// potential new link
$post_link = '/posts/'.page_filename($link);
// make sure to have the post_id as suffix; this will make the link unique (hopefully...)
if (substr_compare($post_link, $post_id, -(strlen($post_id)), strlen($post_id))!=0) {
    $post_link .= PAGE_SPACER.$post_id;
}

// Make sure the post link is set and exists
// Make news post access files dir
make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');
$file_create_time = '';
if (!is_writable(WB_PATH.PAGES_DIRECTORY.'/posts/')) {
    $admin->print_error($MESSAGE['PAGES']['CANNOT_CREATE_ACCESS_FILE']);
} elseif (($old_link != $post_link) or !file_exists(WB_PATH.PAGES_DIRECTORY.$post_link.PAGE_EXTENSION) or $page_id != $old_page_id or $section_id != $old_section_id) {
    // We need to create a new file
    // First, delete old file if it exists
    if (file_exists(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION)) {
        $file_create_time = filemtime(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION);
        unlink(WB_PATH.PAGES_DIRECTORY.$old_link.PAGE_EXTENSION);
    }
    if ($page_id != $old_page_id or $section_id != $old_section_id) {
        $file_create_time = '';
    }
    // Specify the filename
    $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
    mod_nwi_create_file($filename, $file_create_time);
}

// get publishedwhen and publisheduntil
$publishedwhen = jscalendar_to_timestamp($database->escapeString($admin->get_post('publishdate')));
if ($publishedwhen == '' || $publishedwhen < 1) {
    $publishedwhen=0;
} else {
    $publishedwhen -= TIMEZONE;
}

$publisheduntil = jscalendar_to_timestamp($database->escapeString($admin->get_post('enddate')), $publishedwhen);
if ($publisheduntil == '' || $publisheduntil < 1) {
    $publisheduntil=0;
} else {
    $publisheduntil -= TIMEZONE;
}

if (!defined('ORDERING_CLASS_LOADED')) {
    require WB_PATH.'/framework/class.order.php';
}

// post images (gallery images)
if (isset($_FILES["foto"])) {
    echo "FILE [",__FILE__,"] FUNC [",__FUNCTION__,"] LINE [",__LINE__,"]<br /><textarea style=\"width:100%;height:200px;color:#000;background-color:#fff;\">";
    print_r($_FILES);
    echo "</textarea><br />";
    mod_nwi_img_upload($post_id);
}

// ----- post (preview) picture; shown on overview page ----------------------------------
if (isset($_FILES["postfoto"]) && $_FILES["postfoto"]["name"] != "") {
    mod_nwi_img_upload($post_id, true);
} else {
    if (!empty($mediafile)) {
        // use existing image as preview image
        $imgdata = mod_nwi_img_get($mediafile);
        $image = $imgdata['picname'];
        $settings = mod_nwi_settings_get($section_id);
        // preview images size
        $previewwidth = $previewheight = '';
        if (substr_count($settings['resize_preview'], 'x')>0) {
            list($previewwidth, $previewheight) = explode('x', $settings['resize_preview'], 2);
        }
        $crop = ($settings['crop_preview'] == 'Y') ? 1 : 0;
echo "image $image post $post_id section $section_id width $previewwidth height $previewheight crop $crop<br />";
//image 2015-11-10_10_49_19.png post 25 section 48 width 125 height 125 crop 0
        mod_nwi_image_resize($mod_nwi_file_dir.$post_id.'/'.$image, $mod_nwi_file_dir.$image, $previewwidth, $previewheight, $crop);
        $database->query(sprintf(
            "UPDATE `%smod_news_img_posts` SET `image`='%s' " .
            "WHERE `post_id`=%d",
            TABLE_PREFIX, $image, $post_id
        ));
    }

    /*
Array
(
    [formtoken] => 7d6429d0-7f9d61ecbc03d572030b5bf2cc35bcfea21ce281
    [section_id] => 48
    [page_id] => 24
    [post_id] => 25
    [savegoback] =>
    [title] => Test mit Bild
    [link] => test-mit-bild
    [mediafile] => 60
    [group] => a%3A3%3A%7Bs%3A1%3A%22g%22%3Bi%3A0%3Bs%3A1%3A%22s%22%3Bi%3A48%3Bs%3A1%3A%22p%22%3Bi%3A24%3B%7D
    [active] => 1
    [publishdate] => 16.07.2019 15:27
    [enddate] =>
    [short] => <p>Workbench</p>

    [long] =>
    [block2] =>
    [picdesc] => Array
        (
            [60] =>
        )

    [save] => Speichern
)

    */
}
  
// strip HTML from title
$title = strip_tags($title);

$position="";
// if we are moving posts across section borders we have to update the order of the posts
if ($old_section_id!=$section_id) {
    // Get new order
    $order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
    $position = "`position` = '".$order->get_new($section_id)."',";
}

// Update row
$database->query(
    "UPDATE `".TABLE_PREFIX."mod_news_img_posts`"
    . " SET `page_id` = '$page_id',"
    . " `section_id` = '$section_id',"
    . " $position"
    . " `group_id` = '$group_id',"
    . " `title` = '$title',"
    . " `link` = '$post_link',"
    . " `content_short` = '$short',"
    . " `content_long` = '$long',"
    . " `content_block2` = '$block2',"
    . " `active` = '$active',"
    . " `published_when` = '$publishedwhen',"
    . " `published_until` = '$publisheduntil',"
    . " `posted_when` = '".time()."',"
    . " `posted_by` = '".$admin->get_user_id()."'"
    . " WHERE `post_id` = '$post_id'"
);

// when no error has occurred go ahead and update the image descriptions
if (!($database->is_error())) {
    //update Bildbeschreibungen der tabelle mod_news_img_img
    $images = mod_nwi_img_get_by_post($post_id);
    if (count($images) > 0) {
        foreach ($images as $row) {
            $row_id = $row['id'];
            $picdesc = isset($_POST['picdesc'][$row_id])
                          ? strip_tags($_POST['picdesc'][$row_id])
                          : '';
            $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_img` SET `picdesc` = '$picdesc' WHERE id = '$row_id'");
        }
    }
}

// if this went fine so far and we are moving posts across section borders we still have to reorder
if ((!($database->is_error()))&&($old_section_id!=$section_id)) {
    // Clean up ordering
    $order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
    $order->clean($old_section_id);
}

// remove current tags
$database->query(sprintf(
    "DELETE FROM `%smod_news_img_tags_posts` WHERE `post_id`=$post_id",
    TABLE_PREFIX
));
// re-add marked tags
if (is_array($tags) && count($tags)>0) {
    $existing = mod_nwi_get_tags($section_id);
    foreach (array_values($tags) as $t) {
        $t = intval($t);
        if (array_key_exists($t, $existing)) {
            $database->query(sprintf(
                "INSERT IGNORE INTO `%smod_news_img_tags_posts` VALUES('$post_id','$t')",
                TABLE_PREFIX
            ));
        }
    }
}

//   exit;
// Check if there is a db error, otherwise say successful
if ($database->is_error()) {
    $post_id_key = $admin->getIDKEY($id);
    if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
        $post_id_key = $id;
    }
    $admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id_key);
} else {
    if ($imageErrorMessage!='') {
        $post_id_key = $admin->getIDKEY($id);
        if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
            $post_id_key = $id;
        }
        $admin->print_error($MOD_NEWS_IMG['GENERIC_IMAGE_ERROR'].'<br />'.$imageErrorMessage, WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id_key);
    } else {
        if (isset($_POST['savegoback']) && $_POST['savegoback']=='1') {
            $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
        } else {
            $post_id_key = $admin->getIDKEY($id);
            if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
                $post_id_key = $id;
            }
            $admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id_key);
        }
    }
}

// Print admin footer
$admin->print_footer();
