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
 * @version         $Id: uninstall.php 1280 2010-01-29 02:59:35Z Luisehahne $
 * @filesource      $HeadURL: modules/news_img/uninstall.php $
 * @lastmodified    $Date: 2011-10-06  $ by Silvia Reins
 *
 */

// Must include code to stop this file being access directly
if(defined('WB_PATH') == false) { exit("Cannot access this file directly"); }

$database->query("DELETE FROM ".TABLE_PREFIX."search WHERE name = 'module' AND value = 'news'");
$database->query("DELETE FROM ".TABLE_PREFIX."search WHERE extra = 'news'");
$database->query("DROP TABLE ".TABLE_PREFIX."mod_news_img_posts");
$database->query("DROP TABLE ".TABLE_PREFIX."mod_news_img_groups");
$database->query("DROP TABLE ".TABLE_PREFIX."mod_news_img_comments");
$database->query("DROP TABLE ".TABLE_PREFIX."mod_news_img_settings");
$database->query("DROP TABLE ".TABLE_PREFIX."mod_news_img_img");

require_once(WB_PATH.'/framework/functions.php');
//rm_full_dir(WB_PATH.PAGES_DIRECTORY.'/posts');
rm_full_dir(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder');
rm_full_dir(WB_PATH.MEDIA_DIRECTORY.'/.news_img');

?>