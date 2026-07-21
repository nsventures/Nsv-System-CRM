@extends('layout')
@section('title')
<?= get_label('create_role', 'Create role') ?>
@endsection
<?php

use Spatie\Permission\Models\Permission;
?>
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h4 class="fw-bold mb-0"><?= get_label('create_role', 'Create Role') ?></h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item"><?= get_label('settings', 'Settings') ?></span>
                <span class="breadcrumb-sep">/</span>
                <a class="breadcrumb-item" href="{{url('settings/permission')}}"><?= get_label('permissions', 'Permissions') ?></a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= get_label('create_role', 'Create role') ?></span>
            </nav>
        </div>
    </div>
    
    <div class="alert alert-primary d-flex align-items-center mb-4 alert-dismissible" role="alert">
        <i class="bx bx-info-circle me-2 fs-4"></i>
        <div>
            <a href="javascript:void(0)" class="fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#permission_instuction_modal"><?= get_label('click_for_permission_settings_instructions', 'Click Here for Permission Settings Instructions.') ?></a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <form action="{{url('roles/store')}}" class="form-submit-event" method="POST">
        <input type="hidden" name="redirect_url" value="{{ url('settings/permission') }}">
        @csrf
        
        <div class="row">
            <div class="col-12">
                <!-- Role Configuration Panel -->
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex align-items-center text-secondary">
                        <i class="bx bx-cog me-2"></i>
                        <h6 class="mb-0 fw-semibold"><?= get_label('role_configuration', 'Role Configuration') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label"><?= get_label('name', 'Name') ?> <span class="asterisk">*</span></label>
                                <input class="form-control" type="text" placeholder="<?= get_label('please_enter_role_name', 'Please enter role name') ?>" id="name" name="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for=""><?= get_label('data_access', 'Data Access') ?> <i class='bx bx-info-circle text-primary ms-1' data-bs-toggle="tooltip" data-bs-placement="top" title="{{get_label('all_data_access_info', 'If All Data Access Is Selected, Users Under This Roles Will Have Unrestricted Access to All Data, Irrespective of Any Specific Assignments or Restrictions')}}"></i></label>
                                <div class="btn-group d-flex w-100" role="group" aria-label="Data Access Toggle">
                                    <input type="radio" class="btn-check" name="permissions[]" id="access_all_data" value="<?= Permission::where('name', 'access_all_data')->where('guard_name', 'web')->first()->id ?>">
                                    <label class="btn btn-outline-primary" for="access_all_data"><?= get_label('all_data_access', 'All Data Access') ?></label>
                                    <input type="radio" class="btn-check" name="permissions[]" id="access_allocated_data" value="0" checked>
                                    <label class="btn btn-outline-primary" for="access_allocated_data"><?= get_label('allocated_data_access', 'Allocated Data Access') ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Module Permissions Panel -->
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex align-items-center text-secondary">
                        <i class="bx bx-check-shield me-2"></i>
                        <h6 class="mb-0 fw-semibold"><?= get_label('module_permissions', 'Module Permissions') ?></h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover my-2">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" id="selectAllColumnPermissions" class="form-check-input">
                                                <label class="form-check-label fw-bold" for="selectAllColumnPermissions"><?= get_label('select_all', 'Select all') ?></label>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Loop for modules -->
                                    @foreach(config("taskhub.permissions") as $module => $permissions)
                                    <tr>
                                        <td class="bg-label-secondary" style="width: 250px;">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" id="selectRow{{$module}}" class="form-check-input row-permission-checkbox" data-module="{{$module}}">
                                                <label class="form-check-label fw-semibold" for="selectRow{{$module}}">{{ get_label(strtolower(str_replace(' ', '_', $module)), ucfirst(str_replace('_', ' ', $module))) }}</label>
                                            </div>
                                        </td>
                                        <!-- Loop for permissions under each module -->
                                        <td>
                                            <div class="d-flex flex-wrap gap-4">
                                                @foreach($permissions as $permission)
                                                <div class="form-check mb-0">
                                                    <?php $permissionId = Permission::findByName($permission)->id; ?>
                                                    <input type="checkbox" id="permission{{$permissionId}}" name="permissions[]" value="{{$permissionId}}" class="form-check-input permission-checkbox" data-module="{{$module}}">
                                                    <label class="form-check-label text-capitalize" for="permission{{$permissionId}}">{{ get_label(substr($permission, 0, strpos($permission, "_")), ucfirst(str_replace('_', ' ', substr($permission, 0, strpos($permission, "_"))))) }}</label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Save Actions -->
                <div class="card mb-3">
                    <div class="card-body py-3 d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        <button type="submit" class="btn btn-primary" id="submit_btn"><?= get_label('create', 'Create') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection