<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Crop;
use App\Models\Garden;
use App\Models\GardenActivity;
use App\Models\Group;
use App\Models\Location;
use App\Models\Person;
use App\Models\Sacco;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Faker\Factory as Faker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use SplFileObject;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $admin = Admin::user();
        $saccoId = $admin->sacco_id; 
        $usersCount = User::where('sacco_id', $saccoId)->count();
        $sacco = Sacco::find($saccoId);
    
        $pwdUsersCount = User::where('sacco_id', $saccoId)
                            ->where('pwd', 'yes')
                            ->count();
    
        // Assuming 'establishment_date' is the correct field name in the Sacco model
        $dateEstablished = $sacco->created_at ?? 'N/A'; 
        $dateEstablished = $dateEstablished->format('Y-m-d');
    
        $content->row(function (Row $row) use ($usersCount, $dateEstablished, $pwdUsersCount) {
            $row->column(3, function (Column $column) use ($usersCount, $dateEstablished) {
                $column->append(view('widgets.box-5', [
                    'is_dark' => false,
                    'title' => 'Registered Members',
                    // 'sub_title' => 'Established on ' . $dateEstablished,
                    'number' => number_format($usersCount),
                    'sub_title' => '',
                    'link' => 'javascript:;',
                    'title_style' => 'margin-bottom: 5px;', 
                ]));
            });
            
            $row->column(3, function (Column $column) use ($pwdUsersCount) {
                $column->append(view('widgets.box-5', [
                    'is_dark' => false,
                    'title' => 'Number of PWDs',
                    'sub_title' => '',
                    'number' => number_format($pwdUsersCount),
                    'link' => 'javascript:;'
                ]));
            });
        });
    
        return $content;
    }
}    
