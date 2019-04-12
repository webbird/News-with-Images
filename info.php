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
$module_version     = '3.7.1';
$module_platform    = '2.8.x';
$module_author      = 'Ryan Djurovich, Rob Smith, Silvia Reins, Bianka Martinovic';
$module_license     = 'GNU General Public License';
$module_description = 'This page type is designed for making a news page with Images and Lightboxeffect.';

/**
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