<canvas id="myChart" width="600" height="400" style="max-width: 100%;"
    data-savings="{{ isset($Savings) ? $Savings : 0 }}"
    data-loans="{{ isset($Loans) ? $Loans : 0 }}">
</canvas>

<script>
$(function () {
    var ctx = document.getElementById("myChart").getContext('2d');

    var savingsData = parseFloat($("#myChart").data("savings"));
    var loansData = parseFloat($("#myChart").data("loans"));

    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ["Savings", "Loans"],
            datasets: [{
                label: 'Total amount',
                data: [savingsData, loansData],
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
                text: 'Overall Percentage Savings and Loans'
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
