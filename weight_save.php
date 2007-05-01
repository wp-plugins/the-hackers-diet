<?
// get our db settings without loading all of wordpress every save
$html = implode('', file("../../../wp-config.php"));
$html = str_replace ("require_once", "// ", $html);
$html = str_replace ("<?php", "", $html);
$html = str_replace ("?>", "", $html);
eval($html);

if (isset($_POST["id"]) && isset($_POST["user"]) && is_numeric($_POST["user"]) && isset($_POST["content"]) && is_numeric($_POST["content"])) {
	$date = substr($_POST["id"], 7);
	$user_id = $_POST["user"];
	$weight = round($_POST["content"], 1);
} else {
	//print_r($_POST);
	echo "Please enter a valid number for your weight.";
	exit;
}

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$query = "update ".$table_prefix."hackdiet_weightlog set weight = $weight where wp_id = $user_id and date = \"".date("Y-m-d", $date)."\"";
mysql_query($query);
if (mysql_affected_rows() != 1) {
	// record doesn't exist yet, lets create it
	$query = "insert into ".$table_prefix."hackdiet_weightlog set date = \"".date("Y-m-d", $date)."\", weight = $weight, wp_id = $user_id";
	mysql_query($query);
	if (mysql_affected_rows() != 1) {
		echo "Save failed. - " . mysql_error();
		exit();
	} else {
		echo htmlspecialchars($weight);
	}
} else {
	echo htmlspecialchars($weight);
}

$query = "select trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_id and date < \"".date("Y-m-d", $date)."\" order by date desc limit 1";
$result = mysql_query($query);
if (mysql_num_rows($result) == 1) {
	$trend = mysql_result($result, 0);
	$use_first_weight_as_trend = false;
} else {
	// no trends exist below this entry, we must be first.  so in next query, we need to grab today's weight to be trend 1
	$use_first_weight_as_trend = true;
}

$query = "select date, weight, trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_id and date >= \"".date("Y-m-d", $date)."\" order by date asc";
$result = mysql_query($query);
while ($entry = mysql_fetch_assoc($result)) {
	if ($use_first_weight_as_trend) {
		$trend = $entry["weight"];
		$use_first_weight_as_trend = false;
	} else {
		// exponentially smoothed moving average with 10% smoothing
		$trend = $trend + 0.1 * ($entry["weight"] - $trend);
	}
	$entry["trend"] = $trend;
	$weights[] = $entry;
}

foreach ($weights as $entry) {
	$query = "update ".$table_prefix."hackdiet_weightlog set trend = ".round($entry["trend"], 1)." where wp_id = $user_id and date = \"".$entry["date"]."\"";
	mysql_query($query);
}

// 0 will always be the edited date, since the list contains the edited entry + all the ones after it, sorted asc.
$dif = round($weights[0]["weight"] - $weights[0]["trend"], 1);

echo "<span class=\"trend_dif ".(($dif < 0)?"good_trend":"bad_trend")."\">$dif</span>";
?>