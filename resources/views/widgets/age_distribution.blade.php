<!-- resources/views/widgets/age_distribution.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Age Distribution</h3>
    </div>
    <div class="box-body">
        <canvas id="ageDistributionChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('ageDistributionChart').getContext('2d');
        var ageDistributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($ageDistribution->keys()),
                datasets: [{
                    label: 'Users by Age',
                    data: @json($ageDistribution->values()),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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
                            text: 'Age'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Users'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
