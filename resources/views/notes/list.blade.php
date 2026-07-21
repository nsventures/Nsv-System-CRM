@extends('layout')
@section('title')
    <?= get_label('notes', 'Notes') ?>
@endsection
@section('content')
    <!-- Add this in your head section -->
    <!-- js-draw Styles -->


    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
            <!-- Left Side: Breadcrumbs -->
            <div class="d-flex align-items-center gap-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('notes', 'Notes') ?>
                        </li>
                    </ol>
                </nav>
            </div>
            <!-- Right Side: Create Action -->
            <div>
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_note_modal" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left" title="<?= get_label('create_note', 'Create note') ?>">
                    <i class='bx bx-plus'></i>
                </a>
            </div>
        </div>

        @if ($notes->count() > 0)
            <div class="card mb-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check m-0">
                                <input type="checkbox" id="select-all" class="form-check-input">
                                <label for="select-all" class="form-check-label">{{ get_label('select_all', 'Select All') }}</label>
                            </div>
                            <button type="button" id="delete-selected" class="btn btn-outline-danger btn-sm" data-type="notes">
                                <i class="bx bx-trash me-1"></i> {{ get_label('delete_selected', 'Delete Selected') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                @foreach ($notes as $note)
                    <div class="col-md-6 col-xl-4">
                        <div class="tcard h-100 tk-note-card" data-card-id="{{ $note->id }}">
                            <div class="tcard-meta">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="selected-items form-check-input" value="{{ $note->id }}">
                                    <span class="tcard-code mono">#{{ $note->id }}</span>
                                    @php
                                        $colorMap = [
                                            'info' => 'success',
                                            'warning' => 'warning',
                                            'danger' => 'danger'
                                        ];
                                        $displayColor = $colorMap[$note->color] ?? 'primary';
                                        $labelText = [
                                            'info' => get_label('green', 'Green'),
                                            'warning' => get_label('yellow', 'Yellow'),
                                            'danger' => get_label('red', 'Red')
                                        ][$note->color] ?? ucfirst($note->color);
                                    @endphp
                                    <span class="badge bg-{{ $displayColor }} badge-xs" style="font-size: 10px; padding: 2px 6px;">
                                        {{ $labelText }}
                                    </span>
                                    <span class="badge bg-secondary badge-xs" style="font-size: 10px; padding: 2px 6px;">
                                        {{ ucfirst($note->note_type) }}
                                    </span>
                                </div>
                                <div class="tcard-actions">
                                    <a href="javascript:void(0);" class="tcard-ic edit-note" data-id="{{ $note->id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('update', 'Update') }}">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="tcard-ic delete" data-id="{{ $note->id }}" data-type="notes" data-reload="true" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_label('delete', 'Delete') }}">
                                        <i class="bx bx-trash text-danger"></i>
                                    </a>
                                </div>
                            </div>

                            <h4 class="tcard-title mt-2 mb-1"><?= $note->title ?></h4>
                            <div class="tcard-description flex-grow-1" style="font-size: 13px; color: var(--fg-2);">
                                @if ($note->note_type == 'text')
                                    <p class="mb-0" style="white-space: pre-wrap;"><?= $note->description ?></p>
                                @else
                                    <div class="drawing-content">
                                        {!! $note->drawing_data !!}
                                    </div>
                                @endif
                            </div>
                            <div class="tcard-foot border-top pt-2 mt-auto" style="font-size: 11px; color: var(--fg-3); border-top: 1px solid var(--line) !important;">
                                <span><?= get_label('created_at', 'Created at') ?>: {{ format_date($note->created_at, true) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <?php
            $type = 'Notes';
            ?>
            <x-empty-state-card :type="$type" />
        @endif
    </div>
    <script src="{{ asset('assets/js/pages/notes.js') }}"></script>
    <style>
        .imageEditorContainer {
            /* Deafult colors for the editor -- light mode */

            /* Used for unselected buttons and dialog text. */
            --background-color-1: white;
            --foreground-color-1: black;

            /* Used for some menu/toolbar backgrounds. */
            --background-color-2: #f5f5f5;
            --foreground-color-2: #2c303a;

            /* Used for other menu/toolbar backgrounds. */
            --background-color-3: #e5e5e5;
            --foreground-color-3: #1c202a;

            /* Used for selected buttons. */
            --selection-background-color: #cbdaf1;
            --selection-foreground-color: #2c303a;

            /* Used for dialog backgrounds */
            --background-color-transparent: rgba(105, 100, 100, 0.5);

            /* Used for shadows */
            --shadow-color: rgba(0, 0, 0, 0.5);

            /* Color used for some button/input foregrounds */
            --primary-action-foreground-color: #15b;
        }

        @media (prefers-color-scheme: dark) {
            .imageEditorContainer {
                /* Default colors for the editor -- dark mode */
                --background-color-1: #ffffff;
                --foreground-color-1: rgb(0, 0, 0);

                --background-color-2: #222;
                --foreground-color-2: #efefef;

                --background-color-3: #272627;
                --foreground-color-3: #eee;

                --selection-background-color: #607;
                --selection-foreground-color: white;
                --shadow-color: rgba(250, 250, 250, 0.5);
                --background-color-transparent: rgba(50, 50, 50, 0.5);

                --primary-action-foreground-color: #7ae;
            }
        }

        #clr-picker {
            z-index: 1092 !important;
        }

        .toolbar--pen-tool-toggle-buttons {
            display: none !important;
        }

        .toolbar-help-overlay-button {
            display: none !important;
        }

        .pipetteButton {
            display: none !important;
        }

        .drawing-content svg {
            max-width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .drawing-content {
            overflow: hidden;
            /* Prevents drawing overflow */
            padding: 5px 0;
            max-height: 200px;
        }
    </style>
@endsection
