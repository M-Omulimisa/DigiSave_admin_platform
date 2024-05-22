<!-- resources/views/widgets/user_registrations.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Accounts created over time</h3>
    </div>
    <div class="box-body">
        <canvas id="userRegistrationsChart" style="height: 300px;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('userRegistrationsChart').getContext('2d');

        // Create gradient
        var gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 99, 132, 0.2)');
        gradient.addColorStop(0.5, 'rgba(54, 162, 235, 0.2)');
        gradient.addColorStop(1, 'rgba(75, 192, 192, 0.2)');

        var userRegistrationsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($registrationDates),
                datasets: [{
                    label: 'Accounts',
                    data: @json($registrationCounts),
                    backgroundColor: gradient,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Users registered'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
