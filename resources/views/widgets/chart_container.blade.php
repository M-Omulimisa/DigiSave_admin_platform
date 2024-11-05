<div class="row">
    <div class="col-md-12">
        <div class="chart-container" style="padding: 20px; background-color:white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.chartjs', [$Male, $Female])
        </div>
    </div>
</div>

<div class="row mt-4"> <!-- Add margin-top for spacing between rows -->
    <div class="col-md-12">
        <div class="chart-container" style="padding: 20px; background-color:white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            @include('admin.line_chartjs', ['monthYearList' => $monthYearList, 'totalSavingsList' => $totalSavingsList])
        </div>
    </div>
</div>
