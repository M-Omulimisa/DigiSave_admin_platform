<?php

namespace App\Admin\Controllers;

use App\Models\District;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class DistrictsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Districts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new District());

        $grid->column('id', __('ID'));
        $grid->column('name', __('District Name'));

        // Add other columns as needed

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
        $show = new Show(District::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('District Name'));

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
        $form = new Form(new District());
    
        $form->text('name', __('District Name'));
    
        $form->file('csv_file', 'CSV File');
    
        $form->saving(function (Form $form) {
            $csvFile = $form->csv_file;
            if ($csvFile) {
                try {
                    // Move the uploaded file to a temporary directory
                    $fileName = uniqid() . '.' . $csvFile->getClientOriginalExtension();
                    $csvFile->move(storage_path('temp'), $fileName);
    
                    // Get the full path to the uploaded file
                    $filePath = storage_path('temp') . '/' . $fileName;
    
                    // Read the CSV file data with header
                    $csvData = array_map('str_getcsv', file($filePath));
                    $headers = array_shift($csvData);
    
                    // Remove the temporary file
                    unlink($filePath);
    
                    // Debugging output
                    echo "<pre>";
                    print_r($csvData);
                    echo "</pre>";
    
                    $districtIndex = 1; 
                    $districtNames = [];
                    foreach ($csvData as $row) {
                        $districtName = $row[$districtIndex] ?? null;
    
                        // Save district names to an array for debugging
                        if ($districtName) {
                            $districtNames[] = $districtName;
                        }
                    }
    
                    // Debugging output: Print all district names
                    echo "<pre>";
                    print_r($districtNames);
                    echo "</pre>";
    
                    // Save district names using District model
                    foreach ($districtNames as $name) {
                        echo "<pre>";
                        print_r($name);
                        echo "</pre>";
                        $existingDistrict = District::where('name', $name)->first();
                        if (!$existingDistrict) {
                            // If the district doesn't exist, create a new record
                            District::create(['name' => $name]);
                        }
                    }
    
                    die("Districts successfully saved");
    
                } catch (\Exception $e) {
                    // Handle any exceptions
                    die("Error: " . $e->getMessage());
                }
            }
        });
    
        return $form;
    }
    

    /**
     * Read CSV file data.
     *
     * @param string $filePath
     * @return array
     */
    private function readCSV($filePath)
    {
        $csvData = [];
        if (($handle = fopen($filePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $csvData[] = $data;
            }
            fclose($handle);
        }
        return $csvData;
    }
}
