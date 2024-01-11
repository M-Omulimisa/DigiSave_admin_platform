<canvas id="myChart" width="600" height="400" style="max-width: 100%;" 
    data-male="{{ isset($Male) ? $Male : 0 }}" 
    data-female="{{ isset($Female) ? $Female : 0 }}">
</canvas>

<script>
$(function () {
    var ctx = document.getElementById("myChart").getContext('2d');

    var maleData = parseFloat($("#myChart").data("male"));
    var femaleData = parseFloat($("#myChart").data("female"));

    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ["Male", "Female"],
            datasets: [{
                label: 'Total savings',
                data: [maleData, femaleData],
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            title: {
                display: true,
                text: 'Overall Percentage Savings Made by Each Gender'
            },
            responsive: true,
            maintainAspectRatio: false, 
            legend: {
                position: 'top',
            },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function (previousValue, currentValue) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue / total) * 100) + 0.5);
                        return percentage + "%";
                    }
                }
            }
        }
    });
});
</script>
