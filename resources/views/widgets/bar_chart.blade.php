<!-- resources/views/widgets/accounts_created_per_month.blade.php -->
<div class="chart-container" style="padding: 20px; background-color: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
    <div id="accountsCreatedPerMonthChartContainer" style="max-width: 100%; height: 400px;"></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Highcharts.chart('accountsCreatedPerMonthChartContainer', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Members Registered per Month',
                style: {
                    fontSize: '18px',
                    color: '#333',
                    fontWeight: 'bold'
                }
            },
            xAxis: {
                categories: {!! json_encode($registrationDates) !!},
                title: {
                    text: 'Month-Year',
                    style: {
                        fontSize: '14px',
                        color: '#333'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Number of Accounts',
                    style: {
                        fontSize: '14px',
                        color: '#333'
                    }
                },
                labels: {
                    formatter: function() {
                        return this.value;
                    }
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    }
                },
                spline: {
                    marker: {
                        enabled: true,
                        radius: 4,
                        symbol: 'circle'
                    }
                }
            },
            series: [{
                type: 'column',
                name: 'Accounts',
                data: {!! json_encode(array_values($registrationCounts)) !!},
                colorByPoint: true,
                colors: Highcharts.getOptions().colors.map(color => Highcharts.color(color).brighten(0.1).get())
            }, {
                type: 'spline',
                name: 'Trend',
                data: {!! json_encode(array_values($registrationCounts)) !!},
                color: '#FF5733',
                marker: {
                    enabled: true,
                    radius: 5,
                    symbol: 'circle'
                },
                lineWidth: 2,
                tooltip: {
                    valueSuffix: ' accounts'
                }
            }],
            credits: {
                enabled: false
            },
            legend: {
                enabled: true
            }
        });
    });
</script>
