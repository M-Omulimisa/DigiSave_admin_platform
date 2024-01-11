<style>
    .green-background {
        background-color: green;
        padding: 10px; 
        border-radius: 5px; 
    }

    .bg-white {
        transition: color 0.3s, font-size 0.3s, font-weight 0.3s; /* Transition effect */
    }

    .bg-white:hover {
        color: black; 
        font-size: 16px; 
        font-weight: bold;
    }

    .bg-white .green-background {
        color: white; 
    }

    .bg-white:hover .green-background {
        color: white; 
    }

</style>

<div class="col-md-3">
    <div class="small-box bg-white">
        <div class="inner">
            <h3>{{ $totalSaccos }}</h3>
            <div class="green-background">
                <p>Total VSLAs</p>
            </div>
        </div>
    </div>
</div>
<div class="col-md-3">
    <div class="small-box bg-white">
        <div class="inner">
            <h3>{{ $totalMembers }}</h3>
            <div class="green-background">
                <p>Current Users</p>
            </div>
        </div>
    </div>
</div>
<div class="col-md-3">
    <div class="small-box bg-white">
        <div class="inner">
            <h3>{{ $totalPwdMembers }}</h3>
            <div class="green-background">
                <p>PWD Users</p>
            </div>
        </div>
    </div>
</div>
<div class="col-md-3">
    <div class="small-box bg-white">
        <div class="inner">
            <h3>{{ $youthMembersPercentage }}%</h3>
            <div class="green-background">
                <p>Percentage of Youths</p>
            </div>
        </div>
    </div>
</div>
