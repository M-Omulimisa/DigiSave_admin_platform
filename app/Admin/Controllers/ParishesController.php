<?php

namespace App\Admin\Controllers;

use App\Models\Parish;
use App\Models\Subcounty;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ParishesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Parishes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Parish());

        $grid->model()->with('subcounty.district');

        $grid->column('parish_id', __('ID'));
        $grid->column('parish_name', __('Parish Name'))->display(function ($name) {
            return ucwords(strtolower($name));
        });
        $grid->column('subcounty.sub_county', __('Subcounty'))->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('subcounty.district.name', __('District'))->display(function ($district) {
            return $district['name']; // Output just the district name
        });


        // // Add district name column
        // $grid->column('subcounty.district.name', __('District'))->display(function ($name) {
        //     return $name; // Output the district name directly
        // });

        $grid->filter(function($filter){
            $filter->disableIdFilter();

            $filter->equal('subcounty_id', __('Subcounty'))->select(Subcounty::pluck('sub_county', 'id')->transform(function ($name) {
                return ucwords(strtolower($name));
            }));

            // Add more filters as needed
        });

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
        $show = new Show(Parish::findOrFail($id));

        $show->field('parish_id', __('ID'));
        $show->field('parish_name', __('Parish Name'));
        $show->field('subcounty.sub_county', __('Subcounty'));

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
    $form = new Form(new Parish());

    // Use a textarea for inputting multiple parish names, instructing the user to separate names by commas or new lines.
    $form->textarea('parish_names', __('Parish Names'))
         ->help('Enter multiple parish names separated by commas or new lines.');

    $form->select('subcounty_id', __('Subcounty'))->options(Subcounty::pluck('sub_county', 'id'));

    // Handle the custom logic for creating multiple Parish entities on form submission.
    $form->saving(function (Form $form) {
        // First, cancel the default saving operation to handle it manually.
        $form->model()->exists = true; // Trick to prevent saving the model directly.

        $parishNames = preg_split('/\r\n|[\r\n,]/', request()->input('parish_names')); // Split input by new line or comma.
        $subcountyId = request()->input('subcounty_id');

        foreach ($parishNames as $parishName) {
            $parishName = trim($parishName);
            if (!empty($parishName)) {
                Parish::create([
                    'parish_name' => $parishName,
                    'subcounty_id' => $subcountyId,
                ]);
            }
        }

        // Redirect after saving to prevent the form from trying to save the non-existent 'parish_names' field.
        return redirect(admin_url('parishes'))->with('status', 'Parishes added successfully!');
    });

    return $form;
}

}
