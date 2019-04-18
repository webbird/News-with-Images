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

require '../../config.php';

$file_dir = MEDIA_DIRECTORY.'/news_img/';
$thumb_dir = MEDIA_DIRECTORY.'/news_img/thumb/';

// Get id
if(!isset($_GET['post_id']) OR !is_numeric($_GET['post_id'])) {
	header("Location: ".ADMIN_URL."/pages/index.php");
	exit(0);
} else {
	$post_id = $_GET['post_id'];
}

// Include WB admin wrapper script
require WB_PATH.'/modules/admin.php';

// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php')) {
	// no module language file exists for the language set by the user, include default module language file EN.php
	require_once WB_PATH .'/modules/news_img/languages/EN.php';
} else {
	// a module language file exists for the language defined by the user, load it
	require_once WB_PATH .'/modules/news_img/languages/'.LANGUAGE .'.php';
}

// delete image
if(isset ($_GET['img_id'])) {
  $img_id = $_GET['img_id'];
  $query_img=$database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `id` = '$img_id'");
  $row = $query_img->fetchRow();
 
  if(!$row) {
    echo "Datei existiert nicht!";
  }
  else {
    unlink (WB_PATH.$file_dir.$row['bildname']);  
    unlink (WB_PATH.$thumb_dir.'thumb_'.$row['bildname']);  
  } 
  
  $database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `id` = '$img_id'");
  
}   //end delete

// delete previewimage
if(isset ($_GET['post_img'])) {
    $post_img = $_GET['post_img'];
    $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `image` = '' WHERE `post_id` = '$post_id'");
    unlink (WB_PATH.$file_dir.$post_img); 
}   //end delete  preview

// re-order images
// 2014-04-10 by BlackBird Webprogrammierung
if(isset($_GET['id']) && ( isset($_GET['up']) || isset($_GET['down']) ) ) {
    require WB_PATH.'/framework/class.order.php';
    $order = new order(TABLE_PREFIX.'mod_news_img_img', 'position', 'id', 'post_id');
    if(isset($_GET['up'])) {
        $order->move_up($_GET['id']);
    } else {
        $order->move_down($_GET['id']);
    }
}
// 2014-04-10 ---end---

// Get header and footer
$query_content = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_posts` WHERE `post_id` = '$post_id'");
$fetch_content = $query_content->fetchRow();

if (!defined('WYSIWYG_EDITOR') OR WYSIWYG_EDITOR=="none" OR !file_exists(WB_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php')) {
	function show_wysiwyg_editor($name,$id,$content,$width,$height) {
		echo '<textarea name="'.$name.'" id="'.$id.'" rows="10" cols="1" style="width: '.$width.'; height: '.$height.';">'.$content.'</textarea>';
	}
} else {
	$id_list=array("short","long","block2");
	require(WB_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php');
}

// include jscalendar-setup
$jscal_use_time = true; // whether to use a clock, too
require_once(WB_PATH."/include/jscalendar/wb-setup.php");
?>
<h2><?php echo $TEXT['ADD'].'/'.$TEXT['MODIFY'].' '.$TEXT['POST']; ?></h2>
<div class="jsadmin jcalendar hide"></div> 
<form name="modify" action="<?php echo WB_URL; ?>/modules/news_img/save_post.php" method="post" style="margin: 0;" enctype="multipart/form-data">

<input type="hidden" name="section_id" value="<?php echo $section_id; ?>" />
<input type="hidden" name="page_id" value="<?php echo $page_id; ?>" />
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
<input type="hidden" name="link" value="<?php echo $fetch_content['link']; ?>" />
<input type="hidden" name="savegoback" id="savegoback" value="" />

<table class="row_a" cellpadding="2" cellspacing="0" width="100%">
<tr>
	<td><?php echo $TEXT['TITLE']; ?>:</td>
	<td width="80%">
		<input type="text" name="title" value="<?php echo (htmlspecialchars($fetch_content['title'])); ?>" style="width: 98%;" maxlength="255" />
	</td>
</tr>
<tr>
	<td><?php echo $MOD_NEWS_IMG['PREVIEWIMAGE']; ?>:</td>
	<td width="80%">
	   <?php
	     if ($fetch_content['image'] != "") {
           echo '<img class="img_list" src="'.WB_URL.$file_dir.$fetch_content['image'].'" /> '.$fetch_content['image'].' <a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id.'&post_img='.$fetch_content['image'].'">l&ouml;schen</a>';
           echo '<input type="hidden" name="previewimage" value="'.$fetch_content['image'].'" />';
       } else {
           echo '<input type="file" name="postfoto" accept="image/*" />  <br />';
       }
	  ?>
		</td>
</tr>
<tr>
    <td><?php echo $TEXT['GROUP']; ?>:</td>
    <td>
        <select name="group" style="width: 100%;">
            <?php
	    // We encode the group_id, section_id and page_id into an urlencoded serialized array.
	    // So we have a single string that we can submit safely and decode it when receiving.
            echo '<option value="'.urlencode(serialize(array('g' => 0, 's' => $section_id, 'p' => $page_id))).'">'
	        . $TEXT['NONE']." (".$TEXT['CURRENT']." ".$TEXT['SECTION']." ".$section_id.")</option>";
            $query = $database->query("SELECT `group_id`,`title` FROM `".TABLE_PREFIX."mod_news_img_groups`"
	        . " WHERE `section_id` = '$section_id' ORDER BY `position` ASC");
            if($query->numRows() > 0) {
                // Loop through groups
                while($group = $query->fetchRow()) {
                    echo '<option value="'
		        . urlencode(serialize(array('g' => intval($group['group_id']), 's' => $section_id, 'p' => $page_id))).'"';
                    if($fetch_content['group_id'] == $group['group_id']) { 
                        echo ' selected="selected"'; 
                    }
                    echo '>'.$group['title'].' ('.$TEXT['CURRENT']." ".$TEXT['SECTION'].' '.$section_id.')</option>';
                }
            }
            // this was just assignment to a group within the local section. Let's find out which sections exist
	    // and offer to move the post to another news_img section
            $query_sections = $database->query("SELECT `section_id`,`page_id` FROM `".TABLE_PREFIX."mod_news_img_settings`"
	        . " WHERE `section_id` != '$section_id' ORDER BY `page_id`,`section_id` ASC");
            $pid = $page_id;
            if($query_sections->numRows() > 0) {
                // Loop through all news_img sections, do sanity checks and filter out the current section which is handled above
                while($sect = $query_sections->fetchRow()) {
                    if($sect['section_id'] != $section_id){
                        if($sect['page_id'] != $pid){ // for new pages insert a separator
                            $pid = intval($sect['page_id']);
                            $page_title = "";
                            $page_details = "";
			    if($pid != 0){ // find out the page title and print separator line
				$page_details = $admin->get_page_details($pid);
                        	if (!empty($page_details)){
                                    $page_title=isset($page_details['page_title'])?$page_details['page_title']:"";
                        	    echo '<option disabled value="0">'
                        	    .'[--- '.$TEXT['PAGE'].' '.$pid.' ('.$page_title.') ---]</option>';
                        	}
			    }
                	}
			if($pid != 0){  
        		    echo '<option value="'.urlencode(serialize(array('g' => 0, 's' => $sect['section_id'], 'p' => $pid))).'">'
	        	       . $TEXT['NONE']." (".$TEXT['SECTION']." ".$sect['section_id'].")</option>";
                	    // now loop through groups of this section, at least for the ones which are not dummy sections
                	    $query_groups = $database->query("SELECT `group_id`,`title` FROM `".TABLE_PREFIX."mod_news_img_groups`"
		        	. " WHERE `section_id` = '".intval($sect['section_id'])."' ORDER BY `position` ASC");
                	    if($query_groups->numRows() > 0) {
                        	// Loop through groups
                        	while($group = $query_groups->fetchRow()) {
                        	    echo '<option value="'
			        	. urlencode(serialize(array(
					    'g' => intval($group['group_id']), 
					    's' => intval($sect['section_id']), 
					    'p' => $pid)))
					.'">'.$group['title'].' ('.$TEXT['SECTION'].' '.$sect['section_id'].')</option>';
                        	}
                            }
			}
		    }
                }
            }
            ?>
        </select>
    </td>
</tr>
<tr>
	<td><?php echo $TEXT['COMMENTING']; ?>:</td>
	<td>
		<select name="commenting" style="width: 100%;">
			<option value="none"><?php echo $TEXT['DISABLED']; ?></option>
			<option value="public" <?php if($fetch_content['commenting'] == 'public') { echo ' selected="selected"'; } ?>><?php echo $TEXT['PUBLIC']; ?></option>
			<option value="private" <?php if($fetch_content['commenting'] == 'private') { echo ' selected="selected"'; } ?>><?php echo $TEXT['PRIVATE']; ?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?php echo $TEXT['ACTIVE']; ?>:</td>
	<td>
		<input type="radio" name="active" id="active_true" value="1" <?php if($fetch_content['active'] == 1) { echo ' checked="checked"'; } ?> />
		<a href="#" onclick="javascript: document.getElementById('active_true').checked = true;">
		<?php echo $TEXT['YES']; ?>
		</a>
		&nbsp;
		<input type="radio" name="active" id="active_false" value="0" <?php if($fetch_content['active'] == 0) { echo ' checked="checked"'; } ?> />
		<a href="#" onclick="javascript: document.getElementById('active_false').checked = true;">
		<?php echo $TEXT['NO']; ?>
		</a>
	</td>
</tr>
<tr>
	<td><?php echo $TEXT['PUBL_START_DATE']; ?>:</td>
	<td>
	<input type="text" id="publishdate" name="publishdate" value="<?php if($fetch_content['published_when']==0) print date($jscal_format, strtotime((date('Y-m-d H:i')))); else print date($jscal_format, $fetch_content['published_when']);?>" style="width: 120px;" />
	<img src="<?php echo THEME_URL ?>/images/clock_16.png" id="publishdate_trigger" style="cursor: pointer;" title="<?php echo $TEXT['CALENDAR']; ?>" alt="<?php echo $TEXT['CALENDAR']; ?>" onmouseover="this.style.background='lightgrey';" onmouseout="this.style.background=''" />
	<img src="<?php echo THEME_URL ?>/images/clock_del_16.png" style="cursor: pointer;" title="<?php echo $TEXT['DELETE_DATE']; ?>" alt="<?php echo $TEXT['DELETE_DATE']; ?>" onmouseover="this.style.background='lightgrey';" onmouseout="this.style.background=''" onclick="document.modify.publishdate.value=''" />
	</td>
</tr>
<tr>
	<td><?php echo $TEXT['PUBL_END_DATE']; ?>:</td>
	<td>
	<input type="text" id="enddate" name="enddate" value="<?php if($fetch_content['published_until']==0) print ""; else print date($jscal_format, $fetch_content['published_until'])?>" style="width: 120px;" />
	<img src="<?php echo THEME_URL ?>/images/clock_16.png" id="enddate_trigger" style="cursor: pointer;" title="<?php echo $TEXT['CALENDAR']; ?>" alt="<?php echo $TEXT['CALENDAR']; ?>" onmouseover="this.style.background='lightgrey';" onmouseout="this.style.background=''" />
	<img src="<?php echo THEME_URL ?>/images/clock_del_16.png" style="cursor: pointer;" title="<?php echo $TEXT['DELETE_DATE']; ?>" alt="<?php echo $TEXT['DELETE_DATE']; ?>" onmouseover="this.style.background='lightgrey';" onmouseout="this.style.background=''" onclick="document.modify.enddate.value=''" />
	</td>
</tr>
</table>

<table class="row_a" cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
	<td valign="top"><?php echo $TEXT['SHORT']; ?>:</td>
</tr>
<tr>
	<td>
	<?php
	show_wysiwyg_editor("short","short",htmlspecialchars($fetch_content['content_short']),"100%","350px");
	?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo $TEXT['LONG']; ?>:</td>
</tr>
<tr>
	<td>
	<?php
	show_wysiwyg_editor("long","long",htmlspecialchars($fetch_content['content_long']),"100%","650px");
	?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo $TEXT['BLOCK']; ?> 2:</td>
</tr>
<tr>
	<td>
	<?php
	show_wysiwyg_editor("block2","block2",htmlspecialchars($fetch_content['content_block2']),"100%","350px");
	?>
	</td>
</tr>
</table>

<?php


//show all images
// 2014-04-10 by BlackBird Webprogrammierung: added position to sort order
$query_img = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_img` WHERE `post_id` = ".$post_id." ORDER BY `position`,`id` ASC");

if($query_img->numRows() > 0) {
    echo '<div id="fotoshow"><a name="fs"></a><h3>'.$MOD_NEWS_IMG['GALLERYIMAGES'].'</h3><table><tbody>';
    $i=1;

    // 2014-04-10 by BlackBird Webprogrammierung:
    // added up/down links
    $first=true;
    $last=$query_img->numRows();

    while($row = $query_img->fetchRow()) {
        $up='<span style="display:inline-block;width:20px;"></span>';
        $down=$up;
        if(!$first) {
            $up = '<a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id.'&id='.$row['id'].'&up=1">'
                . '<img src="'.THEME_URL.'/images/up_16.png" /></a>';
        }
        if($i!=$last) {
            $down = '<a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id.'&id='.$row['id'].'&down=1">'
                  . '<img src="'.THEME_URL.'/images/down_16.png" /></a>';
        }
        echo '<tr><td>'.$up.$down.'</td>',
             '<td width="100"><a href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id.'" onmouseover="XBT(this, {id:\'tt'.$i.'\'})"><img class="img_list" src="'.WB_URL.$thumb_dir.'thumb_'.$row["bildname"].'" /></a><div id="tt'.$i.'" class="xbtooltip"><img src="'.WB_URL.$file_dir.$row["bildname"].'" /></div></td>',
             '<td>'.$row["bildname"].'<br /><input type="text" name="bildbeschreibung['.$row["id"].']" value="'.$row["bildbeschreibung"].'"></td>',
             '<td><a onclick="return confirm(\''.$MOD_NEWS_IMG['DELETEIMAGE'].'\')" href="'.WB_URL.'/modules/news_img/modify_post.php?page_id='.$page_id.'&section_id='.$section_id.'&post_id='.$post_id.'&img_id='.$row["id"].'#fs"><img src="'.THEME_URL.'/images/delete_16.png" /></a></td><tr>';
        $i++;
        $first=false;
    }
    echo '</tbody></table></div>';
} 
?>

<!-- Formular -->
<div id="fotos"><h3><?php echo $MOD_NEWS_IMG['IMAGEUPLOAD']?></h3>
      <input type="file" name="foto[]" accept="image/*" />  <br />
      <input type="file" name="foto[]" accept="image/*" />  <br />
      <input type="file" name="foto[]" accept="image/*" />  <br />
      <input type="file" name="foto[]" accept="image/*" />   <br />                                                           
      <input type="file" name="foto[]" accept="image/*" />   <br />
      <input type="file" name="foto[]" accept="image/*" />   <br />
</div>

<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
	<td align="left">
		<input name="save" type="submit" value="<?php echo $TEXT['SAVE']; ?>" style="width: 100px; margin-top: 5px;" />
		<input name="save" type="submit" onclick="document.getElementById('savegoback').value='1'" value="<?php echo $MOD_NEWS_IMG['SAVEGOBACK']; ?>" style="width: 200px; margin-top: 5px;" />
		
	</td>
	<td align="right">
		<input type="button" value="<?php echo $MOD_NEWS_IMG['GOBACK'] ?>" onclick="javascript: window.location = '<?php echo ADMIN_URL; ?>/pages/modify.php?page_id=<?php echo $page_id; ?>';" style="width: 100px; margin-top: 5px;" />
	</td>
</tr>
</table>
</form>

<script type="text/javascript">
    
    /* Cross-Browser Tooltip von Mathias Karstädt steht unter einer Creative Commons Namensnennung 3.0 Unported Lizenz.
  http://webmatze.de/ein-einfacher-cross-browser-tooltip-mit-javascript-und-css/ */
  (function(window, document, undefined){
    var XBTooltip = function( element, userConf, tooltip) {
      var config = {
        id: userConf.id|| undefined,
        className: userConf.className || undefined,
        x: userConf.x || 20,
        y: userConf.y || 20,
        text: userConf.text || undefined
      };
      var over = function(event) {
        tooltip.style.display = "block";
      },
      out = function(event) {
        tooltip.style.display = "none";
      },
      move = function(event) {
        event = event ? event : window.event;
        if ( event.pageX == null && event.clientX != null ) {
          var doc = document.documentElement, body = document.body;
          event.pageX = event.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0) - (doc && doc.clientLeft || body && body.clientLeft || 0);
          event.pageY = event.clientY + (doc && doc.scrollTop  || body && body.scrollTop  || 0) - (doc && doc.clientTop  || body && body.clientTop  || 0);
        }
        tooltip.style.top = (event.pageY+config.y) + "px";
        tooltip.style.left = (event.pageX+config.x) + "px";
      }
      if (tooltip === undefined && config.id) {
        tooltip = document.getElementById(config.id);
        if (tooltip) tooltip = tooltip.parentNode.removeChild(tooltip)
      }
      if (tooltip === undefined && config.text) {
        tooltip = document.createElement("div");
        if (config.id) tooltip.id= config.id;
        tooltip.innerHTML = config.text;
      }
      if (config.className) tooltip.className = config.className;
      tooltip = document.body.appendChild(tooltip);
      tooltip.style.position = "absolute";
      element.onmouseover = over;
      element.onmouseout = out;
      element.onmousemove = move;
      over();
    };
    window.XBTooltip = window.XBT = XBTooltip;
  })(this, this.document);
    
	Calendar.setup(
		{
			inputField  : "publishdate",
			ifFormat    : "<?php echo $jscal_ifformat ?>",
			button      : "publishdate_trigger",
			firstDay    : <?php echo $jscal_firstday ?>,
			<?php if(isset($jscal_use_time) && $jscal_use_time==TRUE)
            { ?>
				showsTime   : "true",
				timeFormat  : "24",
			<?php
            } ?>
			date        : "<?php echo $jscal_today ?>",
			range       : [1970, 2037],
			step        : 1
		}
	);
	Calendar.setup(
		{
			inputField  : "enddate",
			ifFormat    : "<?php echo $jscal_ifformat ?>",
			button      : "enddate_trigger",
			firstDay    : <?php echo $jscal_firstday ?>,
			<?php if(isset($jscal_use_time) && $jscal_use_time==TRUE)
            { ?>
				showsTime   : "true",
				timeFormat  : "24",
			<?php
            } ?>
			date        : "<?php echo $jscal_today ?>",
			range       : [1970, 2037],
			step        : 1
		}
	);

  
  </script>


<br />

<h2><?php echo $TEXT['MODIFY'].'/'.$TEXT['DELETE'].' '.$TEXT['COMMENT']; ?></h2>

<?php

// Loop through existing comments
$query_comments = $database->query("SELECT * FROM `".TABLE_PREFIX."mod_news_img_comments` WHERE `section_id` = '$section_id' AND `post_id` = '$post_id' ORDER BY `commented_when` DESC");
if($query_comments->numRows() > 0) {
	$row = 'a';
	?>
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<?php
	while($comment = $query_comments->fetchRow()) {
		?>
		<tr class="row_<?php echo $row; ?>" >
			<td width="20" style="padding-left: 5px;">
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_comment.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;comment_id=<?php echo $comment['comment_id']; ?>" title="<?php echo $TEXT['MODIFY']; ?>">
					<img src="<?php echo THEME_URL; ?>/images/modify_16.png" border="0" alt="^" />
				</a>
			</td>	
			<td>
				<a href="<?php echo WB_URL; ?>/modules/news_img/modify_comment.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;comment_id=<?php echo $comment['comment_id']; ?>">
					<?php echo $comment['title']; ?>
				</a>
			</td>
			<td width="20">
				<a href="javascript: confirm_link('<?php echo $TEXT['ARE_YOU_SURE']; ?>', '<?php echo WB_URL; ?>/modules/news_img/delete_comment.php?page_id=<?php echo $page_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;post_id=<?php echo $post_id; ?>&amp;comment_id=<?php echo $comment['comment_id']; ?>');" title="<?php echo $TEXT['DELETE']; ?>">
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


// Print admin footer
$admin->print_footer();
