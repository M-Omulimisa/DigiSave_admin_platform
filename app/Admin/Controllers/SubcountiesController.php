<?php

namespace App\Admin\Controllers;

use App\Models\Subcounty;
use App\Models\District;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class SubcountiesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Subcounties';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Subcounty());

        $grid->column('id', __('ID'));

        // Transform subcounty name to sentence case
        $grid->column('sub_county', __('Subcounty Name'))->display(function ($name) {
            return ucfirst(strtolower($name));
        });

        // Transform district name to sentence case
        $grid->column('district.name', __('District'))->display(function ($name) {
            return ucfirst(strtolower($name));
        });

        // Add search filter
        $grid->filter(function($filter){
            // Disable the default id filter
            $filter->disableIdFilter();

            // Add a dropdown filter for districts with sentence case names
            $filter->equal('district_id', __('District'))->select(District::pluck('name', 'id')->transform(function ($name) {
                return ucfirst(strtolower($name));
            }));

            // You can add more filters as needed
        });

        // Add other configurations as needed

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Subcounty::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('sub_county', __('Subcounty Name'));
        $show->field('district.name', __('District'));

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
    $form = new Form(new Subcounty());

    // Use a textarea for inputting multiple subcounty names, instructing the user to separate names by commas or new lines.
    $form->textarea('sub_county_names', __('Subcounty Names'))
         ->help('Enter multiple subcounty names separated by commas or new lines.');

    $form->select('district_id', __('District'))->options(District::pluck('name', 'id'));

    // Handle the custom logic for creating multiple Subcounty entities on form submission.
    $form->saving(function (Form $form) {
        // Prevent the default saving mechanism from proceeding since we're handling it manually.
        $form->model()->exists = true; // A trick to prevent Laravel Admin from attempting to save directly.

        $subCountyNames = preg_split('/\r\n|[\r\n,]/', request()->input('sub_county_names')); // Split input by new line or comma.
        $districtId = request()->input('district_id');

        foreach ($subCountyNames as $subCountyName) {
            $subCountyName = trim($subCountyName);
            if (!empty($subCountyName)) {
                Subcounty::create([
                    'sub_county' => $subCountyName,
                    'district_id' => $districtId,
                ]);
            }
        }

        // After saving, redirect to a given path to avoid trying to save the non-existent 'sub_county_names' input as a field.
        return redirect(admin_url('subcounties'))->with('status', 'Subcounties added successfully!');
    });

    return $form;
}

}
