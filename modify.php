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
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_posts`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_groups`  WHERE `page_id` = '$page_id' and `section_id` = '$section_id' and `title`=''");

//overwrite php.ini on Apache servers for valid SESSION ID Separator
if(function_exists('ini_set')) {
	ini_set('arg_separator.output', '&amp;');
}

?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
	<td align="left" width="33%">
		<input type="button" value="<?php echo $TEXT['ADD'].' '.$TEXT['POST']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';" style="width: 100%;" />
	</td>
	<td align="left" width="33%">
		<input type="button" value="<?php echo $TEXT['ADD'].' '.$TEXT['GROUP']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/add_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';" style="width: 100%;" />
	</td>
	<td align="right" width="33%">
		<input type="button" value="<?php echo $TEXT['SETTINGS']; ?>" onclick="javascript: window.location = '<?php echo WB_URL; ?>/modules/news_img/modify_settings.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>';" style="width: 100%;" />
	</td>
</tr>
</table>

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

// Loop through existing posts
$query_posts = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `section_id` = '$section_id' ORDER BY `$order_by` DESC");
if($query_posts->numRows() > 0) {
	$num_posts = $query_posts->numRows();
	$row = 'a';
	?>
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<?php
	while($post = $query_posts->fetchRow()) {
		?>
		<tr class="row_<?php echo $row; ?>">
			<td width="20" style="padding-left: 5px;">
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
				</a>
			</td>
			<td>
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>">
					<?php echo ($post['title']); ?>
				</a>
			</td>
			<td width="180">
				<?php echo $TEXT['GROUP'].': ';
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
			<td width="120">
				<?php echo $TEXT['COMMENTS'].': ';
				// Get number of comments
				$query_title = $database->query("SELECT `title` FROM `".TABLE_PREFIX."mod_news_img_comments` WHERE `post_id` = '".$post['post_id']."'");
				echo $query_title->numRows();
				?>
			</td>
			<td width="80">
				<?php echo $TEXT['ACTIVE'].': '; if($post['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
			</td>
			<td width="20">
			<?php
			$start = $post['published_when'];
			$end = $post['published_until'];
			$t = time();
			$icon = '';
			if($start<=$t && $end==0)
				$icon=THEME_URL.'/images/noclock_16.png';
			elseif(($start<=$t || $start==0) && $end>=$t)
				$icon=THEME_URL.'/images/clock_16.png';
			else
				$icon=THEME_URL.'/images/clock_red_16.png';
			?>
			<a href="<?php echo WB_URL; ?>/modules/news_img/modify_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
				<img src="<?php echo $icon; ?>" border="0" alt="" />
			</a>
			</td>
			<td width="20">
			<?php if(($post['position'] != $num_posts)&&($setting_view_order == 0)) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" />
				</a>
			<?php } ?>
			</td>
			<td width="20">
			<?php if(($post['position'] != 1)&&($setting_view_order == 0)) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" />
				</a>
			<?php } ?>
			</td>
			<td width="20">
				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_post.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post['post_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" />
				</a>
			</td>
		</tr>
		<?php
		// Alternate row color
		if($row == 'a') {
			$row = 'b';
		} else {
			$row = 'a';
		}
	}
	?>
	</table>
	<?php
} else {
	echo $TEXT['NONE_FOUND'];
}

?>

<h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['GROUP']; ?></h2>

<?php

// Loop through existing groups
$query_groups = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `section_id` = '$section_id' ORDER BY `position` ASC");
if($query_groups->numRows() > 0) {
	$num_groups = $query_groups->numRows();
	$row = 'a';
	?>
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<?php
	while($group = $query_groups->fetchRow()) {
		?>
		<tr class="row_<?php echo $row; ?>">
			<td width="20" style="padding-left: 5px;">
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="Modify - " />
				</a>
			</td>		
			<td>
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_group.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>">
					<?php echo $group['title']; ?>
				</a>
			</td>
			<td width="80">
				<?php echo $TEXT['ACTIVE'].': '; if($group['active'] == 1) { echo $TEXT['YES']; } else { echo $TEXT['NO']; } ?>
			</td>
			<td width="20">
			<?php if($group['position'] != 1) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_up.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_UP']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/up_16.png" border="0" alt="^" />
				</a>
			<?php } ?>
			</td>
			<td width="20">
			<?php if($group['position'] != $num_groups) { ?>
				<a href="<?php echo WB_URL; ?>/modules/news_img/move_down.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>" title="<?php echo $TEXT['MOVE_DOWN']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/down_16.png" border="0" alt="v" />
				</a>
			<?php } ?>
			</td>
			<td width="20">
				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_group.php?page_id=<?php echo $page_id; ?>&amp;group_id=<?php echo $group['group_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/delete_16.png" border="0" alt="X" />
				</a>
			</td>
		</tr>
		<?php
		// Alternate row color
		if($row == 'a') {
			$row = 'b';
		} else {
			$row = 'a';
		}
	}
	?>
	</table>
	<?php
} else {
	echo $TEXT['NONE_FOUND'];
}
