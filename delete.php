<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WebsiteBaker Project
 * @copyright       2004-2009, Ryan Djurovich
 * @copyright       2009-2010, Website Baker Org. e.V.
 * @link			      http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 4.3.4 and higher
 * @version         $Id: delete.php 1280 2010-01-29 02:59:35Z Luisehahne $
 * @filesource	    $HeadURL: modules/news_img/delete.php $
 * @lastmodified    $Date: 2011-10-06  $ by Silvia Reins
 *
 */

// Must include code to stop this file being access directly
if(defined('WB_PATH') == false)
{
  exit("Cannot access this file directly");
}

//get and remove all php files created for the news_img section
$query_details = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_news_img_posts WHERE section_id = '$section_id'");
if($query_details->numRows() > 0) {
	while($link = $query_details->fetchRow()) {
		if(is_writable(WB_PATH.PAGES_DIRECTORY.$link['link'].PAGE_EXTENSION)) {
		unlink(WB_PATH.PAGES_DIRECTORY.$link['link'].PAGE_EXTENSION);
		}
                
                //get and remove all images created by posts in section
                $query_img = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_news_img_img WHERE post_id = ".$link['post_id']);
                if($query_img->numRows() > 0) {
                        while($result = $query_img->fetchRow()) {
                                if(is_writable(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/'.$result['bildname'])) {
                                    unlink(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/'.$result['bildname']);
                                    unlink(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/thumb/thumb_'.$result['bildname']);
                                    
                                }
                                $database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_img WHERE post_id = ".$link['post_id']);
                        }
                }
  unlink(WB_PATH.PAGES_DIRECTORY.'/beitragsbilder/'.$link['image']);
	}
}
//check to see if any other sections are part of the news page, if only 1 news is there delete it
$query_details = $database->query("SELECT * FROM ".TABLE_PREFIX."sections WHERE page_id = '$page_id'");
if($query_details->numRows() == 1) {
	$query_details2 = $database->query("SELECT * FROM ".TABLE_PREFIX."pages WHERE page_id = '$page_id'");
	$link = $query_details2->fetchRow();
	if(is_writable(WB_PATH.PAGES_DIRECTORY.$link['link'].PAGE_EXTENSION)) {
		unlink(WB_PATH.PAGES_DIRECTORY.$link['link'].PAGE_EXTENSION);
	}
}

$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_posts WHERE section_id = '$section_id'");
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_groups WHERE section_id = '$section_id'");
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_comments WHERE section_id = '$section_id'");
$database->query("DELETE FROM ".TABLE_PREFIX."mod_news_img_settings WHERE section_id = '$section_id'");

?>