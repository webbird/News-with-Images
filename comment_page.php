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

// Make sure page cannot be accessed directly
if(!defined('WB_URL')) {
	header('Location: ../index.php');
	exit(0);
}

// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php'))
{
	// no module language file exists for the language set by the user, include default module language file EN.php
	require WB_PATH .'/modules/news_img/languages/EN.php';
}
else
{
	// a module language file exists for the language defined by the user, load it
	require WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php';
}

require_once WB_PATH.'/include/captcha/captcha.php';

// Get comments page template details from db
$query_settings = $database->query("SELECT `comments_page`,`use_captcha`,`commenting` FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '".SECTION_ID."'");
if($query_settings->numRows() == 0)
{
	header("Location: ".WB_URL.PAGES_DIRECTORY."");
	exit( 0 );
}
else
{
	$settings = $query_settings->fetchRow();

	// Print comments page
	$vars = array('[POST_TITLE]','[TEXT_COMMENT]');
	$values = array(POST_TITLE, $MOD_NEWS['TEXT_COMMENT']);
	echo str_replace($vars, $values, ($settings['comments_page']));
	?>
	<form name="comment" action="<?php echo WB_URL.'/modules/news_img/submit_comment.php?page_id='.PAGE_ID.'&amp;section_id='.SECTION_ID.'&amp;post_id='.POST_ID; ?>" method="post">
	<?php if(ENABLED_ASP) { // add some honeypot-fields
	?>
	<input type="hidden" name="submitted_when" value="<?php $t=time(); echo $t; $_SESSION['submitted_when']=$t; ?>" />
	<p class="nixhier">
	email address:
	<label for="email">Leave this field email blank:</label>
	<input id="email" name="email" size="60" value="" /><br />
	Homepage:
	<label for="homepage">Leave this field homepage blank:</label>
	<input id="homepage" name="homepage" size="60" value="" /><br />
	URL:
	<label for="url">Leave this field url blank:</label>
	<input id="url" name="url" size="60" value="" /><br />
	Comment:
	<label for="comment">Leave this field comment blank:</label>
	<input id="comment" name="comment" size="60" value="" /><br />
	</p>
	<?php }
	?>
	<?php echo $TEXT['TITLE']; ?>:
	<br />
	<input type="text" name="title" maxlength="255" style="width: 90%;"<?php if(isset($_SESSION['comment_title'])) { echo ' value="'.$_SESSION['comment_title'].'"'; unset($_SESSION['comment_title']); } ?> />
	<br /><br />
	<?php echo $TEXT['COMMENT']; 
	?>:
	<br />
	<?php if(ENABLED_ASP) { ?>
		<textarea name="comment_<?php echo date('W'); ?>" rows="10" cols="1" style="width: 90%; height: 150px;"><?php if(isset($_SESSION['comment_body'])) { echo $_SESSION['comment_body']; unset($_SESSION['comment_body']); } ?></textarea>
	<?php } else { ?>
		<textarea name="comment" rows="10" cols="1" style="width: 90%; height: 150px;"><?php if(isset($_SESSION['comment_body'])) { echo $_SESSION['comment_body']; unset($_SESSION['comment_body']); } ?></textarea>
	<?php } ?>
	<br /><br />
	<?php
	if(isset($_SESSION['captcha_error'])) {
		echo '<font color="#FF0000">'.$_SESSION['captcha_error'].'</font><br />';
		$_SESSION['captcha_retry_news'] = true;
	}
	// Captcha
	if($settings['use_captcha']) {
	?>
	<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><?php echo $TEXT['VERIFICATION']; ?>:</td>
		<td><?php call_captcha(); ?></td>
	</tr>
    </table>
	<?php
	if(isset($_SESSION['captcha_error'])) {
		unset($_SESSION['captcha_error']);
		?><script>document.comment.captcha.focus();</script><?php
	}?>
	<?php
	}
	?>
	<table class="news-table">
	<tr>
	    <td>
            <input type="submit" name="submit" value="<?php echo $MOD_NEWS['TEXT_ADD_COMMENT']; ?>" />
        </td>
        <td>
		    <input type="button" value="<?php echo $TEXT['CANCEL']; ?>" onclick="history.go(-1)"  />
        </td>
	</tr>
    </table>
	</form>
	<?php
}

?>