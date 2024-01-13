<style>
    .green-background {
        background-color: green;
        padding: 5px;
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

    .bg-white .green-background p a {
        color: white;
    }

    .bg-white:hover .green-background {
        color: white;
    }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $totalSaccos }}</h3>
                <div class="green-background">
                    <p><a href="{{ $totalSaccosLink }}">Total VSLAs</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $villageAgents }}</h3>
                <div class="green-background">
                    <p><a href="{{ $villageAgentsLink }}">Village Agents</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $organisationCount }}</h3>
                <div class="green-background">
                    <p><a href="{{ $organisationCountLink }}">Organisations</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $totalMembers }}</h3>
                <div class="green-background">
                    <p><a href="{{ $totalMembersLink }}">Current Users</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $totalPwdMembers }}</h3>
                <div class="green-background">
                    <p><a href="{{ $totalPwdMembersLink }}">PWD Users</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <h3>{{ $youthMembersPercentage }}%</h3>
                <div class="green-background">
                    <p><a href="{{ $youthMembersPercentageLink }}">Percentage of Youths</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
