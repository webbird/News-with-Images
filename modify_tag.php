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
require(WB_PATH.'/modules/admin.php');
$tag_id = $admin->checkIDKEY('tag_id', 0, 'GET');
if (!$tag_id){
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (IDKEY) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
}

$FTAN = $admin->getFTAN();

// Get header and footer
$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_tags` WHERE `tag_id` = '$tag_id'");
$fetch_content = $query_content->fetchRow();

?>

<div class="mod_news_img">
    <h2><?php echo $TEXT['MODIFY'].' '.$MOD_NEWS_IMG['TAG']; ?></h2>

    <form name="modify" action="<?php echo WB_URL; ?>/modules/news_img/save_tag.php" method="post" enctype="multipart/form-data" style="margin: 0;">
    <?php echo $FTAN; ?>
    <input type="hidden" name="section_id" value="<?php echo $section_id; ?>" />
    <input type="hidden" name="page_id" value="<?php echo $page_id; ?>" />
    <input type="hidden" name="tag_id" value="<?php echo $tag_id; ?>" />

    <table>
    <tr>
    	<td style="width:80px"><?php echo $MOD_NEWS_IMG['TAG']; ?>:</td>
    	<td colspan="2">
    		<input type="text" name="tag" value="<?php echo (htmlspecialchars($fetch_content['tag'])); ?>" style="width: 98%;" maxlength="255" />
    	</td>
    </tr>
    </table>

    <table>
    <tr>
    	<td align="left">
    		<input name="save" type="submit" value="<?php echo $TEXT['SAVE']; ?>" style="width: 100px; margin-top: 5px;" />
    	</td>
    	<td align="right">
    		<input type="button" value="<?php echo $TEXT['CANCEL']; ?>" onclick="javascript: window.location = '<?php echo ADMIN_URL; ?>/pages/modify.php?page_id=<?php echo $page_id; ?>';" style="width: 100px; margin-top: 5px;" />
    	</td>
    </tr>
    </table>
    </form>
</div>
<?php

// Print admin footer
$admin->print_footer();

