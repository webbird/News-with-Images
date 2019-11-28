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

if (defined('WB_PATH') == false) {
    exit("Cannot access this file directly");
}

require_once __DIR__.'/functions.inc.php'; // also loads lang file

global $MOD_NEWS_IMG, $TEXT;
require __DIR__ . '/languages/EN.php';
$lang = __DIR__ . '/languages/' . LANGUAGE . '.php';
if(file_exists($lang)) {
    require $lang;
}

// overwrite php.ini on Apache servers for valid SESSION ID Separator
if (function_exists('ini_set')) {
    ini_set('arg_separator.output', '&amp;');
}

// get settings for current section
$settings = mod_nwi_settings_get(intval($section_id));

// Get page info
$query_page = $database->query(sprintf(
    "SELECT `link` FROM `%spages` WHERE `page_id` = '%d'",
    TABLE_PREFIX,intval(PAGE_ID)
));
if ($query_page->numRows() > 0) {
    $page = $query_page->fetchRow();
    $page_link = page_link($page['link']);
    if (isset($_GET['p']) and intval($_GET['p']) > 0) {
        $page_link .= '?p='.intval($_GET['p']);
    }
    if (isset($_GET['g']) and is_numeric($_GET['g'])) {
        if (isset($_GET['p']) and $position > 0) {
            $delim = '&amp;';
        } else {
            $delim = '?';
        }
        $page_link .= $delim.'g='.$_GET['g'];
    }
}

list($vars,$default_replacements) = mod_nwi_replacements();

// switch: posting list or post details

$page_keywords = array();

// ----- read post ---------------------------------------------------------------
if (defined('POST_ID') && is_numeric(POST_ID)) {

    if(!$page_link) {
        exit($MESSAGE['PAGES']['NOT_FOUND']);
    }

    // tags
    $tags = mod_nwi_get_tags_for_post(POST_ID);
    foreach ($tags as $i => $tag) {
        $tags[$i] = "<span class=\"mod_nwi_tag\" id=\"mod_nwi_tag_".POST_ID."_".$i."\""
                  . (strlen($tag['tag_color']>0) ? "style=\"color:".$tag['tag_color']."\"" : "" ) .">"
                  . "<a href=\"".$wb->page_link(PAGE_ID)."?tags=".$tag['tag']."\">".$tag['tag']."</a></span>";
        if(!isset($page_keywords[$tag['tag']])) {
            $page_keywords[] = htmlspecialchars($tag['tag'], ENT_QUOTES | ENT_HTML401);
        }
    }

    $post = mod_nwi_post_show(intval(POST_ID));
    $images = mod_nwi_img_get_by_post(intval(POST_ID),true);

    $replacements = array_merge(
        $default_replacements,
        $TEXT,
        array_change_key_case($post,CASE_UPPER),
        $MOD_NEWS_IMG,
        array(
            'IMAGE'           => $post['post_img'],
			'IMAGE_URL' 	  => WB_URL.MEDIA_DIRECTORY.'/.news_img/'.$post['image'],
            'IMAGES'          => implode("", $images),
            'SHORT'           => $post['content_short'],
            'LINK'            => $post['post_link'],
            'MODI_DATE'       => $post['post_date'],
            'MODI_TIME'       => $post['post_time'],
            'TAGS'            => implode(" ", $tags),
            'CONTENT'         => $post['content_short'].$post['content_long'],
            'BACK'            => $page_link,
            'PREVIOUS_PAGE_LINK'
                => (strlen($post['prev_link'])>0 ? '<a href="'.$post['prev_link'].'">'.$MOD_NEWS_IMG['TEXT_PREV_POST'].'</a>' : null),
            'NEXT_PAGE_LINK'
                => (strlen($post['next_link'])>0 ? '<a href="'.$post['next_link'].'">'.$MOD_NEWS_IMG['TEXT_NEXT_POST'].'</a>' : null),
            'DISPLAY_PREVIOUS_NEXT_LINKS'
                => ((strlen($post['prev_link'])>0 || strlen($post['next_link'])>0) ? 'visible' : 'hidden'),
        )
    );

    $output = preg_replace_callback(
        '~\[('.implode('|',$vars).')+\]~',
        function($match) use($replacements) {
            return (isset($match[1]) && isset($replacements[$match[1]]))
                ? $replacements[$match[1]]
                : '';
        },
        $settings['post_header'].$settings['post_content'].$settings['post_footer']
    );

    // include gallery template
    if (strlen($settings['gallery'])) {
        include __DIR__.'/js/galleries/'.$settings['gallery'].'/include.tpl';
    }

// ----- list of posts -----------------------------------------------------------
} else {
    $posts = mod_nwi_post_list(intval($section_id));
    $tpl_data = mod_nwi_posts_render($section_id,$posts,$settings['posts_per_page']);
    $tags = mod_nwi_get_tags($section_id);
    foreach($tags as $t) {
        $page_keywords[] = htmlspecialchars($t['tag'], ENT_QUOTES | ENT_HTML401);
    }

}

$view = (strlen($settings['view']) ? $settings['view'] : 'default');
if(!defined('CAT_PATH')) {
    if (defined('WBCE_VERSION') && version_compare(WBCE_VERSION, '1.4.0', '>')) {
        I::insertMetaTag(array (
             "setname" => "keywords",
             "name"    => "keywords",
             "content" => implode(' ',$page_keywords),
             "append"  => ", "
        ));
    }
}
include __DIR__.'/templates/default/view.phtml';
