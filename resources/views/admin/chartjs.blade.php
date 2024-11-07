<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highcharts Pie Chart with Percentage Labels</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Include jQuery and Highcharts libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <!-- Optional exporting module -->
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <!-- Optional export-data module -->
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <style>
        /* Style for the chart container */
        #container {
            max-width: 100%;
            height: 350px;
            background: linear-gradient(135deg, #f3f3f3, #e7e7e7);
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div id="container"
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
                    height: 350,
                    backgroundColor: 'transparent'
                },
                title: {
                    text: 'Distribution of Savings by Gender',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold'
                    }
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
                        shadow: true,
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            connectorColor: 'silver',
                            style: {
                                fontSize: '16px',
                                fontWeight: 'bold',
                                color: '#333',
                                textOutline: 'none'
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
