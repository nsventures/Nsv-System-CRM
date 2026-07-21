<!-- Footer -->
<div id="section-not-to-print">
    <footer class="content-footer footer bg-footer-theme mt-4">
        <div class="container-fluid d-flex flex-wrap  flex-md-row flex-column">
            <div class="mb-md-0 d-flex align-items-start justify-content-between">
                ©
                <script>
                    document.write(new Date().getFullYear());
                </script>
                , <?= $general_settings['footer_text'] ?>
                <p class="mx-4 fw-bolder">v{{get_current_version()}}</p>

                @if (config('constants.ALLOW_MODIFICATION') === 0)
                 <span class="badge bg-danger demo-mode">Demo mode</span>
                @endif
            </div>
            <div class="mb-md-0 d-flex align-items-start justify-content-between px-1_5">
                <a href="https://help-taskify.taskhub.company/" target="_blank" rel="noopener noreferrer" class="text-muted text-decoration-none">
                    <i class="bx bx-support fs-5 mb-1 px-1"></i>
                    {{ get_label('help_center', 'Help Center') }}
                </a>
            </div>
        </div>
    </footer>
</div>
<!-- / Footer -->
