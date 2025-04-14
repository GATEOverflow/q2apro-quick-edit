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

class q2apro_quickedit_main_ajax {

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
		);
	}

	// for url query
	function match_request($request)
	{
		if ($request=='quickeditajax') {
			return true;
		}

		return false;
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

		// AJAX post: we received post data, so it should be the ajax call to update the tags of the post
		$transferString = qa_post_text('ajaxdata'); // holds postid, question title, tags
		if(isset($transferString)) {
			//		header('Access-Control-Allow-Origin: '.qa_path(null));
			$newdata = json_decode($transferString,true);
			$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/
			// echo '# '.$newdata['postid'].' ||| '.$newdata['title'].' ||| '.$newdata['tags']; 	return;
			$posttitle = $posttags = $postanswer = null;
			$id = $newdata['id'];
			$postid = $newdata['postid'];
			if($id == 1)
				$posttitle = $newdata['data'];
			else if($id == 2)
				$posttags = $newdata['data'];
			else if($id == 3)
				$postanswer = $newdata['data'];
			else
			{
				header('HTTP/1.1 500 Internal Server Booboo');
				header('Content-Type: application/json; charset=UTF-8');
				echo json_encode('tags=Invalid Update');
				return;
			}
			if(!$postid){// || (!$posttitle && !$posttags && !$postanswer )  ) {
				header('HTTP/1.1 500 Internal Server Booboo');
				header('Content-Type: application/json; charset=UTF-8');
				die(json_encode(array('message' => 'ERROR', 'detail' => 'No postid')));
			}
			else {
				require_once QA_INCLUDE_DIR.'app/users.php';
				$userid = qa_get_logged_in_userid();
				// process new tags
				require_once QA_INCLUDE_DIR.'qa-app-posts.php';
				if($id == 1)
				{
					qa_post_set_content($postid, $posttitle, null, null, null, null,null, $userid, null, null); 
					$tags = "newtitle=$posttitle"; // correctly parse tags string
				}
				else if($id == 2)
				{
					$tags="";
					$tagsIn = str_replace(' ', ',', $posttags); // convert spaces to comma
					qa_post_set_content($postid, null, null, null, $tagsIn, null,null, $userid, null, null); 
					$tags = qa_post_tags_to_tagstring($tagsIn); // correctly parse tags string

				}
				else if ($id == 3)
				{
					$query = "insert into ^ec_answers(postid, answer_str) values(#, $) on duplicate key update answer_str = $";
					qa_db_query_sub($query,$postid, $postanswer, $postanswer);
					$tags = "tags=answer_updated"; // correctly parse tags string
				}
			} // end db update

			// header('Content-Type: text/plain; charset=utf-8');
			// echo 'updated postid: '.$postid.' with tags: '.$tags;

			// ajax return array data to write back into table
			$arrayBack = array(
				'postid' => $postid,
				'title' => $posttitle,
				'tags' => $tags,
				'answer' => $postanswer
			);
			echo json_encode($arrayBack);
			return;
		} // end POST data
	}

};


/*
   Omit PHP closing tag to help avoid accidental output
 */
