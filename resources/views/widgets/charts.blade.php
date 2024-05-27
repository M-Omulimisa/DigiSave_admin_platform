<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div id="registrationChart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div id="topGroupsChart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div id="genderSavingsChart"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
    Highcharts.chart('registrationChart', {
        chart: {
            type: 'line'
        },
        title: {
            text: 'Number of Accounts Registered'
        },
        xAxis: {
            categories: {!! json_encode($months) !!}
        },
        yAxis: {
            title: {
                text: 'Number of Registrations'
            }
        },
        series: [{
            name: 'Registrations',
            data: {!! json_encode($registrationCounts) !!},
            color: '#7cb5ec'
        }],
        accessibility: {
            enabled: false
        }
    });

    Highcharts.chart('topGroupsChart', {
        chart: {
            type: 'bar'
        },
        title: {
            text: 'Top Saving Groups'
        },
        xAxis: {
            categories: {!! json_encode($groupNames) !!}
        },
        yAxis: {
            title: {
                text: 'Total Savings'
            }
        },
        series: [{
            name: 'Savings',
            data: {!! json_encode($groupSavings) !!},
            color: '#90ed7d'
        }],
        accessibility: {
            enabled: false
        }
    });

    Highcharts.chart('genderSavingsChart', {
        chart: {
            type: 'pie'
        },
        title: {
            text: 'Savings by Gender'
        },
        series: [{
            name: 'Savings',
            colorByPoint: true,
            data: [{
                name: 'Female Savings',
                y: {{ $femaleSavings }},
                color: '#f45b5b',
                sliced: true,
                selected: true
            }, {
                name: 'Male Savings',
                y: {{ $maleSavings }},
                color: '#2b908f'
            }]
        }],
        accessibility: {
            enabled: false
        }
    });
</script>
