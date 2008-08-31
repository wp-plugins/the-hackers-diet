<?php

$options = hackdiet_get_options($user_ID);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['form'] == 'settings') {
    	if ($_POST["goal_date"] && !$_POST["goal_weight"]) {
    		// can't plan without a plan
    		$error = "So exactly how much would you like to weigh on " . $_POST["goal_date"] . "?";
    	} else {
            $options["unit"] = $_POST["unit"];
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
}

// find the last entered weight
$today = null;
$last_weight = null;
$query = "SELECT date, weight, trend FROM ${table_prefix}hackdiet_weightlog WHERE wp_id = $user_ID ORDER BY date DESC LIMIT 1";
$last_weight = $wpdb->get_results($query);
if ($last_weight) {
    $last_weight = $last_weight[0];
    if ($last_weight->date == date('Y-m-d')) {
        // last weight entered was today
        $today = $last_weight;
    }
}

// calculate this week's gain/loss
$week_ago = null;
$query = "SELECT trend FROM ${table_prefix}hackdiet_weightlog WHERE wp_id = $user_ID AND date <= '".date('Y-m-d', strtotime('1 week ago'))."' ORDER BY date DESC LIMIT 1";
$week_ago = $wpdb->get_results($query);
if ($week_ago) {
    $week_ago = $week_ago[0];
}

?>
<?php if ($error):?>
<div id="message" class="error fade"><p><strong><?php echo $error?></strong></p></div>
<?php elseif ($message):?>
<div id="message" class="updated fade"><p><strong><?php echo $message?></strong></p></div>
<?php endif;?>
<div id="file_path" style="display: none;"><?= PLUGIN_PATH ?></div>
<div id="hd_header_container">
    <div id="hd_today_entry" class="hd_header_box">
        <div class="hd_header_box_content">
            Today's weight:<br/>
            <span id="hd_weight_printout" style="<?php echo ($today ? 'display:inline;' : 'display:none;' ) ?>" class="hd_header_number">
                <span id="hd_weight">
                    <?php if ($today) echo $today->weight; ?>
                </span>
                <?php echo ' ' . $options['unit'] . 's' ?>
            </span>
            <div id="hd_weight_form">
                <?php if (!$today) {
                    ?>
                    <br/>
                    <form id="weight_entry_form" method="post">
                        <input type="hidden" name="user" value="<?php echo $user_ID;?>" id="weight_entry_user">
                        <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>" id="weight_entry_date">
                        <input type="text" name="weight" value="" id="weight_entry_weight"><br/>
                        <small>(Weight in <?php echo $options['unit'] ?>s)</small><br/>
                        <input type="submit" name="submit" value="Submit" id="weight_entry_submit" onclick="submitWeight(); return false;">
                        <script type="text/javascript" charset="utf-8">
                            YAHOO.util.Event.onDOMReady( function() {
                                document.getElementById('weight_entry_weight').focus();
                            });
                        </script>
                    </form>
                    <?php
                }
                ?>
            </div>
            <span id='weight_entry_message'></span>
        </div>
        <div class="hd_header_box_footnote">
            Edit old weights
        </div>
    </div>
    <div id="hd_current_weight" class="hd_header_box">
        <div class="hd_header_box_content">
            Trend:<br/>
            <span class="hd_header_number">
                <span id="hd_trend">
                <?php
                if ($last_weight) {
                    echo $last_weight->trend;
                } else {
                    echo 'N/A';
                }
                ?>
                </span>
                <?php echo ' ' . $options['unit'] . 's' ?>
            </span>
        </div>
        <div class="hd_header_box_footnote">
            This is how much you actually weigh
        </div>
    </div>
    <div id="hd_change_this_week" class="hd_header_box">
        Loss this week:<br/>
        <span class="hd_header_number">
            <span id="hd_change">
                <?php
                if ($week_ago) {
                    $diff = $last_weight->trend - $week_ago->trend;
                    echo round($diff, 2);
                } else {
                    echo 'N/A';
                }
                ?>
            </span>
            <?php echo ' ' . $options['unit'] . 's' ?>
        </span>
    </div>    
</div>
<br/>
<div id="hd_weight_summary_good" class="hd_weight_summary">
    <?php include 'ajax_blurb.php'; ?>
</div>
<div id="hd_chart">
    <div id="hd_chart_container"></div>
    <div id="hd_chart_types">
        <a href="javascript:showCalendar();">Customize chart view</a>
    </div>
    <div id="hd_chart_options" class="hd_chart_options">
        <div id="calendarContainer"></div>
        <script type="text/javascript" charset="utf-8">
            YAHOO.util.Event.onDOMReady(function() {
                var cal1 = new YAHOO.widget.Calendar("calendarContainer");
                cal1.render();
            });
        </script>
    </div>
</div>
<br/>
<div class='hd_rss_container'>
    <h3 class='hd_rss_title'>
        <span>Plugin News and Updates</span>
        <small>
            <a href="http://wordpress.org/development/">See&nbsp;All</a>
            &nbsp;|&nbsp;
            <img class="rss-icon" src="http://localhost/wp25/wp-includes/images/rss.png" alt="rss icon" />&nbsp;
            <a href="http://wordpress.org/development/feed/">RSS</a>
        </small>
        <br class='clear' />
    </h3>
    <div class='hd_rss_content'><p class="">Loading&#8230;</p></div>
</div>
<div id="hd_settings">
    <h2>Settings</h2>
    <form method="post">
        <input type="hidden" name="form" value="settings" id="form">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Unit</th>
                <td>
                    <input type="hidden" id="old_unit" name="old_unit" value="<?=$options["unit"]?>">
                    <input type="radio" id="unit_lb" name="unit" value="lb" <?=($options["unit"]=="lb"?"checked":"")?> />Pounds<br/>
                    <input type="radio" id="unit_kg" name="unit" value="kg" <?=($options["unit"]=="kg"?"checked":"")?> />Kilograms<br/>
                    <input type="radio" id="unit_st" name="unit" value="st" <?=($options["unit"]=="st"?"checked":"")?> />Stones<br/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Goal Weight (<?=$options['unit']?>)</th>
                <td><input type="text" id="goal_weight" name="goal_weight" value="<?=$options["goal_weight"]?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Goal Date</th>
                <td><input type="text" id="goal_date" name="goal_date" value="<?=($_POST["goal_date"] ? $_POST["goal_date"] : $options["goal_date"])?>" class="format-y-m-d divider-dash range-low-<?= date("Y-m-d") ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Maintenance Mode</th>
                <td>
                    <input type="checkbox" id="maint_mode" name="maint_mode" <?= ($options["maint_mode"]?"checked":"") ?>>
                    <?
                    if ($options["goal_weight"] && $last_weight->trend <= $options["goal_weight"] && !$options["maint_mode"]) {
                        echo "<br/><span style=\"color: red;\">Just hit your goal?<br/>Select Maintenance mode.</span>";
                    }
                    ?>                
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" value="save settings &gt;" />
        </p>
    </form>
</div>
<script type="text/javascript" charset="utf-8">
    YAHOO.widget.Chart.SWFURL = "http://yui.yahooapis.com/2.5.2/build/charts/assets/charts.swf"; 
    var jsonData = new YAHOO.util.DataSource( "/wp25/wp-content/plugins/hackersdiet/weight_json.php?user_id=<?=$user_ID?>&weeks=2&goal=<?=$options["goal_weight"]?>" );
    jsonData.responseType = YAHOO.util.DataSource.TYPE_JSON;
    jsonData.responseSchema =
    {
        fields: [ "date","weight","trend","goal" ]
    };
    
    jsonData.sendRequest('', {success: onDataResponse});
</script>