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

$module_directory   = 'news_img';
$module_name        = 'News with Images';
$module_function    = 'page';
$module_version     = '3.7.8';
$module_platform    = '2.8.x';
$module_author      = 'Ryan Djurovich, Rob Smith, Silvia Reins, Martin Hecht, Florian Meerwinck, Bianka Martinovic';
$module_license     = 'GNU General Public License';
$module_description = 'This page type is designed for making a news page with Images and Lightboxeffect.';

/**
 * v3.7.8 - 2019-04-23 Bianka Martinovic
 *          + renamed db column "bildname" to "picname", "bildbeschreibung" to "picdesc"
 *          + show publishing dates in backend -> list view
 *
 * v3.7.7 - 2019-04-18 Bianka Martinovic
 *          + removed all commenting options / tables / columns / files
 *
 * v3.7.6 - 2019-04-16 
 *        - Martin Hecht
 *          + copy posts
 *          + display post id in backend
 *        - Bianka Martinovic 
 *          + moved images folder to MEDIA_DIRECTORY/news_img
 *        - Florian Meerwinck
 *          + Changes to frontend display
 *          + add default preview size
 *
 * v3.7.5 - 2019-04-15 Martin Hecht
 *          + allow moving posts across section borders
 *          + a few bugfixes
 *
 * v3.7.4 - 2019-04-14 
 *        - Martin Hecht:
 *          + use news_img throughout the module, especially in the search tables
 *        - Bianka Martinovic: 
 *          + placeholder [GROUP_IMAGE_URL]
 *          + show group image in group settings
 *          + fixed zebra markup for settings table (table class "striped")
 *          + added odd and even table row colors (backend.css)
 *          + moved table styles to css
 *          + added info for "custom" sort order
 *        - Florian Meerwinck:
 *          + Preparation for Blog Menu
 *          + Remove non-translated language files
 *          + UI tweaks and language support:
 *            Replacing hard coded strings with language vars, 
 *            some optimizations on look & feel
 *
 * v3.7.3 - 2019-04-13 Martin Hecht
 *          + added automatic ordering
 *          + bugfixes for the gallery settings
 *
 * v3.7.2 - 2019-04-12 Martin Hecht
 *          + added second block
 *
 * v3.7.1 - 2019-04-12 Bianka Martinovic
 *          + added Masonry
 *          + added Gallery setting
 *
 * v3.7.0 - 2019-04-12 Bianka Martinovic
 *          + added Fotorama as default image gallery
 *          + added settings for post content markup and image loop
 *
 * v3.6.6 - 2019-04-10 Bianka Martinovic
 *          + Remove all tags from news title
 *
 * v3.6.5 - 2019-04-10 Bianka Martinovic
 *          + Fix: Warning: sizeof(): Parameter must be an array
 *          + Fix: Undefined index: crop_preview
 **/
