<?php
/*
Plugin Name: The Hacker's Diet
Plugin URI: http://www.afex2win.com/stuff/hackersdiet/
Description: Track your weight loss online via your blog.  Inspired by John Walker's <a href="http://www.fourmilab.ch/hackdiet/">The Hacker's Diet</a>.  This plugin replaces the Excel files supplied for his original system.
Version: 1.0.0
Author: Keith 'afex' Thornhill
Author URI: http://www.afex2win.com/
*/
$hd_version = "1.0.0";
$hackdiet_user_level = 1;	// feel free to change this value (1-10) if you want to restrict lower users from using the plugin

// find URI to plugin dir so ajax calls have correct path
define("PLUGIN_FOLDER_URL", get_bloginfo('wpurl') . '/wp-content/plugins/' . str_replace('/hackersdiet.php', '', $plugin));
$hd_parsed_url = parse_url(PLUGIN_FOLDER_URL);
define("PLUGIN_PATH",  $hd_parsed_url['path'] . "/");
require_once(dirname(__FILE__).'/hackersdiet_lib.php');

function hackdiet_install() {
  global $table_prefix, $wpdb;

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	$table_name = $table_prefix . "hackdiet_weightlog";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE ".$table_name." (wp_id BIGINT( 20 ) UNSIGNED NOT NULL, date DATE NOT NULL, weight DOUBLE UNSIGNED NOT NULL DEFAULT '0', trend DOUBLE UNSIGNED NOT NULL DEFAULT '0', rung SMALLINT UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY ( wp_id, date ))";
		dbDelta($sql);
	}

	$table_name = $table_prefix . "hackdiet_settings";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE ".$table_name." (wp_id BIGINT UNSIGNED NOT NULL, name VARCHAR( 50 ) NOT NULL, value TEXT NOT NULL, PRIMARY KEY ( wp_id , name ) )";
		dbDelta($sql);
	}
}

function hackdiet_add_dashboard_page() {
	global $hackdiet_user_level;

	if (function_exists('add_submenu_page')) {
		add_submenu_page('index.php', 'hackersdiet', 'Hacker\'s Diet', $hackdiet_user_level, basename(__FILE__), 'hackdiet_option_page');
	}
}

function hackdiet_js() {
	global $plugin_page;
	if (basename(__FILE__) == $plugin_page) {
		?>        
		<script src="<?= PLUGIN_FOLDER_URL?>/curvycorners.js" type="text/javascript"></script>
		<script src="<?= PLUGIN_FOLDER_URL?>/hackersdiet.js" type="text/javascript"></script>
		
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/yahoo-dom-event/yahoo-dom-event.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/element/element-beta-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/datasource/datasource-beta-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/json/json-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/logger/logger-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/yahoo/yahoo-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/connection/connection-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/event/event-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/calendar/calendar-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/charts/charts-experimental-min.js"></script>
        <script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/animation/animation-min.js"></script>
        
		<link rel='stylesheet' href='css/dashboard.css?version=2.5' type='text/css' />
		<link rel="stylesheet" type="text/css" href="<?= PLUGIN_FOLDER_URL?>/hackersdiet.css" /> 
        <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.5.2/build/calendar/assets/skins/sam/calendar.css">
		<?
	}
}

function hackdiet_option_page() {
	global $table_prefix, $wpdb, $user_ID;
    global $hd_version;

    require_once 'admin_page.php';
}

function hackdiet_widget_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_hackdiet($args) {
		extract($args);

		// replace this with option from db
		$options = get_option('widget_hackdiet');
		$title = $options['title'];

		echo $before_widget . $before_title . $title . $after_title;
		?>
		Hacker's Diet widget not yet designed.
		<?
		echo $after_widget;
	}

	function widget_hackdiet_control() {
		$options = get_option('widget_hackdiet');

		if ( !is_array($options) )
			$options = array('title'=>'');

		if ( $_POST['hackdiet-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['hackdiet-title']));
			update_option('widget_hackdiet', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<p style="text-align:right;"><label for="hackdiet-title">' . __('Title:') . ' <input style="width: 200px;" id="hackdiet-title" name="hackdiet-title" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="hackdiet-submit" name="hackdiet-submit" value="1" />';
	}

	register_sidebar_widget(array('The Hacker\'s Diet', 'widgets'), 'widget_hackdiet');

	register_widget_control(array('The Hacker\'s Diet', 'widgets'), 'widget_hackdiet_control', 300, 100);
}

// hooks
add_action('admin_menu',                           'hackdiet_add_dashboard_page');
add_action('admin_head',                           'hackdiet_js');
add_action('widgets_init',                         'hackdiet_widget_init');

register_activation_hook(__FILE__, 'hackdiet_install');
?>