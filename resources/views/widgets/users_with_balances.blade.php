<!-- resources/views/widgets/user_balance_chart.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Top 10 Saving Groups</h3>
    </div>
    <div class="box-body">
        <canvas id="userBalanceChart" style="height: 300px;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('userBalanceChart').getContext('2d');
        var userBalanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($userBalances->pluck('name')),
                datasets: [{
                    label: 'Total Savings',
                    data: @json($userBalances->pluck('balance')),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Saving Group'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Balance'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
