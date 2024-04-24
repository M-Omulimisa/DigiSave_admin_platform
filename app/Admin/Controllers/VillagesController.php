<?php

namespace App\Admin\Controllers;

use App\Models\Village;
use App\Models\Parish;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VillagesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Villages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Village());

        $grid->model()->with('parish.subcounty.district');

        $grid->column('village_id', __('ID'));

        // Transform village name to title case
        $grid->column('village_name', __('Village'))->display(function ($name) {
            return ucwords(strtolower($name));
        });

        // Transform parish name to title case
        $grid->column('parish.parish_name', __('Parish'))->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('parish.subcounty.name', __('Subcounty'))->display(function ($subcounty) {
            return ucwords(strtolower($subcounty['sub_county'])); // Output just the district name
        });

        // $grid->column('subcounty.district.name', __('District'))->display(function ($district) {
        //     return optional($this->district)->district->name ?? '';
        // });


        // Add search filter
        $grid->filter(function($filter){
            // Disable the default id filter
            $filter->disableIdFilter();

            // Add a dropdown filter for parishes with title case names
            $filter->equal('parish_id', __('Parish'))->select(Parish::pluck('parish_name', 'parish_id')->transform(function ($name) {
                return ucwords(strtolower($name));
            }));

            // Add more filters as needed
        });

        // Add other columns or configurations as needed

        return $grid;
    }

    // protected function grid()
    // {
    //     $grid = new Grid(new Village());

    //     $grid->column('village_id', __('ID'));
    //     $grid->column('village_name', __('Village'));
    //     $grid->column('parish.parish_name', __('Parish'));

    //     // Add other columns as needed

    //     return $grid;
    // }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Village::findOrFail($id));

        $show->field('village_id', __('ID'));
        $show->field('village_name', __('Village Name'));
        $show->field('parish.parish_name', __('Parish'));

        // Add other fields as needed

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
{
    $form = new Form(new Village());

    // Assuming `village_names` is a temporary field for user input and doesn't correspond to an actual database column.
    $form->textarea('village_names', __('Village Names'))
         ->help('Enter village names separated by commas. Each name will create a new village entry associated with the selected parish.');

    $form->select('parish_id', __('Parish'))->options(Parish::pluck('parish_name', 'parish_id'));

    // Corrected saving logic
    $form->saving(function (Form $form) {
        // This part of the code will not work as intended because $form->village_names does not map to a database field directly.
        // Instead, you should process the village names manually as shown below:

        $villageNames = explode(',', request()->input('village_names')); // Correctly retrieve the input for village names
        $parishId = request()->input('parish_id'); // Correctly retrieve the parish ID

        foreach ($villageNames as $villageName) {
            $villageName = trim($villageName);
            if (!empty($villageName)) {
                Village::create([
                    'village_name' => $villageName,
                    'parish_id' => $parishId,
                ]);
            }
        }

        // Since we're handling record creation manually, we stop the form from attempting to save a non-existent `village_names` field.
        return redirect()->back();
    });

    return $form;
}
    // protected function form()
    // {
    //     $form = new Form(new Village());

    //     $form->text('village_name', __('Village Name'));
    //     $form->select('parish_id', __('Parish'))->options(Parish::pluck('parish_name', 'parish_id'));

    //     // Add other fields as needed

    //     return $form;
    // }
}
