<!-- resources/views/widgets/gender_distribution.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Gender Distribution</h3>
    </div>
    <div class="box-body">
        <canvas id="genderDistributionChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('genderDistributionChart').getContext('2d');
        var genderDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: @json($genderDistribution->keys()),
                datasets: [{
                    label: 'Users by Gender',
                    data: @json($genderDistribution->values()),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>
