<div class="row">
    <!-- First Chart (Narrower) -->
    <div class="col-md-2">
        <div class="chart-container" style="padding: 20px; background-color: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.chartjs', [$Male, $Female])
        </div>
    </div>

    <!-- Line Chart (Wider) -->
    <div class="col-md-10">
        <div class="chart-container" style="padding: 20px; background-color: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.line_chartjs', ['monthYearList' => $monthYearList, 'totalSavingsList' => $totalSavingsList])
        </div>
    </div>
</div>
