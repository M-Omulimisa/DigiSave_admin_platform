<div class="row">
    <div class="col-md-4">
        <div class="chart-container" style="padding: 20px; background-color:white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.chartjs', [$Male, $Female])
        </div>
    </div>

    <div class="col-md-8">
        <div class="chart-container" style="padding: 20px; background-color:white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.line_chartjs', ['monthYearList' => $monthYearList, 'totalSavingsList' => $totalSavingsList])
        </div>
    </div>
</div>
