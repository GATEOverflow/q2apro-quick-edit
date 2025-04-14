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
			'source' => '^words JOIN (SELECT wordid FROM ^words WHERE tagcount>='.$min.' ORDER BY tagcount DESC) y ON ^words.wordid=y.wordid order by word',
			'arguments' => null,
			'arraykey' => 'word',
			'arrayvalue' => 'word',
			'sort' => 'word',
		);
	}

	function tagform()
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';
		$mintags = 1;
		$tags=qa_db_single_select($this->tags_selectspec($mintags));
		$fields = array();
		$fields[] = array(
			'label' => 'Tag to fetch Post-IDs',
			'type'=>'select',
			'tags' => "id='tag_selector' name='tagstring' class='col' onchange='dosubmit(this)'",
			'options' => $tags,
		);
		$userlevel = qa_get_logged_in_level();
		if($userlevel >= QA_USER_LEVEL_SUPER) {
			$fields[] = array(
				'label' => 'Change already updated category to new one as per tag',
				'note' => 'If unchecked change is limited to uncategorized and in categories: Others, Unknown Category and new',
				'type'=>'checkbox',
				'tags' => "id='force' name='force' class='col'",
				'value' => "",
			);
			$fields[] = array(
				'label' => 'Convert image to text',
				'note' => 'If checked will try to append the image text to question content',
				'type'=>'checkbox',
				'tags' => "id='ocr' name='ocr' class='col'",
				'value' => "",
			);
		}

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
	function get_text($imgurl) {
		$ch = curl_init();
		$action = "https://api.mathpix.com/v3/text";
		$header = array(
			'app_id:admin_gateoverflow_com_5160ce_336aad',
			'app_key:19f84f08f1f45df6ff50a54af880a470a244038558934e02deb1851c44d663ae',
			'Content-Type:application/json'
		);
		$params = array();
		$params['src'] = $imgurl;
		$params['math_inline_delimiters'] = array("$", "$");
		$params['rm_spaces'] = 'true';
		//print_r($params);
		curl_setopt($ch, CURLOPT_URL, $action);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch ,CURLOPT_RETURNTRANSFER, true);

		$curlresult = curl_exec($ch);
		curl_close($ch);
		error_log($curlresult);
		$response = json_decode($curlresult, true);
		if($response['confidence'] >= 0)
		{
			if(isset($response['text']))
				$text = $response['text'];
			else
				$text = $response['latex_styled'];
			error_log($text);
		}
		else {
			$text = Null;
		}
		return $text;
	}

	function process_submit_ocr($request) {
		$tag = qa_post_text('tagstring');
		//		$query="select postid, content from ^posts where content like '%<img%' and tags like '%".$tag."%';";
		$query = "select b.postid,b.content from ^posttags a,^posts b  where a.postid = b.postid and wordid = (select wordid from ^words WHERE  word = '".qa_strtolower($tag)."')";
		error_log($query);

		$result = qa_db_query_sub($query);
		$posts = qa_db_read_all_assoc($result);
		$count = 0;
		$tmpcount = 0;
		//$tmpfolder = dirname(__FILE__)."/tmp/".uniqid();
		foreach ($posts as $post) {
			$postid = $post['postid'];
			preg_match_all('/<img[^>]+>/i',$post['content'], $imgTags, PREG_SET_ORDER);
			$newcontent = $post['content'];
				//error_log(json_encode($imgTags));
			for($l=0; $l < count($imgTags); $l++) {
				preg_match('/src="([^"]+)/i',$imgTags[$l][0], $image);
				//error_log(json_encode($image));
				if((bool)qa_post_text('rollback')) {
				//	$orig = $imgTags[0][0];
				//	$query="update $table set content = '$orig' where postid =$postid";
				}
				else {
					$imgsrc = html_entity_decode(str_ireplace( 'src="', '',  $image[0]));
					$text = $this->get_text($imgsrc);
					if($text) {
						$newcontent .= "<p>".nl2br($text)."</p>";
					}
					else {//not used
						continue;
						$wget_cmd = '/usr/bin/curl "'.$imgsrc.'" --create-dirs -o '.$tmpfolder.'/a.png';
						error_log($wget_cmd);
						$ret = system($wget_cmd);
						if($ret != 0 ) {
							echo "Error code $ret for postid $postid";
						}
						else {
							$updated = false;
							system("/usr/bin/convert -resize 500% -type Grayscale -threshold 60% $tmpfolder/a.png $tmpfolder/b.png");
							system("/usr/bin/tesseract $tmpfolder/b.png $tmpfolder/b");
							$text = file_get_contents("$tmpfolder/b.txt");
							$text = preg_replace('/[[:^print:]]/', '', $text);
							$pattern ='/(.*)(\(A\))(.*)(\((B|8)\))(.*)(\(C\))(.*)(\(D\))(.*)/i';
							$replacement = '$1<ol style="list-style-type:upper-alpha"><li> $3 </li><li> $6 </li> <li>$8 </li>  <li>$10  </li></ol>';
							$text = preg_replace($pattern, $replacement, $text);
							$imagetext[] = $text;
						}
						//                              print_r($text);
					}
				}
				
			}
			$tmpcount++;
			if($postid) {
				qa_post_set_content($postid, null, $newcontent, null, null, null,null, null, null, null);
				//$result = qa_db_query_raw($query);
			}
			else {
				echo "No postid";
				break;
			}
			//break;
		}
	}
	function mysorttitle($a, $b){
		//return strcmp($a['postid'], $b['postid']);
		$a_split = explode(":", $a['title']);
		$b_split = explode(":", $b['title']);
		if(!strcmp(trim($a_split[0]), trim($b_split[0])) && count($a_split) > 1 && count($b_split) > 1) return trim($a_split[1]) > trim($b_split[1]);
		return strcmp(trim($a_split[0]), trim($b_split[0]));
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
		$userlevel = qa_get_logged_in_level();
		$c = 2;
		$ocr=(bool)qa_post_text('ocr') && $userlevel >= QA_USER_LEVEL_SUPER ;
		$force=(bool)qa_post_text('force') && $userlevel >= QA_USER_LEVEL_SUPER ;
		$qa_content=qa_content_prepare();
		if (qa_clicked('changesubmit'))
		{
			$tag = qa_post_text('tagstring');
			//			echo "tag = ".$tag;
			$filter = ' true ';
			if(!$force)
			{
				$filter=" postid in (select postid from ^posts where categoryid is null or categoryid in(select categoryid from ^categories where title like 'Others' or title like 'Unknown Category' or title like 'new' ))";// and categoryid =6";
			}
			$query = "select postid from ^posttags where wordid = (select wordid from ^words WHERE word = '".qa_strtolower($tag)."') and $filter";
			$result = qa_db_query_sub($query);
			$postids = qa_db_read_all_values($result, true);
			$count = 0;
			foreach ($postids as $postid)
			{

				$updated = false;
				$query="select tags from ^posts where postid = #";
				$result = qa_db_query_sub($query, $postid);
				$fulltags = qa_db_read_one_value($result, true);
				$fulltagsarray = qa_tagstring_to_tags($fulltags);
				$userid = qa_get_logged_in_userid();
				foreach ($fulltagsarray as $tagvalue)
				{
					if($tagvalue === $tag) continue;//skip the selection one. 

					$query = "select categoryid from ^categories where tags like $";
					$result = qa_db_query_sub($query, $tagvalue);
					$category = qa_db_read_one_value($result, true);
					if(!$category) continue;
					$updated = true;
					//echo "($postid, $category, $tagvalue, $userid)";
					//qa_post_set_category($postid, $category);
					qa_post_set_category($postid, $category, $userid);
					break;
				}
				if($updated) $count++;
				//	break;

			}
			$qa_content['custom'.++$c] = "<p>$count posts have been categorized successfully</p>";
		}
		if($ocr) {
			$this -> process_submit_ocr($request);
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
		$tagstring='';
		if(isset($_GET['tagfilter']))
			$tagfilter = $_GET['tagfilter'];
		$tagfilter = qa_post_text('tagstring');
		if($tagfilter) {
			$tagstring = " and a.tags like '%".$tagfilter."%' ";
			$tagstring = " and a.postid in (select postid from ^posttags where wordid = (select wordid from ^words WHERE word = '$tagfilter' and word = '".qa_strtolower($tagfilter)."'))";
		}
		//echo $tagstring;
		//exit;
		//else		$tagstring .= " and a.tags NOT LIKE ''"; 
		#$tagstring .= " and a.categoryid = 6";

		$qa_content['form'] =  $this -> tagform();

		// query to get all posts according to pagination, ignore closed questions
		$queryAllPosts = qa_db_query_sub('SELECT a.postid,a.tags,a.title,a.content,format,b.title as category,c.answer_str as answer FROM `^posts` a 
			left join ^ec_answers c on a.postid = c.postid 
			left join ^categories b on a.categoryid = b.categoryid
			WHERE `type` = "Q" 
			AND `closedbyid` IS NULL'. $tagstring.' 
			ORDER BY title 
			LIMIT #,#
', $start, $pagesize);

		// initiate output string
		$tagtable = '<table class="tagtable" id="quickedittable"> <thead> <tr> <th>No.</th><th>'.qa_lang_html('q2apro_quickedit_lang/th_postid').'</th> <th>Answer</th><th>'.qa_lang_html('q2apro_quickedit_lang/th_questiontitle').'</th><th>'.qa_lang_html('q2apro_quickedit_lang/th_postcategory').'</th> <th>'.qa_lang_html('q2apro_quickedit_lang/th_posttags').'</th> </tr></thead>';
		$maxlength = qa_opt('mouseover_content_max_len'); // 480

		require_once QA_INCLUDE_DIR.'qa-util-string.php'; // for qa_shorten_string_line()
		$blockwordspreg=qa_get_block_words_preg();
		$results = qa_db_read_all_assoc($queryAllPosts);
		usort($results, array("q2apro_quickeditcat", "mysorttitle"));
		//usort($results, "mysorttitle");
		//	print_r($results);
		//	exit;
		{


		}
		$count = count($results); // items total
		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, true); // last parameter is prevnext
		$rowcnt = 0;
		foreach($results as $row) {
			//while ( ($row = qa_db_read_one_assoc($queryAllPosts,true)) !== null ) {
			$text=qa_viewer_text($row['content'], $row['format'], array('blockwordspreg' => $blockwordspreg));
			$contentPreview = $row['title'].". ".qa_html(qa_shorten_string_line($text, $maxlength));
			$tagtable .= '

				<tr data-original="'.$row['postid'].'">
				<td>'.++$rowcnt.'</td>
				<td>
<div style="display:none" id="h-'.$row['postid'].'">'.$contentPreview.'</div>
<a class="tooltips" data-toggle="tooltip" id="'.$row['postid'].'" title="'.$contentPreview.'" target="_blank" href="./'.$row['postid'].'?state=edit-'.$row['postid'].'">'.$row['postid'].'</a></td>
				<td><div class="post_answer_td" data-postid="'.$row['postid'].'"><input class="post_answer" value ="'.$row['answer'].'" /></div></td>

				<td><div class="post_title_td" data-postid="'.$row['postid'].'"><input class="post_title" value="'.htmlspecialchars($row['title'], ENT_QUOTES, "UTF-8").'" /></div></td> 
				<td>'.$row['category'].'
				</td>
				<td style="width:60%"><div class="post_tags_td" data-postid="'.$row['postid'].'"><input class="post_tags" value="'.$row['tags'].'"   name="q" id="tag_edit_'.$row['postid'].'" autocomplete="off" placeholder="Tags" onkeyup="qa_tag_edit_hints('.$row['postid'].')" onmouseup="qa_tag_edit_hints('.$row['postid'].')" /></div>

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


		return $qa_content;
	}

};


/*
   Omit PHP closing tag to help avoid accidental output
 */
