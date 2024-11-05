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
        $(function() {
            var barContainer = $('#barContainer');
            var monthYearList = {!! json_encode($monthYearList) !!};
            var totalSavingsList = {!! json_encode($totalSavingsList) !!};

            // Prepare data for male and female savings
            var maleSavings = monthYearList.map(function(monthYear) {
                return totalSavingsList[monthYear] ? totalSavingsList[monthYear]['men'] : 0;
            });
            var femaleSavings = monthYearList.map(function(monthYear) {
                return totalSavingsList[monthYear] ? totalSavingsList[monthYear]['women'] : 0;
            });

            Highcharts.chart('barContainer', {
                chart: {
                    type: 'column',
                    height: 400,
                },
                title: {
                    text: 'Total Savings per Month-Year by Sex',
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
                    pointFormat: '{series.name}: <b>UGX {point.y:,.1f}</b>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        dataLabels: {
                            enabled: true,
                            inside: false,
                            formatter: function() {
                                return 'UGX ' + this.y.toLocaleString();
                            },
                            style: {
                                fontSize: '12px',
                                color: '#333'
                            },
                            crop: false,
                            overflow: 'none'
                        }
                    }
                },
                series: [
                    {
                        name: 'Male Savings',
                        data: maleSavings,
                        color: 'rgba(54, 162, 235, 0.8)' // Blue color for male savings
                    },
                    {
                        name: 'Female Savings',
                        data: femaleSavings,
                        color: 'rgba(255, 99, 132, 0.8)' // Pink color for female savings
                    }
                ]
            });
        });
    </script>
</body>

</html>
