<?
if (!$wpdb) {
	// get our db settings without loading all of wordpress every time
	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	$html = str_replace ("?>", "", $html);
	eval($html);

	mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME);

	if (is_numeric($_POST['user'])) {
    	$user_id = $_POST["user"];   
    } else {
        exit;
    }
} else {
	$user_id = $user_ID;
}

require_once(dirname(__FILE__).'/hackersdiet_lib.php');

if (!$options) {
    $options = hackdiet_get_options($user_id);
}

if ($_GET["start_date"] and $_GET["end_date"]) {
	$stats = generate_stats($user_id, $_GET["start_date"], $_GET["end_date"], $options["unit"], $options["goal_weight"], $options["goal_date"]);
} else {
	$stats = generate_stats($user_id, date("Y-m-d", strtotime("2 weeks ago")), date("Y-m-d", strtotime("today")), $options["unit"], $options["goal_weight"], $options["goal_date"]);
}

if ($stats) {
	echo "Weekly ".($stats["weekly_change"] > 0?"gain":"loss").": ".abs($stats["weekly_change"])." ".$options["unit"]."s.";
	echo " - ";
	echo "Daily ".($stats["daily_variance"] > 0?"excess":"deficit").": ".abs($stats["daily_variance"])." calories.";
	echo "<br>";
	if (!$options["maint_mode"] && $options["goal_weight"]) {
		if ($stats["on_target"]) {
            // going good
			echo "You will reach your goal of ".$options["goal_weight"]." ".$options["unit"]."s on ".$stats["new_date"].".";
			if ($options["goal_date"]) {
				$days_early = round((strtotime($options["goal_date"])-strtotime($stats["new_date"]))/60/60/24);

				if ($days_early > 0) {
					echo " (".$days_early." days early!)";
				} else {
					echo " (Exactly!)";
				}
			}
		} else if ($options["goal_date"]) {
            // gaining weight and we have a point of reference
			if ($stats["new_date"]) {
				echo "You won't reach your goal of ".$options["goal_weight"]." ".$options["unit"]."s until ".date("F jS, Y", strtotime($stats["new_date"])).".";
			} else {
				echo "You will never reach your goal of ".$options["goal_weight"]." ".$options["unit"]."s at this rate.";
			}
			echo "<br>";
			echo "Adjust to a ".abs($stats["daily_variance"] + $stats["extra_variance"])." daily calorie ".($stats["daily_variance"] + $stats["extra_variance"] > 0?"excess":"deficit")." to meet your goal.";
			if ($stats["new_date"]) {
				echo ".. or just wait longer!";
			}
		} else {
            // gaining weight in general
            echo "Warning: You are moving away from your goal.  Change your daily excess into a deficit in order to get back on track!";
        }
	}
}
?>