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
if(!defined('WB_PATH')) { exit("Cannot access this file directly"); }

require_once __DIR__.'/functions.inc.php';

$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_posts`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_groups`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");

//overwrite php.ini on Apache servers for valid SESSION ID Separator
if(function_exists('ini_set')) {
	ini_set('arg_separator.output', '&amp;');
}

?>
<div class="mod_news_img">
    <input type="button" class="mod_img_news_add" value="<?php echo $MOD_NEWS_IMG['ADD_POST']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <input  class="mod_img_news_options" type="button" value="<?php echo $MOD_NEWS_IMG['OPTIONS']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/modify_settings.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <input  class="mod_img_news_add_group" type="button" value="<?php echo $MOD_NEWS_IMG['ADD_GROUP']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';"  />
    <br />

    <h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['POST']; ?></h2>

<?php
    // Get settings
    $query_settings = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
    if($query_settings->numRows() > 0)
    {
        $fetch_settings = $query_settings->fetchRow();
        $setting_view_order = ($fetch_settings['view_order']);
    } else {
	$setting_view_order = 0;
    }
    
    $order_by = "position";
    if($setting_view_order==1) $order_by = "published_when"; 
    if($setting_view_order==2) $order_by = "published_until"; 
    if($setting_view_order==3) $order_by = "posted_when"; 
    if($setting_view_order==4) $order_by = "post_id"; 

    // map order to lang string
    $lang_map = array(
        0 => $TEXT['CUSTOM'],
        1 => $TEXT['PUBL_START_DATE'],
        2 => $TEXT['PUBL_END_DATE'],
        3 => $TEXT['SUBMITTED'],
        4 => $TEXT['SUBMISSION_ID']
    );
?>

    <div style="text-align:right;font-style:italic"><?php echo $MOD_NEWS_IMG['ORDERBY'], ": <span class=\"\" title=\"", $MOD_NEWS_IMG['ORDER_CUSTOM_INFO'] ,"\">", $lang_map[$setting_view_order] ?></span></div>

<?php
    // Loop through existing posts

// Include the ordering class
require_once(WB_PATH.'/framework/class.order.php');
// Create new order object and reorder
$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
$order->clean($section_id);
    
    $query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `section_id` = '$section_id' ORDER BY `$order_by` DESC");
    if($query_posts->numRows() > 0) {
    	$num_posts = $query_posts->numRows();
?>
    	<table class="striped dragdrop_form">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>PostID</th>
                    <th><?php echo $TEXT['TITLE'] ?></th>
                    <th><?php echo $TEXT['GROUP'] ?></th>
                    <th><?php echo $TEXT['ACTIVE'] ?></th>
                    <th><?php echo $TEXT['PUBL_START_DATE']; ?></th>
                    <th><?php echo $TEXT['PUBL_END_DATE']; ?></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
    	<?php
    	while($post = $query_posts->fetchRow()) {
    		?>
    		<tr id="post_id:<?php echo  $admin->getIDKEY($post['post_id']); ?>">
			<td <?php if($setting_view_order == 0) echo 'class="dragdrop_item"';?>>&nbsp;</td>
    			<td>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
    				</a>
    			</td>
                <td>
                    <span title="Post ID"><?php echo $post['post_id'] ?></span>
                </td>
    			<td>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>">
    					<?php echo $post['title']; ?>
    				</a>
    			</td>
    			<td><?php
    				// Get group title
    				$query_title = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `group_id` = '".$post['group_id']."'");
    				if($query_title->numRows() > 0) {
    					$fetch_title = $query_title->fetchRow();
    					echo ($fetch_title['title']);
    				} else {
    					echo $TEXT['NONE'];
    				}
    				?>
    			</td>
    			<td>
    				<?php if($post['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
    			</td>
    			<td>
<?php
	$start = $post['published_when'];
	$end = $post['published_until'];
	$t = time();
	$icon = '';
	if($start<=$t && $end==0) {
        $icon='<span class="mod_news_img_icon"></span>';
    }
	elseif(($start<=$t || $start==0) && $end>=$t) {
		$icon='<img src="'.THEME_URL.'/images/clock_16.png" class="mod_news_img_icon" alt="Clock image" />';
    }
	else {
		$icon='<img src="'.THEME_URL.'/images/clock_red_16.png" class="mod_news_img_icon" title="'.$MOD_NEWS_IMG['EXPIRED_NOTE'].'" alt="Clock image" />';
    }
?>
                    <?php echo ( $start>0 ? date(DATE_FORMAT.' '.TIME_FORMAT, $start) : '') ?></td>
                <td><?php echo ( $end>0   ? date(DATE_FORMAT.' '.TIME_FORMAT, $end)   : '') ?></td>
    		<td style="text-align:right"><?php echo $icon ?>
<?php
    // Icons
/*  disable due to drag&drop
    if(($post['position'] != $num_posts)&&($setting_view_order == 0)) {
?>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" class="mod_news_img_icon" />
    				</a>
<?php } else {
*/
    echo '<span class="mod_news_img_icon"></span>';
/*  disable due to drag&drop
}
    if(($post['position'] != 1)&&($setting_view_order == 0)) { ?>
    				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" class="mod_news_img_icon" />
    				</a>
<?php } else {
*/
    echo '<span class="mod_news_img_icon"></span>';
//}
?>
    				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
    					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" class="mod_news_img_icon" />
    				</a>
    			</td>
			<td <?php if($setting_view_order == 0) echo 'class="dragdrop_item"';?>>&nbsp;</td>
    		</tr>
<?php
	}
?>
        </tbody>
        </table>

<script type="text/javascript">
        var LOAD_DRAGDROP = true;
        var ICONS = '<?php echo WB_URL."/modules/news_img/images" ?>';
</script>
	
<?php
} else {
	echo $TEXT['NONE_FOUND'];
}

?>

    <h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['GROUP']; ?></h2>

<?php
$order = new order(TABLE_PREFIX.'mod_news_img_groups', 'position', 'group_id', 'section_id');
$order->clean($section_id);

// Loop through existing groups
$query_groups = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' ORDER BY `position` ASC");
if($query_groups->numRows() > 0) {
	$num_groups = $query_groups->numRows();
	?>
    	<table class="striped dragdrop_form">
	<?php
	while($group = $query_groups->fetchRow()) {
		?>
    		<tr id="group_id:<?php echo  $admin->getIDKEY( $group['group_id']); ?>">
			<td class="dragdrop_item">&nbsp;</td>
			<td style="width:20px">
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
				</a>
			</td>		
			<td>
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>">
					<?php echo $group['title'].' (ID: '.$group['group_id'].')'; ?>
				</a>
			</td>
			<td style="width:150px">
				<?php echo $TEXT['ACTIVE'].': '; if($group['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
			</td>
<?php /* disabled due to drag&drop
			<td style="width:20px">
			<?php if($group['position'] != 1) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" />
				</a>
			<?php } ?>
			</td>
			<td style="width:20px">
			<?php if($group['position'] != $num_groups) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" />
				</a>
			<?php } ?>
			</td>
*/ ?>
			<td style="width:20px">
				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_group.php?page_id=<?php echo $page_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" />
				</a>
			</td>
			<td class="dragdrop_item">&nbsp;</td>
		</tr>
<?php
	}
?>
	</table>
	<?php
} else {
	echo $TEXT['NONE_FOUND'];
}
