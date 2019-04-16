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

if(defined('WB_PATH') == false) { exit("Cannot access this file directly"); }

$header = ''."\n";
$post_loop = '<div class="mod_nwi_group">
    <div class="mod_nwi_teaserpic">
        <a href="[LINK]">[IMAGE]</a>
    </div>
    <div class="mod_nwi_teasertext">
        <a href="[LINK]"><h3>[TITLE]</h3></a>
        <div class="mod_nwi_metadata">[DISPLAY_NAME] | [PUBLISHED_DATE]</div>
            <div class="mod_nwi_shorttext">
                [SHORT]
            </div>
            <span style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[TEXT_READ_MORE]</a></span>
        </div>
    </div>
    <div class="mod_nwi_spacer"><hr /></div>';
$footer = '<table class="page-header" style="display: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="page-left">[PREVIOUS_PAGE_LINK]</td>
    <td class="page-center">[OF]</td>
    <td class="page-right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';
$post_header = addslashes('<h2>[TITLE]</h2>');
$post_content = '
<div class="fotorama"
     id="keyboard"
     data-navposition="top"
     data-nav="thumbs"
     data-width="700"
     data-ratio="3/2">
[IMAGES]
</div>
';
$image_loop = '<img src="[IMAGE]" data-caption="[DESCRIPTION]" />';
$post_footer = '<div class="div_link">
<a href="[BACK]">[TEXT_BACK]</a>
</div>';
$comments_header = addslashes('');
$comments_loop = addslashes('');
$comments_footer = '';
$comments_page = '';
$commenting = 'none';
$use_captcha = true;
$resize_preview = '125x125';

$database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_settings` (`section_id`,`page_id`,`header`,`post_loop`,`footer`,`post_header`,`post_content`,`image_loop`,`post_footer`,`comments_header`,`comments_loop`,`comments_footer`,`comments_page`,`commenting`,`use_captcha`,`gallery`,`resize_preview`) VALUES ('$section_id','$page_id','$header','$post_loop','$footer','$post_header','$post_content','$image_loop','$post_footer','$comments_header','$comments_loop','$comments_footer','$comments_page','$commenting','$use_captcha','','$resize_preview')");
