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

	$user_id = $_POST["user"];
} else {
	$user_id = $user_ID;
}

require_once(dirname(__FILE__).'/hackersdiet_lib.php');

$options = hackdiet_get_options($user_id);

if ($options["maint_mode"]){
       echo "Maintenance Mode";
} else {
    if ($options["goal_weight"]) {
        $query = "select trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_id order by date desc limit 1";
        $result = mysql_query($query);
        if (mysql_num_rows($result) == 1) {
            $current_trend = mysql_result($result, 0);
            if ($current_trend) {
                $diff = $current_trend - $options["goal_weight"];
                if ($diff > 0) {
                    echo "<h3 class=\"title\">Keep going! " . ($diff) . " " . $options["unit"] . "s to go!</h3>";
                } else {
                    echo "<h3 class=\"title\">YOU DID IT!</h3>";
                }
            }
        }        
    }
}
?>