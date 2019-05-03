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

// Get id
if (!isset($_POST['source_id']) or !isset($_POST['section_id']) or !isset($_POST['page_id'])) {
    header("Location: ".ADMIN_URL."/pages/index.php");
    exit(0);
} 

require_once __DIR__.'/functions.inc.php';

$mod_nwi_file_base=$mod_nwi_file_dir;

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
require WB_PATH.'/modules/admin.php';


$section_id = intval($_POST['section_id']);
$page_id = intval($_POST['page_id']);
$source_id = intval($_POST['source_id']);

// find out which type we have to import
$query_module = $database->query("SELECT `module` FROM `".TABLE_PREFIX."sections` WHERE `section_id` = '$source_id'");
$module_type = "";
if($query_module->numRows()==1){
    $module_result = $query_module->fetchRow();
    $module_type = $module_result['module'];
}

// handle topics names
$topics_name = "topics";
$query_tables = $database->query("SHOW TABLES");
while ($table_info = $query_tables->fetchRow()) {
    $table_name = $table_info[0];    
    $topics_name=preg_replace('/'.TABLE_PREFIX.'mod_/','',$table_name);
    $res = $database->query("SHOW COLUMNS FROM `$table_name` LIKE 'topic_id'");
    if (($res->numRows() > 0) && ($module_type == $topics_name)) {
       $module_type = "topics";
       break;
    }
}



$query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
$original_settings = $query_settings->fetchRow();


if($module_type == "news_img"){

// =========================================== News with images ======================================

$query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$source_id'");
$fetch_settings = $query_settings->fetchRow();


// Update settings
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_settings` SET ".
    "`header` = '".$database->escapeString($fetch_settings['header'])."', ".
    "`post_loop` = '".$database->escapeString($fetch_settings['post_loop'])."', ".
    "`view_order` = ".$fetch_settings['view_order'].", ".
    "`footer` = '".$database->escapeString($fetch_settings['footer'])."', ".
    "`block2` = '".$database->escapeString($fetch_settings['block2'])."', ".
    "`posts_per_page` = ".$fetch_settings['posts_per_page'].", ".
    "`post_header` = '".$database->escapeString($fetch_settings['post_header'])."', ".
    "`post_content` = '".$database->escapeString($fetch_settings['post_content'])."', ".
    "`image_loop` = '".$database->escapeString($fetch_settings['image_loop'])."', ".
    "`post_footer` = '".$database->escapeString($fetch_settings['post_footer'])."', ".
    "`resize_preview` = '".$database->escapeString($fetch_settings['resize_preview'])."', ".
    "`crop_preview` = '".$database->escapeString($fetch_settings['crop_preview'])."', ".
    "`gallery` = '".$database->escapeString($fetch_settings['gallery'])."', ".
    "`imgmaxsize` = '".$database->escapeString($fetch_settings['imgmaxsize'])."', ".
    "`imgmaxwidth` = '".$database->escapeString($fetch_settings['imgmaxwidth'])."', ".
    "`imgmaxheight` = '".$database->escapeString($fetch_settings['imgmaxheight'])."', ".
    "`imgthumbsize` = '".$database->escapeString($fetch_settings['imgthumbsize'])."' ".
    "WHERE `section_id` = '$section_id'");


if(!($database->is_error())){

    // Update row
    $database->query(  "INSERT INTO `".TABLE_PREFIX."mod_news_img_groups` (`section_id`,`page_id`,`active`,`position`,`title`) SELECT '".$section_id."', '".$page_id."', `active`,`position`,`title` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '".$source_id."'");

    if(!($database->is_error())){

	// Include the ordering class
	require_once(WB_PATH.'/framework/class.order.php');
	// Create new order object and reorder
	$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
	$order->clean($source_id);
    
	$query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `section_id` = '$source_id' ORDER BY `post_id`");
	if($query_posts->numRows() > 0) {
    	    $num_posts = $query_posts->numRows();
    	    while($post = $query_posts->fetchRow()) {
		// Get new order
		$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
		$position = $order->get_new($section_id);

		// Insert new row into database
		$sql = "INSERT INTO `".TABLE_PREFIX."mod_news_img_posts` (`section_id`,`page_id`,`group_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) VALUES ('$section_id','$page_id','0','$position','','','','','0')";
		$database->query($sql);

		$post_id = $database->get_one("SELECT LAST_INSERT_ID()");

		$mod_nwi_file_dir = "$mod_nwi_file_base/$post_id/";
		$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";

		$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '".$post['post_id']."'");
		$fetch_content = $query_content->fetchRow();

		$title = $fetch_content['title'];
		$link = $fetch_content['link'];
		$group_id = $fetch_content['group_id'];
		if($group_id!=0){
		    // find out new group

		    $query_groups = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$source_id' AND `group_id` = '$group_id' ORDER BY `position` ASC");
		    if($query_groups->numRows()==0){
			$group_id=0;
		    } else {
			$group_result=$query_groups->fetchRow();
			$group_title=$group_result['title'];
			$query_groups = $database->query("SELECT `group_id` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' AND `title` = '$group_title' ORDER BY `group_id` DESC");
			if($query_groups->numRows()==0){
			    $group_id=0;
			} else {
			    $group_result=$query_groups->fetchRow();
			    $group_id = $group_result['group_id'];
			}
		    }
		}
		$posted_by = $fetch_result['posted_by'];
		$short = $fetch_content['content_short'];
		$long = $fetch_content['content_long'];
		$block2 = $fetch_content['content_block2'];
		$image = $fetch_content['image'];
		$active = $fetch_content['active'];
		$publishedwhen =  $fetch_content['published_when'];
		$publisheduntil =  $fetch_content['published_until'];

		// Get page link URL
		$query_page = $database->query("SELECT `level`,`link` FROM `".TABLE_PREFIX."pages` WHERE `page_id` = '$page_id'");
		$page = $query_page->fetchRow();
		$page_level = $page['level'];
		$page_link = $page['link'];

		// get old link
		$old_link = $link;

		// new link
		$post_link = '/posts/'.page_filename(preg_replace('/^\/?posts\/?/s', '', preg_replace('/-[0-9]*$/s', '', $link, 1)));
		// make sure to have the post_id as suffix; this will make the link unique (hopefully...)
		if(substr_compare($post_link,$post_id,-(strlen($post_id)),strlen($post_id))!=0) {
		    $post_link .= PAGE_SPACER.$post_id;
		}

		// Make sure the post link is set and exists
		// Make news post access files dir
		make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');
		$file_create_time = '';
		if (!is_writable(WB_PATH.PAGES_DIRECTORY.'/posts/')) {
		    $admin->print_error($MESSAGE['PAGES']['CANNOT_CREATE_ACCESS_FILE']);
		} else {
		    // Specify the filename
		    $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
		    mod_nwi_create_file($filename, $file_create_time);
		}


		if(!is_dir($mod_nwi_file_dir)) {
		    mod_nwi_img_makedir($mod_nwi_file_dir);
		}
		mod_nwi_img_copy(WB_PATH.MEDIA_DIRECTORY.'/.news_img/'.$post['post_id'],$mod_nwi_file_dir);

		// Update row
		$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `page_id` = '$page_id', `section_id` = '$section_id', `group_id` = '$group_id', `title` = '$title', `link` = '$post_link', `content_short` = '$short', `content_long` = '$long', `content_block2` = '$block2', `image` = '$image', `active` = '$active', `published_when` = '$publishedwhen', `published_until` = '$publisheduntil', `posted_when` = '".time()."', `posted_by` = '".$posted_by."' WHERE `post_id` = '$post_id'");
		if(!($database->is_error())){
		    //update table images
		   $database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_img` (`picname`, `picdesc`, `post_id`, `position`) SELECT `picname`, `picdesc`, '".$post_id."', `position` FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `post_id` = '".$post['post_id']."'");
		}
	    }
	}
    } 
}

if($database->is_error())
    {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id);
    } else {
        $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    }
    
// Print admin footer
$admin->print_footer();

} else if ($module_type == "news") {

// =========================================== Classical News ======================================

$query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_settings` WHERE `section_id` = '$source_id'");
$fetch_settings = $query_settings->fetchRow();


// Update settings
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_settings` SET ".
    "`header` = '".$database->escapeString($fetch_settings['header'])."', ".
    "`post_loop` = '".$database->escapeString($fetch_settings['post_loop'])."', ".
    "`view_order` = ".$original_settings['view_order'].", ".
    "`footer` = '".$database->escapeString($fetch_settings['footer'])."', ".
    "`block2` = '".$database->escapeString($original_settings['block2'])."', ".
    "`posts_per_page` = ".$fetch_settings['posts_per_page'].", ".
    "`post_header` = '".$database->escapeString($fetch_settings['post_header'])."', ".
    "`post_content` = '".$database->escapeString($original_settings['post_content'])."', ".
    "`image_loop` = '".$database->escapeString($original_settings['image_loop'])."', ".
    "`post_footer` = '".$database->escapeString($fetch_settings['post_footer'])."', ".
    "`resize_preview` = '".$database->escapeString($original_settings['resize_preview'])."', ".
    "`crop_preview` = '".$database->escapeString($original_settings['crop_preview'])."', ".
    "`gallery` = '".$database->escapeString($original_settings['gallery'])."', ".
    "`imgmaxsize` = '".$database->escapeString($original_settings['imgmaxsize'])."', ".
    "`imgmaxwidth` = '".$database->escapeString($original_settings['imgmaxwidth'])."', ".
    "`imgmaxheight` = '".$database->escapeString($original_settings['imgmaxheight'])."', ".
    "`imgthumbsize` = '".$database->escapeString($original_settings['imgthumbsize'])."' ".
    "WHERE `section_id` = '$section_id'");

if(!($database->is_error())){

    // Update row
    $database->query(  "INSERT INTO `".TABLE_PREFIX."mod_news_img_groups` (`section_id`,`page_id`,`active`,`position`,`title`) SELECT '".$section_id."', '".$page_id."', `active`,`position`,`title` FROM `".TABLE_PREFIX."mod_news_groups` WHERE `section_id` = '".$source_id."'");

    if(!($database->is_error())){

	// Include the ordering class
	require_once(WB_PATH.'/framework/class.order.php');
	// Create new order object and reorder
	$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
	$order->clean($source_id);
    
	$query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_posts` WHERE `section_id` = '$source_id' ORDER BY `post_id`");
	if($query_posts->numRows() > 0) {
    	    $num_posts = $query_posts->numRows();
    	    while($post = $query_posts->fetchRow()) {
		// Get new order
		$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
		$position = $order->get_new($section_id);

		// Insert new row into database
		$sql = "INSERT INTO `".TABLE_PREFIX."mod_news_img_posts` (`section_id`,`page_id`,`group_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) VALUES ('$section_id','$page_id','0','$position','','','','','0')";
		$database->query($sql);

		$post_id = $database->get_one("SELECT LAST_INSERT_ID()");

		$mod_nwi_file_dir = "$mod_nwi_file_base/$post_id/";
		$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";

		$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_posts` WHERE `post_id` = '".$post['post_id']."'");
		$fetch_content = $query_content->fetchRow();

		$title = $fetch_content['title'];
		$link = $fetch_content['link'];
		$group_id = $fetch_content['group_id'];
		if($group_id!=0){
		    // find out new group

		    $query_groups = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_groups` WHERE `section_id` = '$source_id' AND `group_id` = '$group_id' ORDER BY `position` ASC");
		    if($query_groups->numRows()==0){
			$group_id=0;
		    } else {
			$group_result=$query_groups->fetchRow();
			$group_title=$group_result['title'];
			$query_groups = $database->query("SELECT `group_id` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' AND `title` = '$group_title' ORDER BY `group_id` DESC");
			if($query_groups->numRows()==0){
			    $group_id=0;
			} else {
			    $group_result=$query_groups->fetchRow();
			    $group_id = $group_result['group_id'];
			}
		    }
		}
		$posted_by = $fetch_result['posted_by'];
		$short = $fetch_content['content_short'];
		$long = $fetch_content['content_long'];
		$block2 = '';
		$image = '';
		$active = $fetch_content['active'];
		$publishedwhen =  $fetch_content['published_when'];
		$publisheduntil =  $fetch_content['published_until'];

		// Get page link URL
		$query_page = $database->query("SELECT `level`,`link` FROM `".TABLE_PREFIX."pages` WHERE `page_id` = '$page_id'");
		$page = $query_page->fetchRow();
		$page_level = $page['level'];
		$page_link = $page['link'];

		// get old link
		$old_link = $link;

		// new link
		$post_link = '/posts/'.page_filename(preg_replace('/^\/?posts\/?/s', '', preg_replace('/-[0-9]*$/s', '', $link, 1)));
		// make sure to have the post_id as suffix; this will make the link unique (hopefully...)
		if(substr_compare($post_link,$post_id,-(strlen($post_id)),strlen($post_id))!=0) {
		    $post_link .= PAGE_SPACER.$post_id;
		}

		// Make sure the post link is set and exists
		// Make news post access files dir
		make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');
		$file_create_time = '';
		if (!is_writable(WB_PATH.PAGES_DIRECTORY.'/posts/')) {
		    $admin->print_error($MESSAGE['PAGES']['CANNOT_CREATE_ACCESS_FILE']);
		} else {
		    // Specify the filename
		    $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
		    mod_nwi_create_file($filename, $file_create_time);
		}

		if(!is_dir($mod_nwi_file_dir)) {
		    mod_nwi_img_makedir($mod_nwi_file_dir);
		}
		if(!is_dir($mod_nwi_thumb_dir)) {
		    mod_nwi_img_makedir($mod_nwi_thumb_dir);
		}
		$mod_nwi_file_dir = "$mod_nwi_file_base/";
		$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";
		if(!is_dir($mod_nwi_file_dir)) {
		    mod_nwi_img_makedir($mod_nwi_file_dir);
		}
		if(!is_dir($mod_nwi_thumb_dir)) {
		    mod_nwi_img_makedir($mod_nwi_thumb_dir);
		}

		mod_nwi_img_copy(WB_PATH.MEDIA_DIRECTORY.'/.news/image'.$fetch_content['group_id'].'.jpg',
			$mod_nwi_file_dir.'/image'.$group_id.'.jpg');
		mod_nwi_img_copy(WB_PATH.MEDIA_DIRECTORY.'/.news/thumb'.$fetch_content['group_id'].'.jpg',
			$mod_nwi_thumb_dir.'/image'.$group_id.'.jpg');
		
		// Update row
		$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `page_id` = '$page_id', `section_id` = '$section_id', `group_id` = '$group_id', `title` = '$title', `link` = '$post_link', `content_short` = '$short', `content_long` = '$long', `content_block2` = '$block2', `image` = '$image', `active` = '$active', `published_when` = '$publishedwhen', `published_until` = '$publisheduntil', `posted_when` = '".time()."', `posted_by` = '".$posted_by."' WHERE `post_id` = '$post_id'");
	    }
	}
    } 
}

if($database->is_error())
    {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id);
    } else {
        $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    }
    
// Print admin footer
$admin->print_footer();

} else if($module_type == "topics"){

    // ==================================================== topics ===============================================

$query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_".$topics_name."_settings` WHERE `section_id` = '$source_id'");
$fetch_settings = $query_settings->fetchRow();

$view_order = 0;
if(($fetch_settings['sort_topics']==1)||($fetch_settings['sort_topics']==3)) $view_order=$fetch_settings['sort_topics'];

// Placeholders
 $vars = array(
	 '[SECTION_ID]'			=> "",
	 '[SECTION_TITLE]'		=> "",
	 '[SECTION_DESCRIPTION]'	        => "",
	 '[TOPIC_ID]'			=> "",  
	 '[SHORT_DESCRIPTION]'	        => "", 
	 '[TOPIC_SHORT]'			=> "[SHORT]", 
	 '[META_DESCRIPTION]'		=> "", 
	 '[META_KEYWORDS]'		=> "",
	 '{SEE_ALSO}'			=> "",  
	 '{SEE_PREVNEXT}'		=> "",
	 '[BACK]'				=> "",
	 '[PICTURE_DIR]'			=> "",			
	 '[PICTURE]'				=> "", 
	 '{PICTURE}'				=>  "",
	 '{THUMB}'				=>  "",
	 '[ADDITIONAL_PICTURES]'	=>  "",
	 '{FULL_TOPICS_LIST}'	=> "",
	 '[XTRA1]'				=> '',
	 '[XTRA2]'				=> '',
	 '[XTRA3]'				=> '', 
	 '[CLASSES]'				=>  '',
	 '[COMMENTSCOUNT]'		=>   '',
	 '[COMMENTSCLASS]'		=>  '',
	 '[TOPIC_SCORE]'			=>   '',
	 '[EDITLINK]'			=>  '',
	 '[ACTIVE]'			=>  '',
	 '[USER_MODIFIEDINFO]'	=> '',
	 '[ALLCOMMENTSLIST]'		=>  '', 
	 '[COMMENTFRAME]'		=>  '',			
	 '[USER_DISPLAY_NAME]'	=> '[DISPLAY_NAME]', 
	 );
	
$fetch_settings=str_replace(array_keys($vars),array_values($vars),$fetch_settings);

// Update settings
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_settings` SET ".
    "`header` = '".$database->escapeString($fetch_settings['header'])."', ".
    "`post_loop` = '".$database->escapeString($fetch_settings['topics_loop'])."', ".
    "`view_order` = ".$view_order.", ".    
    "`footer` = '".$database->escapeString($fetch_settings['footer'])."', ".
    "`block2` = '".$database->escapeString($fetch_settings['topic_block2'])."', ".
    "`posts_per_page` = ".$fetch_settings['topics_per_page'].", ".
    "`post_header` = '".$database->escapeString($fetch_settings['topic_header'])."', ".
    "`post_content` = '".$database->escapeString($original_settings['post_content'])."', ".
    "`post_footer` = '".$database->escapeString($fetch_settings['topic_footer']).", ".
    "`resize_preview` = '".$database->escapeString($original_settings['resize_preview'])."', ".
    "`crop_preview` = '".$database->escapeString($original_settings['crop_preview'])."', ".
    "`gallery` = '".$database->escapeString($original_settings['gallery'])."', ".
    "`imgmaxsize` = '".$database->escapeString($original_settings['imgmaxsize'])."', ".
    "`imgmaxwidth` = '".$database->escapeString($original_settings['imgmaxwidth'])."', ".
    "`imgmaxheight` = '".$database->escapeString($original_settings['imgmaxheight'])."', ".
    "`imgthumbsize` = '".$database->escapeString($original_settings['imgthumbsize'])."' ".
    "WHERE `section_id` = '$section_id'");


// Include the ordering class
require_once(WB_PATH.'/framework/class.order.php');
// Create new order object and reorder
$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
$order->clean($source_id);
$query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_".$topics_name."` WHERE `section_id` = '$source_id' ORDER BY `topic_id`");
if($query_posts->numRows() > 0) {
    $num_posts = $query_posts->numRows();
    while($post = $query_posts->fetchRow()) {
// Get new order
	$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
	$position = $order->get_new($section_id);

	// Insert new row into database
	$sql = "INSERT INTO `".TABLE_PREFIX."mod_news_img_posts` (`section_id`,`page_id`,`group_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) VALUES ('$section_id','$page_id','0','$position','','','','','0')";
	$database->query($sql);

	$post_id = $database->get_one("SELECT LAST_INSERT_ID()");

	$mod_nwi_file_dir = "$mod_nwi_file_base/$post_id/";
	$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";

	$fetch_content = $post;

	$title = $fetch_content['title'];
	$link = $fetch_content['link'];
	$group_id = 0;
	$posted_by = $fetch_result['posted_by'];
	$short = $fetch_content['content_short'];
	$long = $fetch_content['content_long'];
	$block2 = '';
	$image = $fetch_content['picture'];
	$active = ($fetch_content['active']>3)?1:0;
	$publishedwhen =  $fetch_content['published_when'];
	$publisheduntil =  $fetch_content['published_until'];

	// Get page link URL
	$query_page = $database->query("SELECT `level`,`link` FROM `".TABLE_PREFIX."pages` WHERE `page_id` = '$page_id'");
	$page = $query_page->fetchRow();
	$page_level = $page['level'];
	$page_link = $page['link'];

	// get old link
	$old_link = $link;

	// new link
	$post_link = '/posts/'.page_filename(preg_replace('/^\/?posts\/?/s', '', preg_replace('/-[0-9]*$/s', '', $link, 1)));
	// make sure to have the post_id as suffix; this will make the link unique (hopefully...)
	if(substr_compare($post_link,$post_id,-(strlen($post_id)),strlen($post_id))!=0) {
	    $post_link .= PAGE_SPACER.$post_id;
	}

	// Make sure the post link is set and exists
	// Make news post access files dir
	make_dir(WB_PATH.PAGES_DIRECTORY.'/posts/');
	$file_create_time = '';
	if (!is_writable(WB_PATH.PAGES_DIRECTORY.'/posts/')) {
	    $admin->print_error($MESSAGE['PAGES']['CANNOT_CREATE_ACCESS_FILE']);
	} else {
	    // Specify the filename
	    $filename = WB_PATH.PAGES_DIRECTORY.'/'.$post_link.PAGE_EXTENSION;
	    mod_nwi_create_file($filename, $file_create_time);
	}

	if(!is_dir($mod_nwi_file_dir)) {
	    mod_nwi_img_makedir($mod_nwi_file_dir);
	}
	if(!is_dir($mod_nwi_thumb_dir)) {
	    mod_nwi_img_makedir($mod_nwi_thumb_dir);
	}
	$mod_nwi_file_dir = "$mod_nwi_file_base/$post_id/";
	$mod_nwi_thumb_dir = $mod_nwi_file_dir . "thumb/";
	if(!is_dir($mod_nwi_file_dir)) {
	    mod_nwi_img_makedir($mod_nwi_file_dir);
	}
	if(!is_dir($mod_nwi_thumb_dir)) {
	    mod_nwi_img_makedir($mod_nwi_thumb_dir);
	}

	mod_nwi_img_copy(WB_PATH.$fetch_settings['picture_dir'].'/'.$fetch_content['picture'],
		$mod_nwi_file_dir.'/'.$fetch_content['picture']);
	mod_nwi_img_copy(WB_PATH.$fetch_settings['picture_dir'].'/'.$fetch_content['picture'],
		$mod_nwi_thumb_dir.'/'.$fetch_content['picture']);
		

	// Update row
	$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `page_id` = '$page_id', `section_id` = '$section_id', `group_id` = '$group_id', `title` = '$title', `link` = '$post_link', `content_short` = '$short', `content_long` = '$long', `content_block2` = '$block2', `image` = '$image', `active` = '$active', `published_when` = '$publishedwhen', `published_until` = '$publisheduntil', `posted_when` = '".time()."', `posted_by` = '".$posted_by."' WHERE `post_id` = '$post_id'");
	$additional_picture_path = WB_PATH.$fetch_settings['picture_dir'].'/topic'.$fetch_content['topic_id'];
	if (is_dir($additional_picture_path)) {
	    $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'post_id', 'section_id');
	    $position = $order->get_new($section_id);

	    $additional_picture_url = WB_URL.$settings_fetch['picture_dir'].'/topic'.TOPIC_ID.'/';

	    $dir = $additional_picture_path . '/';
	    $extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp');

	    $pictures=scandir($additional_picture_path);
	    $directory = new DirectoryIterator($dir);
	    foreach ($pictures as $currfile) {
	        $currfilepath=$dir.'/'.$currfile;
        	if (is_file($currfilepath)) {
        	    $extension = strtolower(pathinfo($currfilepath, PATHINFO_EXTENSION));
        	    if (in_array($extension, $extensions)) {
		        // Get new order
                        $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'post_id', 'section_id');
                        $position = $order->get_new($section_id);
                        $database->query("INSERT INTO `".TABLE_PREFIX."mod_news_img_img` (`picname`, `picdesc`, `post_id`, `position`) VALUES ($currfile, '', '$post_id','$position'");
        	    }
        	}
	    }
	    
	}
    }
}

if($database->is_error())
    {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id);
    } else {
        $admin->print_success($TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
    }
    
// Print admin footer
$admin->print_footer();


} else {

    // =========================================== unsupported section type ======================================

    $admin->print_error("unsupported section type", WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id);
    
    // Print admin footer
    $admin->print_footer();
}
