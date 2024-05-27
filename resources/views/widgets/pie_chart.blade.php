<!-- resources/views/widgets/groups_highest_balances.blade.php -->
<div class="chart-container" style="padding: 20px; background-color:white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
    <div id="groupsHighestBalancesChartContainer" style="max-width: 100%; height: 400px;"></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Highcharts.chart('groupsHighestBalancesChartContainer', {
            chart: {
                type: 'pie',
                height: 400,
            },
            title: {
                text: 'Groups with Highest Balances',
                style: {
                    fontSize: '18px',
                    color: '#333'
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
                        }
                    }
                }
            },
            series: [{
                name: 'Balances',
                data: {!! json_encode($groupBalances) !!},
            }],
            credits: {
                enabled: false
            }
        });
    });
</script>
