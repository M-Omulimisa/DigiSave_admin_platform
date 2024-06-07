@php
function getRandomColor($id) {
    $colors = ['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#A133FF', '#33FFF3', '#FFB833', '#A1FF33', '#FF5733'];
    return $colors[$id % count($colors)];
}

function formatBalance($balance) {
    return 'UGX ' . number_format($balance, 2);
}

function sentenceCase($string) {
    return ucwords(strtolower($string));
}
@endphp

<div class="card" style="padding: 5px; background-color: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
    <div class="card-header" style="font-size: 14px; color: #333; font-weight: bold;">
        Top Saving Groups
    </div>
    <div class="card-body px-5" style="padding-right: 10px; padding-left: 10px;">
        @foreach($topSavingGroups as $group)
            <div class="d-flex justify-content-between align-items-center group-row" style="padding: 10px 0; border-bottom: 1px solid #ddd; width: 100%; margin-bottom: 2px;">
                <div class="d-flex align-items-center" style="flex: 1;">
                    <div class="avatar-circle" style="background-color: {{ getRandomColor($group->id) }}; font-size: 12px; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                        {{ strtoupper(substr($group->first_name, 0, 1)) }}{{ strtoupper(substr($group->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: bold; font-size: 12px; color: #333;">
                            {{ sentenceCase($group->first_name) }} {{ sentenceCase($group->last_name) }}
                        </div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #666;">
                    {{ formatBalance($group->balance) }}
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .avatar-circle {
        font-size: 16px;
        font-weight: bold;
    }
    .group-row {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .group-row .d-flex {
        display: flex;
        align-items: center;
        justify-content: flex-start; /* Ensures alignment to the left */
    }
</style>
