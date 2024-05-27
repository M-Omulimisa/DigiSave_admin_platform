<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highcharts Bar Chart</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
</head>
<body>
    <div id="barContainer" style="max-width: 100%; height: 400px;"></div>

    <script>
        $(function () {
            var barContainer = $('#barContainer');
            var monthYearList = {!! json_encode($monthYearList) !!};
            var totalSavingsList = {!! json_encode(array_values($totalSavingsList)) !!};

            Highcharts.chart('barContainer', {
                chart: {
                    type: 'column',
                    height: 400,
                },
                title: {
                    text: 'Total Savings per Month-Year',
                    style: {
                        fontSize: '18px',
                        color: '#333'
                    }
                },
                xAxis: {
                    categories: monthYearList,
                    title: {
                        text: 'Month-Year',
                        style: {
                            fontSize: '14px',
                            color: '#333'
                        }
                    },
                    labels: {
                        style: {
                            fontSize: '12px',
                            color: '#333'
                        }
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Total Savings (UGX)',
                        style: {
                            fontSize: '14px',
                            color: '#333'
                        }
                    },
                    labels: {
                        style: {
                            fontSize: '12px',
                            color: '#333'
                        },
                        formatter: function() {
                            return 'UGX ' + this.value.toLocaleString();
                        }
                    }
                },
                legend: {
                    enabled: true,
                    itemStyle: {
                        fontSize: '14px',
                        color: '#333'
                    }
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y:.1f}</b>',
                    valuePrefix: 'UGX ',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        dataLabels: {
                            enabled: true,
                            formatter: function() {
                                return 'UGX ' + this.y.toLocaleString();
                            },
                            style: {
                                fontSize: '12px',
                                color: '#333'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Total Savings',
                    data: totalSavingsList,
                    colorByPoint: true,
                    colors: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)'
                    ]
                }]
            });
        });
    </script>
</body>
</html>
