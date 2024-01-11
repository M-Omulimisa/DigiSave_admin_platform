<!-- line_chartjs.blade.php -->

<canvas id="lineChart" width="600" height="400" style="max-width: 100%;"></canvas>
<script>
  $(function () {
    var ctx = document.getElementById("lineChart").getContext('2d');
    var lineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthYearList) !!},
            datasets: [{
                label: 'Total Savings',
                data: {!! json_encode(array_values($totalSavingsList)) !!},
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
                title: {
                    display: true,
                    text: 'Monthly total savings'
                },
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        unit: 'month',
                        displayFormats: {
                            month: 'MMM YYYY'
                        }
                    },
                    distribution: 'linear',
                    ticks: {
                        source: 'auto',
                        autoSkip: true
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Date'
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Total Savings'
                    }
                }]
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

</script>
