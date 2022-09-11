$(document).ready(function(){
	var recentTR, doingPost = false, last;
	$(".post_tags").click( function(e) {
		if(doingPost){
			var recent = e.target.id;
			if(recent !== last)
			{

				alert("Please Wait until update is over");
				return;
	}
	}
	doingPost = true;
	last = e.target.id;
	// remove former css
	$(".post_tags").removeClass("inputactive");
	recentTR = $(this).parent().parent().parent();
	recentTR.find("input.post_tags").addClass("inputactive");
	// alert(recentTR.find("input.post_tags").val());

	// add Update-Button if not yet added
	if(recentTR.find(".post_tags_td").has(".sendr").length == 0) {
		// remove all other update buttons
		$(".sendr").fadeOut(200, function(){$(this).remove() });
		recentTR.find(".post_tags_td").append("<a class='sendr' data-id='2'>Update</a>");
	}
	});
	$("input.post_title").focusout(function () {

		doAjaxPost(1);
	});
	$("input.post_answer").focusout(function () {

		doAjaxPost(3);
	});
	$(".post_title").click( function() {
		if(doingPost){
			alert("Please Wait until update is over");
			return;
	}
	doingPost = true;
	//while(doingPost);
	// remove former css
	$(".post_title").removeClass("inputactive");
	recentTR = $(this).parent().parent().parent();
	recentTR.find("input.post_title").addClass("inputactive");
	// alert(recentTR.find("input.post_title").val());

	// add Update-Button if not yet added
	if(recentTR.find(".post_title_td").has(".sendr").length == 0) {
		// remove all other update buttons
		$(".sendr").fadeOut(200, function(){$(this).remove() });
		recentTR.find(".post_title_td").append("<a class='sendr' data-id='1'>Update</a>");
	}
	});
	$(".post_answer").click( function() {
		if(doingPost){
			alert("Please Wait until update is over");
			return;
	}
	doingPost = true;
	//while(doingPost);
	// remove former css
	$(this).removeClass("inputactive");
	recentTR = $(this).parent().parent().parent();
	recentTR.find("input.post_answer").addClass("inputactive");
	// alert(recentTR.find("input.post_answer").val());

	// add Update-Button if not yet added
	if(recentTR.find(".post_answer_td").has(".sendr").length == 0) {
		// remove all other update buttons
		$(".sendr").fadeOut(200, function(){$(this).remove() });
		recentTR.find(".post_answer_td").append("<a class='sendr' data-id='3'>Update</a>");
	}
	});
	$(document).keyup(function(e) {
		// get focussed element
		var focused = $(":focus");
		// if enter key and input field selected
		if(e.which == 13){
			if  (focused.hasClass("post_title")){
				doAjaxPost(1);
	}
else if  (focused.hasClass("post_tags")){
	doAjaxPost(2);
	}
else if  (focused.hasClass("post_answer")){
	doAjaxPost(3);
	}
	}
	// escape has been pressed
else if(e.which == 27) {
	// remove all Update buttons and unfocus input fields
	$(".sendr").remove();
	// remove focus from input field
	$(":focus").blur();
	// remove active css class
	$(".post_title, .post_tags, .post_answer").removeClass("inputactive");									
	}
	});
	$(document).on("click", ".sendr", function() {
		id = $(this).attr('data-id');
		//console.log("id = "+ id);
		doAjaxPost(id);
	});

	function doAjaxPost(id) {
		//do a lock
		doingPost = true;
		// get post data from <tr> element
		//var postid = recentTR.attr("data-original"); 
		if(id == 1)
			var dataf = recentTR.find("input.post_title");
		else if(id == 2)
			var dataf = recentTR.find("input.post_tags");
		else if(id == 3)
			var dataf = recentTR.find("input.post_answer");
		console.log(dataf);
		var postid = dataf.parent().attr("data-postid");
		var data = dataf.val();
		// alert(postid + " | " + posttitle + " | " + posttags);
		recentTR.find("#tag_edit_hints_"+postid).fadeOut(1500, function(){$(this).remove() });
		var dataArray = {
		id: id,
			postid: postid,
			data: data
	};		
	var senddata = JSON.stringify(dataArray);

	console.log("sending: "+senddata);
	// send ajax
	$.ajax({
	type: "POST",
		url: qa_root + "/" + "quickeditajax",
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
			recentTR.find(".sendrOff").html("<span style='font-size:150%;'>&check;</span>");
			if(id == 1)
				//recentTR.find("input.post_title").val(data["title"]);
				dataf.val(data["title"]);
			// write tags back to tags input field
			else if(id == 2)
				//recentTR.find("input.post_tags").val(data["tags"]);
				dataf.val(data["tags"]);
			else if(id == 3)
				//recentTR.find("input.post_answer").val(data["answer"]);
				dataf.val(data["answer"]);

			// remove update button
			recentTR.find(".sendrOff").fadeOut(800, function(){$(this).remove() });
			// remove focus from input field
			$(":focus").blur();
			// remove active css class
			$(".post_title, .post_tags, .post_answer").removeClass("inputactive");									
			doingPost = false;
	},
		error: function(data) {
			console.log("Error: "+data);
	}
	});
	}
	});

	var popOverSettings= {
	placement: 'bottom',
	}
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip(popOverSettings);
		$('[data-toggle="tooltip"]').on('shown.bs.tooltip',function (){
			MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
	});





	});

function qa_tag_edit_hints(postid)
{
	var elem=document.getElementById('tag_edit_'+postid);
	var html='';
	var completed=false;

	// first try to auto-complete
	if (qa_tags_complete) {
		var parts=qa_tag_edit_typed_parts(elem);

		if (parts.typed) {
			html=qa_edit_tags_to_html((qa_html_unescape(qa_tags_examples+','+qa_tags_complete)).split(','), parts.typed.toLowerCase());
			completed=html ? true : false;
		}
	}

	// otherwise show examples
	if (qa_tags_examples && !completed)
		html=qa_edit_tags_to_html((qa_html_unescape(qa_tags_examples)).split(','), null);

	// set title visiblity and hint list
	document.getElementById('tag_edit_examples_title_'+postid).style.display=(html && !completed) ? '' : 'none';
	document.getElementById('tag_edit_complete_title_'+postid).style.display=(html && completed) ? '' : 'none';
	document.getElementById('tag_edit_hints_'+postid).innerHTML=html;
}






function qa_tag_edit_click(link)

{

	var id = link.parentNode.id;
	var post = id.split("_");
	var postid = post[post.length-1];
	var elem=document.getElementById("tag_edit_"+postid);

	var parts=qa_tag_edit_typed_parts(elem);



	// removes any HTML tags and ampersand

	var tag=qa_html_unescape(link.innerHTML.replace(/<[^>]*>/g, ''));



	var separator=' ';


	// replace if matches typed, otherwise append

	var newvalue=(parts.typed && (tag.toLowerCase().indexOf(parts.typed.toLowerCase())>=0))

		? (parts.before+separator+tag+separator+parts.after+separator) : (elem.value+separator+tag+separator);



	// sanitize and set value

	if (false)

		elem.value=newvalue.replace(/[\s,]*,[\s,]*/g, ', ').replace(/^[\s,]+/g, '');

	else

		elem.value=newvalue.replace(/[\s,]+/g, ' ').replace(/^[\s,]+/g, '');



//	elem.focus();

	qa_tag_edit_hints(postid);



	return false;

}




function qa_edit_tags_to_html(tags, matchlc)

{

	var html='';

	var added=0;

	var tagseen={};



	for (var i=0; i<tags.length; i++) {

		var tag=tags[i];

		var taglc=tag.toLowerCase();



		if (!tagseen[taglc]) {

			tagseen[taglc]=true;



			if ( (!matchlc) || (taglc.indexOf(matchlc)>=0) ) { // match if necessary

				if (matchlc) { // if matching, show appropriate part in bold

					var matchstart=taglc.indexOf(matchlc);

					var matchend=matchstart+matchlc.length;

					inner='<span style="font-weight:normal;">'+qa_html_escape(tag.substring(0, matchstart))+'<b>'+

						qa_html_escape(tag.substring(matchstart, matchend))+'</b>'+qa_html_escape(tag.substring(matchend))+'</span>';

				} else // otherwise show as-is

					inner=qa_html_escape(tag);



				html+=qa_tag_edit_template.replace(/\^/g, inner.replace('$', '$$$$'))+' '; // replace ^ in template, escape $s



				if (++added>=5)

					break;

			}

		}

	}



	return html;

}


function qa_tag_edit_typed_parts(elem)

{

	var caret=elem.value.length-qa_tag_edit_caret_from_end(elem);

	var active=elem.value.substring(0, caret);

	var passive=elem.value.substring(active.length);


	var qa_tag_edit_onlycomma = false;
	// if the caret is in the middle of a word, move the end of word from passive to active

	if (

			active.match(qa_tag_edit_onlycomma ? /[^\s,][^,]*$/ : /[^\s,]$/) &&

			(adjoinmatch=passive.match(qa_tag_edit_onlycomma ? /^[^,]*[^\s,][^,]*/ : /^[^\s,]+/))

	   ) {

		active+=adjoinmatch[0];

		passive=elem.value.substring(active.length);

	}



	// find what has been typed so far

	var typedmatch=active.match(qa_tag_edit_onlycomma ? /[^\s,]+[^,]*$/ : /[^\s,]+$/) || [''];



	return {before:active.substring(0, active.length-typedmatch[0].length), after:passive, typed:typedmatch[0]};

}

function qa_tag_edit_caret_from_end(elem)

{

	if (document.selection) { // for IE

		elem.focus();

		var sel=document.selection.createRange();

		sel.moveStart('character', -elem.value.length);



		return elem.value.length-sel.text.length;



	} else if (typeof(elem.selectionEnd)!='undefined') // other browsers

		return elem.value.length-elem.selectionEnd;



	else // by default return safest value

		return 0;

}
function qa_html_unescape(html)
{
	return html.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
}
function qa_html_escape(text)
{
	return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

