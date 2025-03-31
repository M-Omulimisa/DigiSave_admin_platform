@php
    if (!isset($header)) {
        $header = 'Delete VSLA Group';
    }

    // Ensure other common Laravel Admin template variables are set
    $_user_ = \Encore\Admin\Facades\Admin::user();
    $favicon = \Encore\Admin\Facades\Admin::favicon();
@endphp

@extends('admin::index', ['header' => $header])

@section('content')
<div class="sacco-delete-content">
    <div class="box box-danger" style="margin-bottom: 0;">
    <div class="box-header with-border">
        <h3 class="box-title">Delete VSLA Group: {{ $sacco->name }}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <div class="alert alert-warning">
            <h4><i class="fa fa-warning"></i> Warning!</h4>
                <p>You cannot delete this group until you have deleted all associated records listed below. Follow the recommended deletion order to avoid foreign key constraint errors.</p>
        </div>

        <div class="row">
            <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">VSLA Group Information</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> {{ $sacco->name }}</p>
                                    <p><strong>Phone:</strong> {{ $sacco->phone_number }}</p>
                                    <p><strong>Email:</strong> {{ $sacco->email_address }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Physical Address:</strong> {{ $sacco->physical_address }}</p>
                                    <p><strong>District:</strong> {{ $sacco->district }}</p>
                                    <p><strong>Created At:</strong> {{ $sacco->created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                                </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Deletion Steps - Follow in Order</h3>
                        </div>
                        <div class="panel-body">
                            <div class="alert alert-info">
                                <p><i class="fa fa-info-circle"></i> Follow these steps in order from top to bottom to avoid foreign key constraint errors. Clear all related data before deleting the group.</p>
                                </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="bg-primary">
                                            <th style="width: 5%;">Step</th>
                                            <th style="width: 15%;">Data Type</th>
                                            <th style="width: 10%;">Records</th>
                                            <th style="width: 40%;">Description</th>
                                            <th style="width: 15%;">Dependencies</th>
                                            <th style="width: 15%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deletionOrder as $index => $item)
                                        <tr class="{{ $item['count'] > 0 ? 'warning' : 'success' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <i class="fa {{ $item['icon'] }}"></i>
                                                {{ $item['name'] }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $item['count'] > 0 ? 'bg-red' : 'bg-green' }}">
                                                    {{ $item['count'] }}
                                                </span>
                                            </td>
                                            <td>{{ $item['description'] }}</td>
                                            <td>
                                                @if(count($item['dependent_on']) > 0)
                                                    @foreach($item['dependent_on'] as $dependency)
                                                        <span class="label label-warning">{{ $dependency }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="label label-success">None</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['count'] > 0)
                                                    <form action="{{ route($item['route'], ['id' => $sacco->id, 'model' => $item['model']]) }}"
                                                        method="POST"
                                                        class="delete-related-form"
                                                        style="display: inline-block;"
                                                        onsubmit="return confirmDelete(this, 'Are you sure you want to delete all {{ strtolower($item['name']) }}? This action cannot be undone.');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">
                                                            <i class="fa fa-trash"></i> Delete All {{ $item['name'] }}
                                                        </button>
                                                    </form>
                                                    <a href="{{ admin_url(strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $item['model'])) . 's') . '?sacco_id=' . $sacco->id }}"
                                                    class="btn btn-xs btn-primary" target="_blank">
                                                    <i class="fa fa-eye"></i> View
                                                    </a>
                                                @else
                                                    <span class="label label-success">
                                                        <i class="fa fa-check"></i> No Records
                                                    </span>
                                                @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                                </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <h3 class="panel-title">Final Deletion</h3>
                        </div>
                        <div class="panel-body">
                            <p>Once all related records have been deleted, you can delete the VSLA group.</p>

                            @php
                                $canDelete = true;
                                foreach($deletionOrder as $item) {
                                    if($item['count'] > 0) {
                                        $canDelete = false;
                                        break;
                                    }
                                }
                            @endphp

                            @if($canDelete)
                                <form id="final-delete-form" action="{{ admin_url('saccos/'.$sacco->id) }}" method="post" style="display: inline-block;">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                    <button type="button" id="delete-sacco-btn" class="btn btn-danger">
                                        <i class="fa fa-trash"></i> Delete VSLA Group
                                    </button>
                                                    </form>
                            @else
                                <div class="alert alert-warning">
                                    <p><i class="fa fa-exclamation-triangle"></i> You must delete all associated records before you can delete this VSLA group.</p>
                                </div>
                                <button class="btn btn-danger" disabled>
                                    <i class="fa fa-trash"></i> Delete VSLA Group
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div id="delete-result" class="alert" style="display: none;"></div>

        <div class="row">
                <div class="col-md-12">
                <a href="{{ admin_url('saccos') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Groups
                </a>
            </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Directly target the page content with more specific CSS rules */
    .content {
        padding: 0 !important;
        margin: 0 !important;
    }

    .content > .sacco-delete-content {
        padding: 0 !important;
        margin: 0 !important;
    }

    .content-wrapper > .content {
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Adjust sidebar width and positioning */
    .main-sidebar {
        width: 230px !important;
    }

    .content-wrapper {
        margin-left: 230px !important;
        padding: 0 !important;
    }

    /* Box styles */
    .box.box-danger {
        border-top: 3px solid #dd4b39;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    /* Other styles */
    .badge {
        font-size: 14px;
        padding: 5px 10px;
    }

    .label {
        margin-right: 5px;
        display: inline-block;
        margin-bottom: 5px;
    }

    .panel-title {
        font-weight: bold;
    }

    .table th, .table td {
        vertical-align: middle !important;
    }

    .warning td {
        background-color: #fff3cd;
    }

    #delete-result {
        margin-top: 15px;
    }
</style>

<script>
$(document).ready(function() {
    // Force layout reset to fix the gap
    function fixLayout() {
        $('.content-wrapper').css({
            'margin-left': '50px',
            'padding': '0',
            'margin-right': '0',
            'min-height': '100vh'
        });

        $('.content').css({
            'padding': '0',
            'margin': '0'
        });

        // Fix the sidebar width
        $('.main-sidebar').css({
            'width': '230px'
        });

        // Remove padding in all parent containers
        $('.content > *').css({
            'padding-left': '0',
            'margin-left': '0'
        });

        // Add a hack to remove white space
        $('head').append(`
            <style>
                .content-wrapper > .content {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .content > * {
                    padding-left: 0 !important;
                    margin-left: 0 !important;
                }
                body.fixed .wrapper {
                    overflow: visible !important;
                }
            </style>
        `);
    }

    // Run immediately
    fixLayout();

    // Run again after short delay to catch dynamic updates
    setTimeout(fixLayout, 100);
    setTimeout(fixLayout, 500);

    // Handle the delete button click with Ajax
    $('#delete-sacco-btn').click(function() {
        if (confirm('Are you sure you want to delete this VSLA group? This action cannot be undone.')) {
            var form = $('#final-delete-form');
            var url = form.attr('action');
            var method = 'DELETE';
            var token = $('input[name="_token"]').val();

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: method,
                    _token: token
                },
                success: function(response) {
                    // Redirect to the saccos list page
                    window.location.href = "{{ admin_url('saccos') }}";
                },
                error: function(xhr) {
                    // Display error message
                    var errorMessage = 'An error occurred during deletion.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    $('#delete-result').removeClass('alert-success').addClass('alert-danger').html('<strong>Error:</strong> ' + errorMessage).show();
                }
            });
        }
    });

    // Handle related records deletion with Ajax
    window.confirmDelete = function(form, message) {
        if (confirm(message)) {
            var $form = $(form);
            var url = $form.attr('action');
            var method = 'DELETE';
            var token = $form.find('input[name="_token"]').val();

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: method,
                    _token: token
                },
                success: function(response) {
                    // Reload the current page to refresh the counts
                    window.location.reload();
                },
                error: function(xhr) {
                    // Display error message
                    var errorMessage = 'An error occurred during deletion.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    $('#delete-result').removeClass('alert-success').addClass('alert-danger').html('<strong>Error:</strong> ' + errorMessage).show();
                }
            });

            return false; // Prevent form submission
        }
        return false; // Prevent form submission
    };
    });
</script>
@endsection
