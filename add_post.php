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

require_once __DIR__.'/functions.inc.php';

// Include WB admin wrapper script
require WB_PATH.'/modules/admin.php';

// Include the ordering class
require WB_PATH.'/framework/class.order.php';
// Get new order
$order = new order(TABLE_PREFIX.'mod_news_img_posts', 'position', 'post_id', 'section_id');
$position = $order->get_new($section_id);

// Insert new row into database
$sql = "INSERT INTO `".TABLE_PREFIX."mod_news_img_posts` (`section_id`,`page_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) VALUES ('$section_id','$page_id','$position','','','','','1')";
$database->query($sql);

// Say that a new record has been added, then redirect to modify page
if($database->is_error()) {
	$admin->print_error($database->get_error(), WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id);
} else {
    // Get the id
    $post_id = $database->get_one("SELECT LAST_INSERT_ID()");

?>
<h2><?php echo $TEXT['ADD'].'/'.$TEXT['MODIFY'].' '.$TEXT['POST']; ?></h2>
<form name="modify" action="<?php echo WB_URL; ?>/modules/news_img/copy_post.php" method="post" style="margin: 0;" enctype="multipart/form-data">

<input type="hidden" name="section_id" value="<?php echo $section_id; ?>" />
<input type="hidden" name="page_id" value="<?php echo $page_id; ?>" />
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
<input type="hidden" name="link" value="<?php echo $fetch_content['link']; ?>" />
<input type="hidden" name="savegoback" id="savegoback" value="" />

<table class="row_a" cellpadding="2" cellspacing="0" width="100%">
<tr>
    <td><?php echo $MOD_NEWS_IMG['ADD_POST'] ?>:</td>
    <td>
        <select name="original_post_id" style="width: 100%;">
            <?php
            echo '<option value="0" selected="selected">'.$MOD_NEWS_IMG['NEW_POST']."</option>";
            $query = $database->query("SELECT `post_id`,`title`,`section_id` FROM `".TABLE_PREFIX."mod_news_img_posts`"
	        . " ORDER BY `page_id`,`position` ASC");
            if($query->numRows() > 0) {
                // Loop through posts
                while($post = $query->fetchRow()) {
		    if(intval($post['post_id']>0)&&($post['post_id']!=$post_id)&&($post['section_id']>0))
                        echo '<option value="'.$post['post_id'].'">'.$MOD_NEWS_IMG['COPY_POST'].': '.$post['title'].' ('.$post['post_id'].')</option>';
                }
            }
            ?>
        </select>
    </td>
</tr>

</table>

<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
	<td align="left">
		<input name="save" type="submit" value="<?php echo $TEXT['SAVE']; ?>" style="width: 100px; margin-top: 5px;" />
	</td>
	<td align="right">
		<input type="button" value="<?php echo $MOD_NEWS_IMG['GOBACK'] ?>" onclick="javascript: window.location = '<?php echo ADMIN_URL; ?>/pages/modify.php?page_id=<?php echo $page_id; ?>';" style="width: 100px; margin-top: 5px;" />
	</td>
</tr>
</table>
</form>

	<?php
}

// Print admin footer
$admin->print_footer();
