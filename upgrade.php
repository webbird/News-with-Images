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

if(defined('WB_URL')) {

    function create_new_post($filename, $filetime=NULL, $content )
    {
        global $page_id, $section_id, $post_id;
    	// The depth of the page directory in the directory hierarchy
    	// '/pages' is at depth 1
    	$pages_dir_depth = count(explode('/',PAGES_DIRECTORY))-1;
    	// Work-out how many ../'s we need to get to the index page
    	$index_location = '../';
    	for($i = 0; $i < $pages_dir_depth; $i++)
        {
    		$index_location .= '../';
    	}

    	// Write to the filename
    	$content .='
define("POST_SECTION", $section_id);
define("POST_ID", $post_id);
require("'.$index_location.'config.php");
require(WB_PATH."/index.php");
?>';
    	if($handle = fopen($filename, 'w+'))
        {
        	fwrite($handle, $content);
        	fclose($handle);
            if($filetime)
            {
                touch($filename, $filetime);
            }
        	change_mode($filename);
        }
    }   // end function create_new_post()

    // read files from /pages/posts/
    if( !function_exists('scandir') )
    {
        function scandir($directory, $sorting_order = 0)
        {
            $dh  = opendir($directory);
            while( false !== ($filename = readdir($dh)) )
            {
                $files[] = $filename;
            }
            if( $sorting_order == 0 )
            {
                sort($files);
            } else
            {
                rsort($files);
            }
            return($files);
        }
    }   // end function scandir()

    $target_dir = WB_PATH . PAGES_DIRECTORY.'/posts/';
	$files = scandir($target_dir);
	natcasesort($files);

	// All files in /pages/posts/
	foreach( $files as $file )
    {
        if( file_exists($target_dir.$file)
            AND ($file != '.')
                AND ($file != '..')
                    AND ($file != 'index.php') )
        {
            clearstatcache();
            $timestamp = filemtime ( $target_dir.$file );
            $lines = file($target_dir.$file);
            $content = '';
            // read lines until first define
            foreach ($lines as $line_num => $line) {
                if(strstr($line,'define'))
                {
                  break;
                }
                $content .= $line;
            }

            create_new_post($target_dir.$file, $timestamp, $content);
        }

    }

    // 2014-04-10 by BlackBird Webprogrammierung:
    //            image position
    $database->query(sprintf(
        'ALTER TABLE `%smod_news_img_img` ADD `position` INT(11) NOT NULL DEFAULT \'0\' AFTER `post_id`',
        TABLE_PREFIX
    ));

    // 2018-11-28 by BlackBird Webprogrammierung:
    //            new image resize settings (leaving the old column untouched)
    $database->query(sprintf(
        'ALTER TABLE `%smod_news_img_settings` ADD `resize_preview` VARCHAR(50) NULL AFTER `resize`, ADD `crop_preview` CHAR(1) NOT NULL DEFAULT \'N\' AFTER `resize_preview`',
        TABLE_PREFIX
    ));

    // Print admin footer
    $admin->print_footer();
}
