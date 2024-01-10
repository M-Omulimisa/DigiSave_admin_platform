<?php

namespace App\Admin\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Utils;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class ProductController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Products & Services';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product());


        $grid->column('created_at', __('Posted'))->display(function ($created_at) {
            return Utils::my_date($created_at);
        })->sortable();
        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->column('photo', __('Photo'))
            ->display(function ($avatar) {
                $img = url("storage/" . $avatar);
                return '<img class="img-fluid " style="border-radius: 10px;"  src="' . $img . '" >';
            })
            ->width(80)
            ->sortable();
        $grid->column('name', __('Product Name'));

        $grid->column('details', __('Details'))->hide();
        $grid->column('price', __('Price'));
        $grid->column('offer_type', __('Offer type'));
        $grid->column('state', __('State'));
        $grid->column('category', __('Category'));
        $grid->column('type', __('Contact'));
        $grid->column('subcounty_id', __('Locations'))->display(function ($subcounty_id) {
            $this->subcounty = Location::find($subcounty_id);
            if (!$this->subcounty) {
                return 'Unknown';
            }
            return $this->subcounty->name_text;
        })->sortable();

        $grid->column('administrator_id', __('Posted By'))->display(function ($administrator_id) {
            $this->administrator = Administrator::find($administrator_id);
            if (!$this->administrator) {
                return 'Unknown';
            }
            return $this->administrator->name;
        })->sortable();

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
        $show = new Show(Product::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('administrator_id', __('Administrator id'));
        $show->field('name', __('Name'));
        $show->field('type', __('Type'));
        $show->field('photo', __('Photo'));
        $show->field('details', __('Details'));
        $show->field('price', __('Price'));
        $show->field('offer_type', __('Offer type'));
        $show->field('state', __('State'));
        $show->field('category', __('Category'));
        $show->field('subcounty_id', __('Subcounty id'));
        $show->field('district_id', __('District id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product());

        if (
            (Auth::user()->isRole('staff')) ||
            (Auth::user()->isRole('admin'))
        ) {

            $ajax_url = url(
                '/api/ajax?'
                    . "search_by_1=name"
                    . "&search_by_2=id"
                    . "&model=User"
            );
            $form->select('administrator_id', "Product provider")
                ->options(function ($id) {
                    $a = Administrator::find($id);
                    if ($a) {
                        return [$a->id => "#" . $a->id . " - " . $a->name];
                    }
                })
                ->ajax($ajax_url)->rules('required');
        } else {
            $form->select('administrator_id', __('Product provider'))
                ->options(Administrator::where('id', Auth::user()->id)->get()->pluck('name', 'id'))->default(Auth::user()->id)->readOnly()->rules('required');
        }


        $form->radio('type', __('Item type'))->options([
            'Product' => 'Product',
            'Service' => 'Service',
        ])->rules('required');

        $form->text('name', __('Item name'))->rules('required');
        $form->image('photo', __('Photo'))->rules('required');

        $form->radio('state', __('Item State'))->options([
            'New' => 'New',
            'Used but like new' => 'Used but like new',
            'Used' => 'Used',
        ])->rules('required');

        $form->radio('offer_type', __('Offer type'))->options([
            'For sale' => 'For sale',
            'For hire' => 'For hire/Rent',
        ])->rules('required');

        $form->decimal('price', __('Price (in UGX)'))->rules('required');

        $form->select('subcounty_id', __('Item location'))
            ->rules('required')
            ->options(Location::get_sub_counties_array());


        $form->text('details', __('Details'))->rules('required');

        return $form;
    }
}
