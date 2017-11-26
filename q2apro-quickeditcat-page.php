<?php

/*
   Plugin Name: Quick Edit
   Plugin URI: http://www.q2apro.com/plugins/quick-edit
   Plugin Description: Update all question titles and tags quickly on one page and save hours of time
   Plugin Version: 1.0
   Plugin Date: 2014-02-13
   Plugin Author: q2apro.com
   Plugin Author URI: http://www.q2apro.com
   Plugin Minimum Question2Answer Version: 1.5
   Plugin Update Check URI: http://www.q2apro.com/pluginupdate?id=20

   Licence: Copyright © q2apro.com - All rights reserved

 */

class q2apro_quickeditcat {

	var $directory;
	var $urltoroot;

	function load_module($directory, $urltoroot)
	{
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	// for display in admin interface under admin/pages
	function suggest_requests() 
	{	
		return array(
				array(
					'title' => 'Quick Categorize', // title of page
					'request' => 'quickeditcat', // request name
					'nav' => 'F', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				     ),
			    );
	}

	// for url query
	function match_request($request)
	{
		if ($request=='quickeditcat') {
			return true;
		}

		return false;
	}
function tags_selectspec($min)
                /*
                   Return the selectspec to retrieve all the tags and their ids having a min count of $min
                 */
        {
                $count=QA_DB_RETRIEVE_COMPLETE_TAGS;
                return array(
                                'columns' => array('word',  '^words.wordid as wordid'),
                                'source' => '^words JOIN (SELECT wordid FROM ^words WHERE tagcount>'.$min.' ORDER BY tagcount DESC) y ON ^words.wordid=y.wordid order by word',
                                'arguments' => null,
                                'arraykey' => 'word',
                                'arrayvalue' => 'word',
                                'sort' => 'word',
                            );
        }
                
	function tagform()
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';
		$mintags = 40;
                $tags=qa_db_single_select($this->tags_selectspec($mintags));
                $fields = array();
		 $fields[] = array(
                                'label' => 'Tag to fetch Post-IDs',
                                'type'=>'select',
                                'tags' => "id='tag_selector' name='tagstring' class='col' onchange='dosubmit(this)'",
                                'options' => $tags,
                                );

		 $ok = qa_get('ok')?qa_get('ok'):null;

                return array(
                                'ok' => ($ok && !isset($error)) ? $ok : null,

                                'fields' => $fields,
                                'tags' => " action='".qa_self_html()."' method='post'",
                                'title' => 'Select a tag to do Category Update',
                                'style' => 'wide',
                                'buttons' => array(
                                        array(
                                                'label' => 'Submit',
                                                'tags' => 'NAME="changesubmit" id="submit_button"',
                                                ' value' => 'Submit',
                                             ),
                                        ),
                            );

		
	}
	function process_request($request) {

		if(qa_opt('q2apro_quickedit_enabled')!=1) {
			$qa_content=qa_content_prepare();
			$qa_content['error'] = '<div>'.qa_lang_html('q2apro_quickedit_lang/plugin_disabled').'</div>';
			return $qa_content;
		}
		// return if permission level is not sufficient
		if(qa_user_permit_error('q2apro_quickedit_permission')) {
			$qa_content=qa_content_prepare();
			$qa_content['error'] = qa_lang_html('q2apro_quickedit_lang/access_forbidden');
			return $qa_content;
		}
		$userid = qa_get_logged_in_userid();
		$c = 2;
	$qa_content=qa_content_prepare();
		 if (qa_clicked('changesubmit'))
		{
		$tag = qa_post_text('tagstring');
			echo "tag = ".$tag;
			$query="select postid from ^posts where tags like '%".$tag."%' and categoryid = 62";
			$result = qa_db_query_sub($query);
			$postids = qa_db_read_all_values($result, true);
			$count = 0;
			foreach ($postids as $postid)
			{

				$updated = false;
				$query="select tags from ^posts where postid like #";
				$result = qa_db_query_sub($query, $postid);
				$fulltags = qa_db_read_one_value($result, true);
				$fulltagsarray = qa_tagstring_to_tags($fulltags);
				foreach ($fulltagsarray as $tagvalue)
				{
					if($tagvalue === $tag) continue;//skip the selection one. 

					$query = "select categoryid from ^categories where tags like $";
					$result = qa_db_query_sub($query, $tagvalue);
					$category = qa_db_read_one_value($result, true);
					if(!$category) continue;
					$updated = true;
					//echo "($postid, $category, $tagvalue, $userid)";
					qa_post_set_category($postid, $category, $userid);
					break;
				}
				if($updated) $count++;
				//	break;

			}
		$qa_content['custom'.++$c] = "<p>$count posts have been categorized successfully</p>";
		}
		/* start */
		qa_set_template('qp-quickeditcat-page');
		$qa_content['title'] = qa_lang_html('q2apro_quickedit_lang/page_title'); // page title

		// counter for custom html output

		$qa_content['custom'.++$c] = '<script type="text/javascript">
$(function()
                {
if (localStorage.getItem("tag_selector")) {
        $("#tag_selector option").eq(localStorage.getItem("tag_selector")).prop("selected", true);
    }
});

function dosubmit(e){ 
        localStorage.setItem("tag_selector", $("option:selected", e).index());
e.form.submit();}</script>';
		// do pagination
		$start = (int)qa_get('start'); // gets start value from URL
		$pagesize = 500; // items per page
		$count = qa_opt('cache_qcount'); // items total
		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, true); // last parameter is prevnext
		$tagstring='';
		if(isset($_GET['tagfilter']))
			$tagfilter = $_GET['tagfilter'];
		$tagfilter = qa_post_text('tagstring');
		if($tagfilter) $tagstring = " and a.tags like '%".$tagfilter."%' ";
		$tagstring .= " and a.tags NOT LIKE '' and a.categoryid = 62";
		
		$qa_content['form'] =  $this -> tagform();

		// query to get all posts according to pagination, ignore closed questions
		$queryAllPosts = qa_db_query_sub('SELECT postid,a.tags,a.title,a.content,format,b.title as category FROM `^posts` a, ^categories b
				WHERE `type` = "Q" and (a.categoryid=b.categoryid or a.categoryid is null)
				AND `closedbyid` IS NULL'. $tagstring.' 
				ORDER BY postid DESC
				LIMIT #,#
				', $start, $pagesize);

		// initiate output string
		$tagtable = '<table class="tagtable"> <thead> <tr> <th>No.</th><th>'.qa_lang_html('q2apro_quickedit_lang/th_postid').'</th> <th>'.qa_lang_html('q2apro_quickedit_lang/th_questiontitle').'</th><th>'.qa_lang_html('q2apro_quickedit_lang/th_postcategory').'</th> <th>'.qa_lang_html('q2apro_quickedit_lang/th_posttags').'</th> </tr></thead>';
		$maxlength = qa_opt('mouseover_content_max_len'); // 480

		require_once QA_INCLUDE_DIR.'qa-util-string.php'; // for qa_shorten_string_line()
		$blockwordspreg=qa_get_block_words_preg();

		while ( ($row = qa_db_read_one_assoc($queryAllPosts,true)) !== null ) {
			$text=qa_viewer_text($row['content'], $row['format'], array('blockwordspreg' => $blockwordspreg));
			$contentPreview = qa_html(qa_shorten_string_line($text, $maxlength));
			$tagtable .= '

				<tr data-original="'.$row['postid'].'">
				<td>'.++$rowcnt.'</td>
				<td><a class="tooltipS" title="'.$contentPreview.'" target="_blank" href="./'.$row['postid'].'?state=edit-'.$row['postid'].'">'.$row['postid'].'</a>
				<td><div class="post_title_td"><input class="post_title" value="'.htmlspecialchars($row['title'], ENT_QUOTES, "UTF-8").'" /></div></td> 
				<td>'.$row['category'].'
				</td>
				<td style="width:60%"><div class="post_tags_td"><input class="post_tags" value="'.$row['tags'].'"   name="q" id="tag_edit_'.$row['postid'].'" autocomplete="off" placeholder="Tags" onkeyup="qa_tag_edit_hints('.$row['postid'].')" onmouseup="qa_tag_edit_hints('.$row['postid'].')" /></div>

				<div class="qa-form-tall-note2">
				<span id="tag_edit_examples_title_'.$row['postid'].'" style="display:none;"> </span>
				<span id="tag_edit_complete_title_'.$row['postid'].'" style="display:none;"></span>
				<span id="tag_edit_hints_'.$row['postid'].'"></span></div>
				</td>
				</tr>';
		}
		$tagtable .= "</table>";

		// output into theme
		//$qa_content['custom'.++$c]='<p style="font-size:14px;">Click on the post tags to edit them!</p>';
		$qa_content['custom'.++$c]='<p>'.qa_lang_html('q2apro_quickedit_lang/edit_hint').'</p>';
		$qa_content['custom'.++$c]='<p>'.qa_lang_html('q2apro_quickedit_lang/edit_hint_q').'</p>';
		$qa_content['custom'.++$c]= $tagtable;

		// make newest users list bigger on page
		$qa_content['custom'.++$c] = '
			<style type="text/css">
			.qa-sidepanel {
display:none;
			}
		.qa-main {
width:100%;
		}
		table {
width:90%;
background:#EEE;
margin:30px 0 15px;
       text-align:left;
       border-collapse:collapse;
		}
		table th {
padding:4px;
background:#cfc;
border:1px solid #CCC;
       text-align:center;
		}
		table tr:nth-child(even){
background:#EEF;
		}
		table tr:nth-child(odd){
background:#F5F5F5;
		}
		table tr:hover {
background:#FFD;
		}
		table th:nth-child(1), table td:nth-child(1) {
width:60px;
      text-align:center;
		}
		td {
border:1px solid #CCC;
padding:1px 10px;
	line-height:25px;
		}
		table.tagtable td a { 
			font-size:12px;
		}
		input.post_title, input.post_tags, .inputdefault {
width:100%;
border:1px solid transparent;
padding:3px;
background:transparent;
		}
		input.post_title:focus, input.post_tags:focus, .inputactive {
background:#FFF !important;
	   box-shadow:0 0 2px #7AF
		}
		.post_title_td, .post_tags_td {
position:relative;
		}
		.sendr,.sendrOff {
padding:3px 10px;
background:#FC0;
border:1px solid #FEE;
       border-radius:2px;
position:absolute;
right:-77px;
top:-5px;
color:#123;
cursor:pointer;
		}
		.sendrOff {
			text-decoration:none !important;
		}
		</style>';

		$qa_content['custom'.++$c] = '
			<script type="text/javascript">
			$(document).ready(function(){
					var recentTR;
					$(".post_title, .post_tags").click( function() {
							// remove former css
							$(".post_title, .post_tags").removeClass("inputactive");
							recentTR = $(this).parent().parent().parent();
							recentTR.find("input.post_title, input.post_tags").addClass("inputactive");
							// alert(recentTR.find("input.post_tags").val());

							// add Update-Button if not yet added
							if(recentTR.find(".post_tags_td").has(".sendr").length == 0) {
							// remove all other update buttons
							$(".sendr").fadeOut(200, function(){$(this).remove() });
							recentTR.find(".post_tags_td").append("<a class=\'sendr\'>Update</a>");
							}
							});
					$(document).keyup(function(e) {
							// get focussed element
							var focused = $(":focus");
							// if enter key and input field selected
							if(e.which == 13 && (focused.hasClass("post_title") || focused.hasClass("post_tags"))) { 
							doAjaxPost();
							}
							// escape has been pressed
							else if(e.which == 27) {
							// remove all Update buttons and unfocus input fields
							$(".sendr").remove();
							// remove focus from input field
							$(":focus").blur();
							// remove active css class
							$(".post_title, .post_tags").removeClass("inputactive");									
							}
							});
					$(document).on("click", ".sendr", function() {
							doAjaxPost();
							});

					function doAjaxPost() {
						// get post data from <tr> element
						var postid = recentTR.attr("data-original"); 
						var posttitle = recentTR.find("input.post_title").val();
						var posttags = recentTR.find("input.post_tags").val();
						// alert(postid + " | " + posttitle + " | " + posttags);
						// var senddata = "postid="+postid+"&title="+posttitle+"&tags="+posttags;
						recentTR.find("#tag_edit_hints_"+postid).fadeOut(1500, function(){$(this).remove() });
						var dataArray = {
postid: postid,
	title: posttitle,
	tags: posttags
						};
						var senddata = JSON.stringify(dataArray);
						console.log("sending: "+senddata);
						// send ajax
						$.ajax({
type: "POST",
url: "'.qa_self_html().'",
data: { ajaxdata: senddata },
dataType:"json",
cache: false,
success: function(data) {
//dev
console.log("server returned:"+data+" #Tags: "+data["tags"]);

// prevent another click on button by assigning another class id
$(".sendr").attr("class","sendrOff");
// show success indicator checkmark
recentTR.find(".sendrOff").css("background", "#55CC55");
recentTR.find(".sendrOff").html("<span style=\'font-size:150%;\'>&check;</span>");

// write title back to posttitle input field
recentTR.find("input.post_title").val(data["title"]);
// write tags back to tags input field
recentTR.find("input.post_tags").val(data["tags"]);

// remove update button
recentTR.find(".sendrOff").fadeOut(1500, function(){$(this).remove() });
// remove focus from input field
$(":focus").blur();
// remove active css class
$(".post_title, .post_tags").removeClass("inputactive");									
}
});
}
});

</script>';

return $qa_content;
}

};


/*
   Omit PHP closing tag to help avoid accidental output
 */
