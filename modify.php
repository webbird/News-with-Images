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

// Must include code to stop this file being access directly
if (!defined('WB_PATH')) {
    exit("Cannot access this file directly");
}

require_once __DIR__.'/functions.inc.php';

// cleanup database (orphaned)
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");

// overwrite php.ini on Apache servers for valid SESSION ID Separator
if (function_exists('ini_set')) {
    ini_set('arg_separator.output', '&amp;');
}
$section_key = $admin->getIDKEY($section_id);

// Get settings
$query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
if ($query_settings->numRows() > 0) {
    $fetch_settings = $query_settings->fetchRow();
    $setting_view_order = ($fetch_settings['view_order']);
} else {
    $setting_view_order = 0;
}

$order_by = "position";
switch($setting_view_order) {
    case 1:
        $order_by = "published_when";
        break;
    case 2:
        $order_by = "published_until";
        break;
    case 3:
        $order_by = "posted_when";
        break;
    case 4:
        $order_by = "post_id";
        break;
}

// map order to lang string
$lang_map = array(
    0 => $TEXT['CUSTOM'],
    1 => $TEXT['PUBL_START_DATE'],
    2 => $TEXT['PUBL_END_DATE'],
    3 => $TEXT['SUBMITTED'],
    4 => $TEXT['SUBMISSION_ID']
);

$FTAN = $admin->getFTAN();

// Loop through existing posts

// Include the ordering class
require_once(WB_PATH.'/framework/class.order.php');

// Create new order object and reorder
$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
$order->clean($section_id);

$query_posts = $database->query(sprintf(
    "SELECT *, (select count(`post_id`) FROM `%smod_news_img_tags_posts` AS t2 WHERE t2.post_id=t1.post_id ) as tags " .
    "FROM `%smod_news_img_posts` AS t1 WHERE `section_id` = '$section_id' ORDER BY `$order_by` DESC",
    TABLE_PREFIX, TABLE_PREFIX
));

$posts = array();
$importable_sections = 0;

// if there are already some posts, list them
if ($query_posts->numRows() > 0) {
    $num_posts = $query_posts->numRows();
    while ($post = $query_posts->fetchRow()) {
        $post['id_key'] = $admin->getIDKEY($post['post_id']);
        if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
            $post['id_key'] = $post['post_id'];
        }
        // Get group title
        $query_title = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `group_id` = '".$post['group_id']."'");
        if ($query_title->numRows() > 0) {
            $fetch_title = $query_title->fetchRow();
            $post['group_title'] = $fetch_title['title'];
        } else {
            $post['group_title'] = $TEXT['NONE'];
        }

        $t = time();
        $icon = '';
        if ($post['published_when']<=$t && $post['published_until']==0) {
            $post['icon'] ='<span class="fa fa-fw fa-calendar-o" title="'.$MOD_NEWS_IMG['POST_ACTIVE'].'"></span>';
        } elseif (($post['published_when']<=$t || $post['published_when']==0) && $post['published_until']>=$t) {
            $post['icon'] ='<span class="fa fa-fw fa-calendar-check-o nwi-active" title="'.$MOD_NEWS_IMG['POST_ACTIVE'].'"></span>';
        } else {
            $post['icon'] ='<span class="fa fa-fw fa-calendar-times-o nwi-inactive" title="'.$MOD_NEWS_IMG['POST_INACTIVE'].'"></span>';
        }
        $posts[] = $post;
    }
// ... else find importable items from other sections
} else {

    // count groups
    $query_groups = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id'");
    $num_groups = $query_groups->numRows();
    if ($num_groups != 0) {

        // news with images
        $query_nwi = $database->query(sprintf(
            "SELECT `section_id` FROM `%ssections`" .
            " WHERE `module` = 'news_img' AND `section_id` != '$section_id' ORDER BY `section_id` ASC",
            TABLE_PREFIX
        ));
        $importable_sections = $query_nwi->numRows();
        // classical news
        $query_news = $database->query(sprintf(
            "SELECT `section_id` FROM `%ssections`" .
            " WHERE `module` = 'news' ORDER BY `section_id` ASC",
            TABLE_PREFIX
        ));
        $importable_sections += $query_news->numRows();
        // topics
        $topics_names = array();
        $query_tables = $database->query("SHOW TABLES");
        while ($table_info = $query_tables->fetchRow()) {
            $table_name = $table_info[0];
            $topics_name=preg_replace('/'.TABLE_PREFIX.'mod_/', '', $table_name);
            $res = $database->query("SHOW COLUMNS FROM `$table_name` LIKE 'topic_id'");
            if ($res->numRows() > 0) {
                $topics_names[] = $topics_name;
                $query_topics = $database->query(sprintf(
                    "SELECT `section_id` FROM `".TABLE_PREFIX."sections`" .
                    " WHERE `module` = '$topics_name' ORDER BY `section_id` ASC",
                    TABLE_PREFIX
                ));
                $importable_sections += $query_topics->numRows();
            }
        }

        $nwi_sections = array();
        $news_sections = array();
        $topics_sections = array();

        if ($query_nwi->numRows() > 0) {
            // Loop through possible sections
            while ($source = $query_nwi->fetchRow()) {
                $nwi_sections[] = $source;
            }
        }
        if ($query_news->numRows() > 0) {
            // Loop through possible sections
            while ($source = $query_news->fetchRow()) {
                $news_sections[] = $source;
            }
        }

        foreach ($topics_names as $topics_name) {
            $topics_sections[$topics_name] = array();
            $query_topics = $database->query(sprintf(
                "SELECT `section_id` FROM `%ssections`" .
                " WHERE `module` = '$topics_name' ORDER BY `section_id` ASC",
                TABLE_PREFIX
            ));
            if ($query_topics->numRows() > 0) {
                #echo '<option disabled value="0">[--- '.$topics_name.' ---]</option>';
                // Loop through possible sections
                while ($source = $query_topics->fetchRow()) {
                    #echo '<option value="'.$source['section_id'].'">'.$TEXT['SECTION'].' '.$source['section_id'].'</option>';
                    $topics_sections[$topics_name][] = $source;
                }
            }
        }
    }
}

// groups
$order = new order(TABLE_PREFIX.'mod_news_img_groups', 'position', 'group_id', 'section_id');
$order->clean($section_id);

// Loop through existing groups
$groups = array();
$query_groups = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' ORDER BY `position` ASC");
if ($query_groups->numRows() > 0) {
    $num_groups = $query_groups->numRows();
    while ($group = $query_groups->fetchRow()) {
        $group['id_key'] = $admin->getIDKEY($group['group_id']);
        if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
            $group['id_key'] = $group['group_id'];
        }
        $groups[] = $group;
    }
}

// existing tags
$tags = mod_nwi_get_tags($section_id);

include __DIR__.'/templates/default/modify.phtml';
