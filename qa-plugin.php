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


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}

	// language file
	qa_register_plugin_phrases('q2apro-quickedit-lang-*.php', 'q2apro_quickedit_lang');

	// page
	qa_register_plugin_module('page', 'q2apro-quickedit-page.php', 'q2apro_quickedit', 'Quick-Edit Page');
	// page
	qa_register_plugin_module('page', 'q2apro-quickeditcat-page.php', 'q2apro_quickeditcat', 'Quick-Edit Cat Page');
	//ajax page
	qa_register_plugin_module('page', 'q2apro-quickedit-ajax-page.php', 'q2apro_quickedit_ajax', 'Quick-Edit Ajax Page');

	// admin
	qa_register_plugin_module('module', 'q2apro-quickedit-admin.php', 'q2apro_quickedit_admin', 'Quick-Edit Admin');
        
qa_register_plugin_layer('q2apro-quickedit-layer.php', 'Quick-Edit Layer');

/*
	Omit PHP closing tag to help avoid accidental output
*/
