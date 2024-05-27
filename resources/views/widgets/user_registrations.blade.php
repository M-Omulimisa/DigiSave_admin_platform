<!-- resources/views/widgets/user_registrations.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Accounts created over time</h3>
    </div>
    <div class="box-body">
        <div id="userRegistrationsChart" style="height: 300px;"></div>
    </div>
</div>

<!-- Include Highcharts and Accessibility Module -->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        Highcharts.chart('userRegistrationsChart', {
            chart: {
                type: 'line',
                height: 300
            },
            title: {
                text: 'Accounts created over time'
            },
            xAxis: {
                categories: @json($registrationDates),
                title: {
                    text: 'Date'
                }
            },
            yAxis: {
                title: {
                    text: 'Users registered'
                },
                min: 0
            },
            series: [{
                name: 'Accounts',
                data: @json($registrationCounts),
                color: 'rgba(75, 192, 192, 1)'
            }],
            tooltip: {
                valueSuffix: ' users'
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: true
                }
            },
            accessibility: {
                enabled: false
            }
        });
    });
</script>
