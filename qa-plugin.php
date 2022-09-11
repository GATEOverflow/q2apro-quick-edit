<?php

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}

	// language file
	qa_register_plugin_phrases('q2apro-quickedit-lang-*.php', 'q2apro_quickedit_lang');

	// page
	qa_register_plugin_module('page', 'q2apro-quickedit-page.php', 'q2apro_quickedit', 'Quick-Edit Page');

	//ajax page
	qa_register_plugin_module('page', 'q2apro-quickedit-main-ajax-page.php', 'q2apro_quickedit_main_ajax', 'Quick-Edit Main Ajax Page');
	//ajax page
	qa_register_plugin_module('page', 'q2apro-quickedit-ajax-page.php', 'q2apro_quickedit_ajax', 'Quick-Edit Ajax Page');

	// admin
	qa_register_plugin_module('module', 'q2apro-quickedit-admin.php', 'q2apro_quickedit_admin', 'Quick-Edit Admin');
        
	qa_register_plugin_layer('q2apro-quickedit-layer.php', 'Quick-Edit Layer');

/*
	Omit PHP closing tag to help avoid accidental output
*/
