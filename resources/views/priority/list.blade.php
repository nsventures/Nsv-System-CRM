@extends('layout')
@section('title')
<?= get_label('priorities', 'Priorities') ?>
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 mt-4 gap-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{url('home')}}"><?= get_label('home', 'Home') ?></a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?= get_label('priorities', 'Priorities') ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_priority_modal"><button type="button" class="btn btn-sm btn-primary action_create_priorities" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title=" <?= get_label('create_priority', 'Create Priority') ?>"><i class="bx bx-plus"></i></button></a>
        </div>
    </div>
    <x-priority-card />
</div>
<script src="{{asset('assets/js/pages/priority.js')}}"></script>
@endsection