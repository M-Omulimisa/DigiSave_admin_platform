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
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Total VSLAs</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $totalSaccos }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Village Agents</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $villageAgents }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Organisations</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $organisationCount }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Current Users</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $totalMembers }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">PWD Users</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $totalPwdMembers }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="green-background" style="padding: 5px; border-radius: 5px;">
                    <h4 style="color: white; font-family: 'Arial', sans-serif; text-align: center; margin: 0; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">Percentage of Youths</h4>
                </div>
                <h3 style="margin-top: 10px; text-align: center;">{{ $youthMembersPercentage }}%</h3>
            </div>
        </div>
    </div>
</div>
    
