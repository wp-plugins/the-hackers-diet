<?php
// get our db settings without loading all of wordpress every save
$html = implode('', file(str_replace('wp-content/plugins/hackersdiet/weight_json.php', 'wp-config.php', $_SERVER['SCRIPT_FILENAME'])));
$html = str_replace ("require_once", "// ", $html);
$html = str_replace ("<?php", "", $html);
$html = str_replace ("?>", "", $html);
eval($html);

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$weeks = $_GET['weeks'];
$user_id = $_GET['user_id'];
$goal = $_GET['goal'];

$query = "SELECT date, weight, trend, \"$goal\" as goal FROM ".$table_prefix."hackdiet_weightlog WHERE wp_id = $user_id and date > \"".date("Y-m-d", strtotime("$weeks weeks ago"))."\" order by date asc";
$res = mysql_query($query);
while ($row = mysql_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
?>