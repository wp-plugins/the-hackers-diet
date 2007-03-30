<?php
/*
Plugin Name: The Hacker's Diet
Plugin URI: http://www.afex2win.com/stuff/hackersdiet/
Description: Track your weight loss online via your blog.  Inspired by John Walker's <a href="http://www.fourmilab.ch/hackdiet/">The Hacker's Diet</a>.  This plugin replaces the Excel files supplied for his original system.
Version: 0.9.2b
Author: Keith 'afex' Thornhill
Author URI: http://www.afex2win.com/
*/
$hd_version = "0.9.2b";
$hackdiet_user_level = 1;	// feel free to change this value (1-10) if you want to restrict lower users from using the plugin


define("PLUGIN_FOLDER_URL", get_bloginfo('wpurl') . "/wp-content/plugins/hackersdiet");

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
		<script src="<?= PLUGIN_FOLDER_URL?>/prototype.js" type="text/javascript"></script>
		<script src="<?= PLUGIN_FOLDER_URL?>/editinplace.js" type="text/javascript"></script>
		<script src="<?= PLUGIN_FOLDER_URL?>/date-picker/js/datepicker.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="<?= PLUGIN_FOLDER_URL?>/date-picker/css/datepicker.css" /> 
		<?
	}
}

function hackdiet_option_page() {
	global $table_prefix, $wpdb, $user_ID;
    global $hd_version;

	$options = hackdiet_get_options($user_ID);

	// first time they open the page, we need to set default unit
	if (!$options["unit"]) {
		$options["unit"] = "lb";
		hackdiet_save_options($user_ID, $options);
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		if ($_POST["goal_date"] && !$_POST["goal_weight"]) {
			// can't plan without a plan
			$error = "So exactly how much would you like to weigh on " . $_POST["goal_date"] . "?";
		} else {
//			$options["unit"] = $_POST["unit"];

			$options["goal_weight"] = $_POST["goal_weight"];
			$options["goal_date"] = $_POST["goal_date"];
            if ($_POST["maint_mode"]) {
                $options["maint_mode"] = true;
            } else {
                $options["maint_mode"] = "";
            }

			hackdiet_save_options($user_ID, $options);

			if ($_POST["unit"] != $_POST["old_unit"]) {
				convert_weights($_POST["old_unit"], $_POST["unit"]);
			}
		}
	}

	?>
	<?php if ($error):?>
	<div id="message" class="error fade"><p><strong><?php echo $error?></strong></p></div>
	<?php elseif ($message):?>
	<div id="message" class="updated fade"><p><strong><?php echo $message?></strong></p></div>
	<?php endif;?>

	<style>
		fieldset.options {
		    clear:both;
		    border:1px solid #ccc;
		}
		fieldset.options legend {
		    font-family: Georgia,"Times New Roman",Times,serif;
		    font-size: 22px;
		}
		.editable {
			color: #000;
			background-color: #ffffd3;
		}
		.blankedit {
			color: #ccc;
			font-size: 9px!important;
			font-style: italic!important;
			font-weight: none!important;
		}
		.weightedit {
			width: 200px;
			font-size: 18px;
			font-weight: bold;
		}

        .trend_dif {
            font-size: small;
            vertical-align: 50%;
        }

        .good_trend {
            color: green;
        }

        .bad_trend {
            color: red;
        }

		.hackdiet_edit_text {
			width: 50px;
		}
		.hackdiet_edit_button {

		}
		.hackdiet_cancel_button {

		}
		h3.title {
			font: 100 186%/1.1 constantia, georgia, serif;
			color: #75C20F;
			letter-spacing:-0.02em;
		}
		#hackdiet_zeitgeist {
			background: #eee;
			border: 1px solid #69c;
			font-size: 90%;
			margin-right: 1em;
			margin-top: 4em;
			padding: 1em;
		}

		#hackdiet_zeitgeist h2, fieldset legend a {
			border-bottom: none;
		}

		#hackdiet_zeitgeist h2 {
			margin-top: .4em;
		}

		#hackdiet_zeitgeist h3 {
			border-bottom: 1px solid #ccc;
			font-size: 16px;
			margin: 1em 0 0;
		}

		#hackdiet_zeitgeist h3 cite {
			font-size: 12px;
			font-style: normal;
		}

		#hackdiet_zeitgeist li, #zeitgeist p {
			margin: .2em 0;
		}

		#hackdiet_zeitgeist ul {
			margin: 0 0 .3em .6em;
			padding: 0 0 0 .6em;
		}
	</style>
    <div id="file_path" style="display: none;"><?= preg_replace("/^(.*)\/wp-admin\/index\.php$/", "\\1/wp-content/plugins/hackersdiet/", $_SERVER["PHP_SELF"]) ?></div>
    <!--<div id="file_path" style="display: none;"><?= PLUGIN_FOLDER_URL ?></div>-->
    
	<div class="wrap">
		<h2 id="write-post">The Hacker's Diet</h2>

		<?
		if ($_GET["section"] == "reports") {
			/*********************************************
			*                                            *
			*                                            *
			*              REPORTING SCREEN              *
			*                                            *
			*                                            *
			*********************************************/

			if ($_GET["show"] == "all") {
				$query = "select min(date) as max, max(date) as min from ".$table_prefix."hackdiet_weightlog where wp_id = $user_ID";
				$row = $wpdb->get_row($query);
				$_GET['start_date'] = $row->max;
				$_GET['end_date']= $row->min;
			}

			?>
			<table width="100%">
				<form method="get" action="<?=$_SERVER["PHP_SELF"]?>">
				<input type="hidden" name="page" value="<?=$_GET["page"]?>">
				<input type="hidden" name="section" value="<?=$_GET["section"]?>">
				<tr>
					<td width="33%" align="center">
						From: <input type="text" id="start_date" name="start_date" class="format-y-m-d divider-dash" value="<?=$_GET['start_date']?>">
					</td>
					<td width="33%" align="center">
						To: <input type="text" id="end_date" name="end_date" class="format-y-m-d divider-dash" value="<?=$_GET['end_date']?>">
					</td>
					<td align="center">
						<input type="submit" value="run report &gt;" />
					</td>
				</tr>
				</form>
				<tr>
					<td colspan="3" align="center">
						<br>
						<br>
						<img id="main_graph" src="<?=PLUGIN_FOLDER_URL?>/hackersdiet_chart.php?user=<?=$user_ID?>&start_date=<?=$_GET['start_date']?>&end_date=<?=$_GET['end_date']?>&goal=<?=$options["goal_weight"]?>">
						<div id="blurb">
						<?
							include(dirname(__FILE__).'/ajax_blurb.php');
						?>
						</div>
					</td>
				</tr>
			</table>
			<?
		} else {
			/**********************************************
			*                                             *
			*                                             *
			*                  MAIN PAGE                  *
			*                                             *
			*                                             *
			**********************************************/
			?>
			<table width="100%">
				<tr>
					<td rowspan="2" width="30%" valign="top">
						<input type="hidden" id="user_id" name="user_id" value="<?=$user_ID?>">
						<table>
							<tr>
								<th>Date</th>
								<th>Weight (<?=$options["unit"]?>s)</th>
								<?
								$date_index = strtotime("now");
								for ($i = 0; $i < 14; ++$i) {
									$weight_array[date("Y-m-d", $date_index)] = array("weight" => "", "trend" => "", "rung" => "");
									$date_index = strtotime("yesterday", $date_index);
								}

								$query = "select date, weight, trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_ID and date > '".date("Y-m-d", strtotime("2 weeks ago"))."' order by date desc";
								$dbweights = $wpdb->get_results($query);
								if ($dbweights) {
									foreach ($dbweights as $weight) {
										$weight_array[$weight->date]["weight"] = $weight->weight;
										$weight_array[$weight->date]["trend"] = $weight->trend;
									}
								}
								foreach ($weight_array as $date => $weight) {
                                    $dif = round($weight["weight"] - $weight["trend"], 1);
									?>
									<tr>
										<td nowrap align="right"><?=date("D n/j", strtotime($date))?></td>
										<td><div class="weightedit <?=($weight["weight"]?"":"blankedit")?>" id="weight_<?=strtotime($date)?>"><?=($weight["weight"]?round($weight["weight"], 1) . "<span class=\"trend_dif ".(($dif < 0)?"good_trend":"bad_trend")."\">$dif</span>":"click here to add a weight")?></div></td>
									</tr>
									<?
								}
								?>
							</tr>
						</table>
					</td>
					<td align="center" valign="top">
                        <div id="togo">
                        <?
							include(dirname(__FILE__).'/ajax_togo.php');
						?>
                        </div>
						<img id="main_graph" src="<?=PLUGIN_FOLDER_URL?>/hackersdiet_chart.php?user=<?=$user_ID?>&weeks=2&goal=<?=$options["goal_weight"]?>">
						<br>
					</td>
				</tr>
				<tr>
					<td align="center" valign="top" height="100%">
						<div id="blurb">
						<?
							include(dirname(__FILE__).'/ajax_blurb.php');
						?>
						</div>
						<br>
					</td>
				</tr>
			</table>
			<table width="100%">
				<tr>
					<td width="25%">
						<div id="hackdiet_zeitgeist" align="left">
							<h2><?php _e('More &raquo;'); ?></h2>
							<div>
								<h3><?php _e('Charts'); ?></h3>
								<?
								$report_url = $_SERVER['PHP_SELF']."?page=hackersdiet.php&section=reports";
								?>
								<ul>
									<li><a href="<?=$report_url . "&start_date=".date("Y-m-01")."&end_date=".date("Y-m-t")?>">Monthly</a></li>
									<li><a href="<?=$report_url . "&start_date=".date("Y-m-01", strtotime("-2 months"))."&end_date=".date("Y-m-t")?>">Quarterly</a></li>
									<li><a href="<?=$report_url . "&start_date=".date("Y-01-01")."&end_date=".date("Y-m-d")?>">Year to date</a></li>
									<li><a href="<?=$report_url . "&show=all"?>">Complete history</a></li>
								</ul>
							</div>
							<div>
								<h3><?php _e('Updates'); ?></h3>
								<?
									if ( file_exists(ABSPATH . WPINC . '/rss.php') )
										require_once(ABSPATH . WPINC . '/rss.php');
									else
										require_once(ABSPATH . WPINC . '/rss-functions.php');

									$rss = fetch_rss('http://www.afex2win.com/stuff/hackersdiet/feed/');
									if ( isset($rss->items) && 0 != count($rss->items) ) {
										$rss->items = array_slice($rss->items, 0, 3);
										echo "<ul>";
										foreach ($rss->items as $item ) {
											?>
											<li><a href='<?php echo wp_filter_kses($item['link']); ?>'><?php echo wp_specialchars($item['title']); ?></a> &#8212; <?php printf(__('%s ago'), human_time_diff(strtotime($item['pubdate'], time() ) ) ); ?></li>
											<?
										}
										echo "</ul>";
									}
								?>
                                <br/><br/>(Your version is <?= $hd_version ?>)
							</div>
						</div>
					</td>
					<td>
						<fieldset class="options">
							<legend>Settings</legend>
							<form method="post">
								<table id="weighttable" width="100%">
									<colgroup width="20%" align="right"></colgroup>
                                    <colgroup width="30%" align="left"></colgroup>
                                    <colgroup width="20%" align="right"></colgroup>
                                    <colgroup width="30%" align="left"></colgroup>
                                    <!--
									<tr>
										<td><b>Unit:</b></td>
										<td>
											<input type="hidden" id="old_unit" name="old_unit" value="<?=$options["unit"]?>">
											<input type="radio" id="unit" name="unit" value="lb" <?=($options["unit"]=="lb"?"checked":"")?>>&nbsp;Pounds (lb)&nbsp;
											<input type="radio" id="unit" name="unit" value="kg" <?=($options["unit"]=="kg"?"checked":"")?>>&nbsp;Kilograms (kg)
										</td>
									</tr>
                                    -->
									<tr>
										<td><b>Goal Weight:</b></td>
										<td><input type="text" id="goal_weight" name="goal_weight" value="<?=$options["goal_weight"]?>"></td>
                                        <td>Maintenance mode?</td>
                                        <td>
                                            <input type="checkbox" id="maint_mode" name="maint_mode" <?= ($options["maint_mode"]?"checked":"") ?>><br />
                                            <?
                                            if ($options["goal_weight"] && $current_trend <= $options["goal_weight"] && !$options["maint_mode"]) {
                                                echo "<span class=\"bad_trend\">Just hit your goal? Select Maintenance mode.</span>";
                                            }
                                            ?>
                                        </td>
									</tr>
									<tr>
										<td><b>Goal Date:</b></td>
										<td><input type="text" id="goal_date" name="goal_date" value="<?=($_POST["goal_date"] ? $_POST["goal_date"] : $options["goal_date"])?>" class="format-y-m-d divider-dash range-low-<?= date("Y-m-d") ?>"></td>
									</tr>
									<tr>
										<td colspan="4">
											<p class="submit"><input type="submit" value="save settings &gt;" /></p>
										</td>
									</tr>
								</table>
							</form>
						</fieldset>
					</td>
				</tr>
			</table>
			<?
		}
        ?>

	</div>
	<?
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
add_action('activate_hackersdiet/hackersdiet.php', 'hackdiet_install');
add_action('admin_head',                           'hackdiet_js');

add_action('widgets_init',                         'hackdiet_widget_init');

?>