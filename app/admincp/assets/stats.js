function randomNum(minNum, maxNum) {
    switch (arguments.length) {
        case 1:
            return parseInt(Math.random() * minNum + 1, 10);
            break;
        case 2:
            return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10);
            break;
        default:
            return 0;
            break;
    }
}
Chart.defaults.global.defaultFontColor = "#999";
Chart.defaults.global.defaultFontStyle = "600";
Chart.defaults.scale.gridLines.color = "rgba(0,0,0,.05)";
Chart.defaults.scale.gridLines.zeroLineColor = "rgba(0,0,0,.1)";
Chart.defaults.scale.ticks.beginAtZero = true;
Chart.defaults.global.elements.line.borderWidth = 2;
Chart.defaults.global.elements.point.radius = 4;
Chart.defaults.global.elements.point.hoverRadius = 6;
Chart.defaults.global.tooltips.cornerRadius = 3;
Chart.defaults.global.legend.labels.boxWidth = 5;

function widgetChartBar($id, $url) {
    var chartBarCon = $("#" + $id + " .js-chartjs-bar");
    var chartLinesBarsRadarData = {
        labels: [],
        datasets: [],
    };
    var _datasets = {
        label: "",
        fill: true,
        backgroundColor: "rgba(81,121,214, .3)",
        borderColor: "rgba(81,121,214, 1)",
        pointBackgroundColor: "rgba(81,121,214, 1)",
        pointBorderColor: "#fff",
        pointHoverBackgroundColor: "#fff",
        pointHoverBorderColor: "rgba(81,121,214, 1)",
        data: [],
    };
    iCMS.request
        .get($url + "&CSRF_TOKEN=" + $CSRF_TOKEN)
        .then(function (json) {
            if (json.code) {
                if (json.data.html) {
                    $("#" + $id + " .block-content").html(json.data.html);
                } else {
                    if (json.data.datas) {
                        chartLinesBarsRadarData.datasets = [_datasets];
                        $.each(json.data.datas, function (idx, value) {
                            chartLinesBarsRadarData.labels.push(value[1]);
                            chartLinesBarsRadarData.datasets[0].data.push(value[0]);
                        });
                        if (json.data.title) {
                            chartLinesBarsRadarData.datasets[0].label = json.data.title;
                        }
                        chartLinesBarsRadarData.datasets[0].label += "(" + json.data.total[0] + ")";
                        $("#" + $id + " .badge").text(json.data.total[0]);
                    } else if (json.data.datasets) {
                        chartLinesBarsRadarData.labels = json.data.labels;
                        chartLinesBarsRadarData.datasets = json.data.datasets;
                        $.each(json.data.datasets, function (idx, value) {
                            var opts = $.extend({}, _datasets, value);
                            chartLinesBarsRadarData.datasets[idx] = opts;
                        });
                    }else{

                    }
                    new Chart(chartBarCon, {
                        type: "bar",
                        data: chartLinesBarsRadarData,
                    });
                }
                // iCMS.notify.success('提交成功！');
                // window.location.reload();
            } else {
                iCMS.ui.alert(json.message, 300000);
            }
        })
        .catch(function (error) {
            console.log(error);
            // iCMS.ui.alert(error.message, 300000);
        });
}
