<?php
include (dirname(__FILE__)."/jpgraph/jpgraph.php");
include (dirname(__FILE__)."/jpgraph/jpgraph_line.php");
include (dirname(__FILE__)."/jpgraph/jpgraph_scatter.php");

// get our db settings without loading all of wordpress every save
$html = implode('', file("../../../wp-config.php"));
$html = str_replace ("require_once", "// ", $html);
$html = str_replace ("<?php", "", $html);
$html = str_replace ("?>", "", $html);
eval($html);

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$weeks = $_GET["weeks"];
$start_date = $_GET["start_date"];
$end_date = $_GET["end_date"];
$goal = $_GET["goal"];
$user_id = $_GET["user"];
$maint_mode = $_GET["maint_mode"];

if ($weeks) {
	$query = "select date, weight, trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_id and date > \"".date("Y-m-d", strtotime("$weeks weeks ago"))."\" order by date asc";
} else if ($start_date and $end_date) {
	$query = "select date, weight, trend from ".$table_prefix."hackdiet_weightlog where wp_id = $user_id and date >= \"$start_date\" and date <= \"$end_date\" order by date asc";
}

$result = mysql_query($query);
if (mysql_num_rows($result)) {
	if (mysql_num_rows($result) == 1) {
		// only one day, gotta finagle the display

		$row = mysql_fetch_assoc($result);

		// fake day before
		$weight_data[] = 0;
		if ($goal > 0) {
			$goal_data[] = $goal;
		}
		$x_data[] = date("n/j", strtotime("yesterday", strtotime($row["date"])));

		// data
		$weight_data[] = $row["weight"];
		if ($goal > 0) {
			$goal_data[] = $goal;
		}
		$x_data[] = date("n/j", strtotime($row["date"]));
		
		// fake day after
		$weight_data[] = 0;
		if ($goal > 0) {
			$goal_data[] = $goal;
		}
		$x_data[] = date("n/j", strtotime("tomorrow", strtotime($row["date"])));
	} else {
		$num_rows = mysql_num_rows($result);
		if ($num_rows <= 7 * 2) { // 0-2 weeks
			$ticks = "daily";
		} else if ($num_rows <= 31 * 4) { // 2 weeks - 4 months
			$ticks = "weekly";
		} else { // 4 months +
			$ticks = "monthly";
		}

		$count = 1;
		while ($row = mysql_fetch_assoc($result)) {
			$weight_data[] = $row["weight"];
			$trend_data[] = $row["trend"];
			if ($goal > 0) {
				$goal_data[] = $goal;
			}
			switch ($ticks) {
				case "weekly":
					if ($count == 1) {
						$x_data[] = date("n/j", strtotime($row["date"]));
					} else {
						$x_data[] = "";
						if ($count == 7) {
							$count = 0;
						}
					}
					break;
				case "monthly":
					if (date("j", strtotime($row["date"])) == "1") {
						$x_data[] = date("n/j", strtotime($row["date"]));						
					} else {
						$x_data[] = "";
					}
					break;
				case "daily":
				default:
					$x_data[] = date("n/j", strtotime($row["date"]));
					break;
			}

			$count++;
		}
	}
} else {
	$trend_data[] = 0;
	$trend_data[] = 0;
	$x_data[] = date("n/j", strtotime("yesterday"));
	$x_data[] = date("n/j", strtotime("today"));
}

if ($weight_data) {
	$lowest_weight = $weight_data[0];
	foreach ($weight_data as $weight_entry) {
		if ($goal) {
			if ($weight_entry < $goal) {
				$to_check = $weight_entry;
			} else {
				$to_check = $goal;
			}
			if ($to_check < $lowest_weight) {
				$lowest_weight = $to_check;
			}
		} else {
			if ($weight_entry < $lowest_weight) {
				$lowest_weight = $weight_entry;
			}
		}
	}
} else {
	$lowest_weight = $trend_data[0] + 1;
}


$graph = new Graph(600,300,"auto");
$graph->SetScale("textlin");
$graph->legend->SetLayout(LEGEND_HOR);
$graph->legend->Pos(0.5, 0.1, "center", "bottom");

if ($x_data) {
	$graph->xaxis->SetTickLabels($x_data); 
}

if ($weight_data) {
	$sp1 = new ScatterPlot($weight_data);
    $sp1->SetLegend("Daily Weight");
	$graph->Add($sp1);
}

if ($trend_data) {
	$trendplot=new LinePlot($trend_data);
	$trendplot->SetColor("red");
    $trendplot->SetLegend("Trend");
	$graph->Add($trendplot);
}

if ($goal > 0) {
	$graph->yaxis->scale->SetAutoMin($lowest_weight - 1); 

	$goalplot = new PlotLine (HORIZONTAL, $goal, "green" ,2);
	$graph->Add($goalplot);

    // PlotLine don't support SetLegend, so we fake it thusly
	$fakegoal=new LinePlot($temp = 0);
	$fakegoal->SetColor("green");
    $fakegoal->SetLegend("Goal");
    $graph->Add($fakegoal);

    if ($maint_mode) {
        $upperplot = new PlotLine (HORIZONTAL, $goal + 2.5, "blue" ,1); 
        $graph->Add($upperplot);
        $lowerplot = new PlotLine (HORIZONTAL, $goal - 2.5, "blue" ,1); 
        $graph->Add($lowerplot);

      	$bound=new LinePlot($temp = 0);
        $bound->SetColor("blue");
        $bound->SetLegend("Boundaries");
        $graph->Add($bound);
    }
}

$graph->Stroke();
?>