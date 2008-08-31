window.onload = function() {
    settings = {
        tl: { radius: 10 },
        tr: { radius: 10 },
        bl: { radius: 10 },
        br: { radius: 10 },
        antiAlias: true,
        autoPad: true,
        validTags: ["div"]
    }
    var header_boxen = new curvyCorners(settings, "hd_header_box");
    header_boxen.applyCornersToAll();
    
    settings = {
        tl: { radius: 5 },
        tr: { radius: 5 },
        bl: { radius: 5 },
        br: { radius: 5 },
        antiAlias: true,
        autoPad: true,
        validTags: ["div"]
    }
    var info_box = new curvyCorners(settings, "hd_weight_summary");
    info_box.applyCornersToAll(); 
}

function showCalendar() {
    if (document.getElementById('hd_chart_options').style.display == 'none' || document.getElementById('hd_chart_options').style.display == '') {
        document.getElementById('hd_chart_options').style.display = 'block';
        var myAnim = new YAHOO.util.Anim('hd_chart_options', { 
            height: { to: 150 }
        }, 1, YAHOO.util.Easing.easeOut);
        myAnim.animate();
    } else {
        var myAnim = new YAHOO.util.Anim('hd_chart_options', { 
            height: { to: 0 }
        }, 1, YAHOO.util.Easing.easeIn);
        myAnim.onComplete.subscribe(onHideComplete);
        myAnim.animate();
    }
}
function onHideComplete() {
    document.getElementById('hd_chart_options').style.display = 'none';
}

function onDataResponse(request, response) {
    var min = 99999;
    var max = 0;
    // find min/max of all numerical values
    for (var i=0; i < response.results.length; i++) {
        if (response.results[i].weight < min) {
            min = response.results[i].weight;
        }
        if (response.results[i].trend < min) {
            min = response.results[i].trend;
        }
        if (response.results[i].goal < min) {
            min = response.results[i].goal;
        }
        if (response.results[i].weight > max) {
            max = response.results[i].weight;
        }
        if (response.results[i].trend > max) {
            max = response.results[i].trend;
        }
        if (response.results[i].goal > max) {
            max = response.results[i].goal;
        }
    };
    
    var seriesDef = 
    [
        {
            xField: 'date',
            yField: 'weight',
            displayName: 'Weight',
            style: {
                color: 0x0000ff
            }
        },
        {
            xField: 'date',
            yField: 'trend',
            displayName: 'Trend',
            style: {
                color: 0xff0000
            }
        },
        {
            xField: 'date',
            yField: 'goal',
            displayName: 'Goal',
            style: {
                color: 0x00ff00
            }
        }
    ];
    var yAxis = new YAHOO.widget.NumericAxis();
    yAxis.maximum = parseInt(max) + 5;
    yAxis.minimum = parseInt(min) - 5;
    yAxis.alwaysShowZero = false;
    var myChart = new YAHOO.widget.LineChart( "hd_chart_container", jsonData,
    {
        series: seriesDef,
        yField: "weight",
        yAxis: yAxis
    });
}

function submitWeight() {
    callback = {
        success: function(o) {
            results = YAHOO.lang.JSON.parse(o.responseText);
            document.getElementById('hd_weight_form').style.display = 'none';
            document.getElementById('hd_weight_printout').style.display = 'inline';
            document.getElementById('hd_weight').innerHTML = results.weight;
            document.getElementById('hd_trend').innerHTML = results.trend;
            if (results.change) {
              document.getElementById('hd_change').innerHTML = results.change;
            }
            jsonData.sendRequest('', {success: onDataResponse});
        },
        failure: function(o) {
            var msg = document.getElementById('weight_entry_message');
            msg.style.color = 'red';
            msg.innerHTML = o.responseText;
        }
    }
    var formObj = document.getElementById('weight_entry_form');
    YAHOO.util.Connect.setForm(formObj);
    var cObj = YAHOO.util.Connect.asyncRequest('POST', document.getElementById('file_path').innerHTML + 'weight_save.php', callback);
}