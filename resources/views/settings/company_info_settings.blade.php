@extends('layout')
@section('title')
<?= get_label('company_info', 'Company Information') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h4 class="fw-bold mb-0"><?= get_label('company_info', 'Company Information') ?></h4>
        <div class="d-flex align-items-center gap-3">
            <nav class="breadcrumb mb-0" aria-label="breadcrumb">
                <a class="breadcrumb-item" href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-item"><?= get_label('settings', 'Settings') ?></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= get_label('company_info', 'Company Information') ?></span>
            </nav>
        </div>
    </div>
    
    <div class="alert alert-primary" role="alert"><i class="bx bx-info-circle me-1"></i><?= get_label('company_info_info', 'This information will be displayed on estimates and invoices.')?></div>
    
    <form action="{{url('settings/store_company_info')}}" class="form-submit-event" method="POST">
        <input type="hidden" name="dnr">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header border-bottom d-flex align-items-center text-secondary">
                        <i class="bx bx-building-house me-2"></i>
                        <h6 class="mb-0 fw-semibold"><?= get_label('company_details', 'Company Details') ?></h6>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyEmail"><?= get_label('email', 'Email') ?></label>
                                <input class="form-control" type="text" name="companyEmail" value="{{ old('companyEmail', $company_info['companyEmail']) }}" placeholder="<?= get_label('please_enter_company_email', 'Please Enter Company Email') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyPhone"><?= get_label('phone_number', 'Phone Number') ?></label>
                                <input class="form-control" type="text" name="companyPhone" value="{{ old('companyPhone', $company_info['companyPhone']) }}" placeholder="<?= get_label('please_enter_company_phone_number', 'Please Enter Company Phone Number') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyAddress"><?= get_label('address', 'Address') ?></label>
                                <input class="form-control" type="text" name="companyAddress" value="{{ old('companyAddress', $company_info['companyAddress']) }}" placeholder="<?= get_label('please_enter_company_address', 'Please Enter Company Address') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyCity"><?= get_label('city', 'City') ?></label>
                                <input class="form-control" type="text" name="companyCity" value="{{ old('companyCity', $company_info['companyCity']) }}" placeholder="<?= get_label('please_enter_company_city', 'Please Enter Company City') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyState"><?= get_label('state', 'State') ?></label>
                                <input class="form-control" type="text" name="companyState" value="{{ old('companyState', $company_info['companyState']) }}" placeholder="<?= get_label('please_enter_company_state', 'Please Enter Company State') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyCountry"><?= get_label('country', 'Country') ?></label>
                                <input class="form-control" type="text" name="companyCountry" value="{{ old('companyCountry', $company_info['companyCountry']) }}" placeholder="<?= get_label('please_enter_company_country', 'Please Enter Company Country') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyZip"><?= get_label('zip_code', 'Zip Code') ?></label>
                                <input class="form-control" type="text" name="companyZip" value="{{ old('companyZip', $company_info['companyZip']) }}" placeholder="<?= get_label('please_enter_company_zip_code', 'Please Enter Company Zip Code') ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="companyWebsite"><?= get_label('website', 'Website') ?></label>
                                <input class="form-control" type="text" name="companyWebsite" value="{{ old('companyWebsite', $company_info['companyWebsite']) }}" placeholder="<?= get_label('please_enter_company_website', 'Please Enter Company Website') ?>">
                            </div>
                            <div class="mb-3 col-md-12">
                                <label class="form-label" for="companyVatNumber"><?= get_label('vat_number', 'VAT Number') ?></label>
                                <input class="form-control" type="text" name="companyVatNumber" value="{{ old('companyVatNumber', $company_info['companyVatNumber']) }}" placeholder="<?= get_label('please_enter_company_vat_number', 'Please Enter Company VAT Number') ?>">
                            </div>
                            
                            <div class="col-md-12 text-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-2"><?= get_label('cancel', 'Cancel') ?></button>
                                <button type="submit" class="btn btn-primary" id="submit_btn"><?= get_label('update', 'Update') ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection