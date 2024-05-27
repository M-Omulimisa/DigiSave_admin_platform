<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highcharts Pie Chart with Percentage Labels</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
</head>
<body>
    <div id="container" style="max-width: 100%; height: 400px;"
        data-male="{{ isset($Male) ? $Male : 0 }}"
        data-female="{{ isset($Female) ? $Female : 0 }}">
    </div>

    <script>
        $(function () {
            var container = $('#container');
            var maleData = parseFloat(container.data('male'));
            var femaleData = parseFloat(container.data('female'));

            Highcharts.chart('container', {
                chart: {
                    type: 'pie',
                    height: 400,
                },
                title: {
                    text: 'Overall Percentage Savings Made by Each Gender'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            connectorColor: 'silver',
                            style: {
                                fontSize: '16px', // Increase the font size
                                fontWeight: 'bold', // Make the font bold
                                color: '#333', // Set a custom color for the labels
                            }
                        }
                    }
                },
                series: [{
                    name: 'Savings',
                    data: [
                        { name: 'Male', y: maleData, color: '#36a2eb' },
                        { name: 'Female', y: femaleData, color: '#ff6384' }
                    ]
                }]
            });
        });
    </script>
</body>
</html>
