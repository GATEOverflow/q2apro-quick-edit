<?php

class qa_html_theme_layer extends qa_html_theme_base
{

	function head_css()//add custom css
	{
		qa_html_theme_base::head_css();
		$version=0.0071;
		$url=qa_request();
		$url_parts=explode('/',$url);
		if(sizeof($url_parts) > 0 && (strpos($url_parts[0], "quickedit") !== false))
		{
			$this->output('<link rel="stylesheet" defer type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'css/style.css?v='.$version.'">');

		}
	}

	function body_suffix()//add custom js
	{
		qa_html_theme_base::body_suffix();
		$version=0.0071;
		$url=qa_request();
		$url_parts=explode('/',$url);
		if(sizeof($url_parts) > 0 && (strpos($url_parts[0], "quickedit") !== false))
		{
			$this->output('<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js"></script>');
			$this->output('<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/base/jquery-ui.css">');

			$this->output('<script> var qa_tags_examples="";');
			$this->output('if (typeof qa_tags_complete === "undefined") {var qa_tags_complete =\'\';}');
			$template='<a href="#" class="qa-tag-link" onclick="return qa_tag_edit_click(this);">^</a>';
			$this->output('var qa_tag_edit_template =\''.$template.'\';');
			$this->output('</script>');

			$this->output('<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'js/quickedit.js?v='.$version.'"></script>');
			$this->output(' <script type="text/javascript">
				$(document).ready(function(){

					$(".post_tags").click( function() {

						if(qa_tags_complete == ""){
							$.ajax({
							type: "POST",
								url: "'.qa_path("quickedit_ajax_page").'",
								data: {ajax:"hello" },
								error: function() {
									console.log("server: ajax error");
		},
			success: function(htmldata) {
				qa_tags_complete = htmldata;
		}
		});
		}
		else {
		}
		});

		});
</script> 
');


		}
	}
}
