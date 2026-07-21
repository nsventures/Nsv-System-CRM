<!-- Navbar (Taskify v2 command bar) -->
<?php

use App\Models\Language;
use App\Models\Notification;

$authenticatedUser = getAuthenticatedUser();
$current_language = Language::where('code', app()->getLocale())->get(['name', 'code']);
$default_language = $authenticatedUser->lang;
$unreadNotificationsCount = $authenticatedUser->notifications()
    ->wherePivot('read_at', null)
    ->wherePivot('is_system', 1)
    ->count();
$unreadNotifications = $authenticatedUser->notifications()
    ->wherePivot('read_at', null)
    ->wherePivot('is_system', 1)
    ->getQuery()
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();


// Calculate the remaining unread notifications count
$remainingUnreadNotificationsCount = $unreadNotificationsCount - 10;

// Ensure the remaining count is not negative
if ($remainingUnreadNotificationsCount < 0) {
    $remainingUnreadNotificationsCount = 0;
}
?>
@authBoth
<div id="section-not-to-print">
    <header class="tk-cbar" id="layout-navbar">
        {{-- Mobile: toggle the context panel --}}
        <button type="button" class="tk-cbar-burger d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="{{ get_label('menu', 'Menu') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        {{-- Breadcrumb: workspace monogram / current page --}}
        <div class="tk-cbar-crumb d-none d-md-block">
            <span class="tk-cbar-ws">{{ strtoupper(substr($general_settings['company_title'] ?? 'TK', 0, 2)) }}</span>
            <span class="tk-cbar-sep">/</span>
            <span class="tk-cbar-crumb-title">@yield('title')</span>
        </div>

        {{-- Global search (CTRL+K → #globalSearchModal). Keep #global-search for the existing handler --}}
        <button type="button" class="tk-cbar-search d-none d-md-flex" id="global-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <span class="tk-cbar-search-text">{{ get_label('search', 'Search') }}</span>
            <span class="tk-kbd">CTRL K</span>
        </button>

        <div class="tk-cbar-actions">
            {{-- Theme toggle (light/dark via the design system) --}}
            <button type="button" class="tk-icon-btn" id="tk-theme-toggle"
                title="{{ get_label('toggle_theme', 'Toggle theme') }}" aria-label="{{ get_label('toggle_theme', 'Toggle theme') }}">
                <span class="tk-ico-moon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                </span>
                <span class="tk-ico-sun">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </span>
            </button>

            {{-- Notifications (unchanged logic + IDs/classes) --}}
            @if (getAuthenticatedUser()->can('manage_system_notifications'))
                <div class="dropdown">
                    <a class="tk-icon-btn" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                        title="{{ get_label('notifications', 'Notifications') }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9ZM10 21a2 2 0 0 0 4 0"/></svg>
                        <span id="unreadNotificationsCount"
      class="tk-badge-counter tk-badge-counter-danger tk-badge-counter-nav
      {{ $unreadNotificationsCount > 0 ? '' : 'd-none' }}">
    {{ $unreadNotificationsCount }}
</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 350px;">
                        <li class="fixed-header border-bottom px-3 py-2">
                            <div class="d-flex align-items-center text-muted fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                <i class="bx bx-bell me-2"></i>
                                <span class="text-uppercase">{{ get_label('notifications', 'Notifications') }}</span>
                            </div>
                        </li>
                        <div id="unreadNotificationsContainer" class="scrollable-dropdown">
                            @if ($unreadNotificationsCount > 0)
                                @foreach ($unreadNotifications as $notification)
                                    <li>
                                        <a class="dropdown-item update-notification-status py-3 px-3" data-id="{{ $notification->id }}" href="javascript:void(0);">
                                            <div class="d-flex align-items-start">
                                                <i class="bx bx-bell me-3 mt-1"></i>
                                                <div class="d-flex flex-column w-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-semibold text-wrap">{{ $notification->title }}</span>
                                                        <small class="text-muted text-nowrap ms-2">{{ $notification->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    <div class="small text-muted text-wrap">
                                                        {{ strlen(strip_tags($notification->message)) > 50 ? substr(strip_tags($notification->message), 0, 50) . '...' : strip_tags($notification->message) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider m-0">
                                    </li>
                                @endforeach
                            @else
                                <li class="p-5 d-flex align-items-center justify-content-center text-muted">
                                    <span>{{ get_label('no_unread_notifications', 'No unread notifications') }}</span>
                                </li>
                            @endif
                        </div>
                        <li class="d-flex justify-content-between align-items-center fixed-footer border-top px-3 py-2" style="background: var(--bs-dropdown-bg);">
                            <a href="{{ url('notifications') }}" class="text-decoration-none">
                                <span class="fw-bold">{{ get_label('view_all', 'View all') }}</span>
                                @if ($remainingUnreadNotificationsCount > 0)
                                    <span class="badge bg-primary ms-1">+{{ $remainingUnreadNotificationsCount }}</span>
                                @endif
                            </a>
                            <a href="#" class="text-decoration-none text-end" id="mark-all-notifications-as-read">
                                <span class="fw-bold">{{ get_label('mark_all_as_read', 'Mark all as read') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif

            {{-- Language switcher --}}
            <div class="dropdown">
                <button type="button" class="tk-icon-btn" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    title="{{ get_label('language', 'Language') }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.9 2.6 15.1 0 18M12 3c-2.6 2.9-2.6 15.1 0 18"/></svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-2 language-dropdown" id="languageDropdown" style="min-width: 220px; max-height: 80vh; overflow-y: auto;">
                    @foreach ($languages as $language)
                        <?php $is_active = $language->code == app()->getLocale(); ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 rounded" href="{{ url('/settings/languages/switch/' . $language->code) }}">
                                <span>{{ $language->name }}</span>
                                @if ($is_active)
                                    <i class="bx bx-check" style="color: var(--signal) !important; font-size: 1.25rem;"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                    <li><hr class="dropdown-divider my-1"></li>
                    @if (!$current_language->isEmpty() && $current_language[0]['code'] == $default_language)
                        <li class="px-2 pt-1">
                            <span class="tk-badge tk-badge-primary justify-content-center d-flex w-100 py-1" style="font-size: 11px; font-weight: 600;" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('current_language_is_your_primary_language', 'Current language is your primary language') ?>">
                                <?= get_label('primary', 'Primary') ?>
                            </span>
                        </li>
                    @else
                        <li class="px-2 pt-1">
                            <a href="javascript:void(0);" class="text-decoration-none d-block" id="set-as-default" data-lang="{{ app()->getLocale() }}" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= get_label('set_current_language_as_your_primary_language', 'Set current language as your primary language') ?>">
                                <span class="tk-badge justify-content-center d-flex w-100 py-1" style="font-size: 11px; font-weight: 600; background: var(--bg-3); color: var(--fg-1);">
                                    <?= get_label('set_as_primary', 'Set as primary') ?>
                                </span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <span class="tk-cbar-divider"></span>

            {{-- User menu (unchanged logic) --}}
       <div class="dropdown">
    <a class="tk-cbar-user" href="javascript:void(0);" 
       data-bs-toggle="dropdown" aria-expanded="false"
       title="<?= get_label('hi', 'Hi') ?> {{ $authenticatedUser->first_name }}">

        <img src="{{ $authenticatedUser->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($authenticatedUser->photo) ? asset('storage/' . $authenticatedUser->photo) : asset('storage/photos/no-image.jpg') }}"
             alt="" />

        <span class="tk-cbar-username nav-mobile-hidden">
            {{ Str::limit($authenticatedUser->first_name, 10) }}
        </span>
    </a>

    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 260px;">

        <!-- Profile header -->
        <li>
            <div class="dropdown-item d-block py-2">
                <div class="d-flex align-items-center gap-3">

                    <div class="flex-shrink-0">
                        <div class="avatar avatar-online avatar-nav-dropdown">
                            <img src="{{ $authenticatedUser->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($authenticatedUser->photo) ? asset('storage/' . $authenticatedUser->photo) : asset('storage/photos/no-image.jpg') }}"
                                 class="rounded-circle"
                                 alt="">
                        </div>
                    </div>

                    <div class="flex-grow-1">
                        <span class="fw-semibold d-block">
                            {{ Str::limit($authenticatedUser->first_name . ' ' . $authenticatedUser->last_name, 18) }}
                        </span>

                        <small class="text-muted text-capitalize">
                            {{ ucfirst($authenticatedUser->getRoleNames()->first()) }}
                        </small>
                    </div>

                </div>
            </div>
        </li>

        <li><hr class="dropdown-divider my-1"></li>

        <!-- Menu items -->
        <li>
            <a class="dropdown-item d-block py-2" href="{{ url('/account/' . $authenticatedUser->id) }}">
                <i class="bx bx-user me-2"></i>
                <span> <?= get_label('my_profile', 'My Profile') ?> </span>
            </a>
        </li>

        <li>
            <a class="dropdown-item d-block py-2" href="{{ url('preferences') }}">
                <i class="bx bx-cog me-2"></i>
                <span> <?= get_label('preferences', 'Preferences') ?> </span>
            </a>
        </li>

        <li>
            <a class="dropdown-item d-block py-2" href="{{ url('clear-cache') }}">
                <i class="bx bx-refresh me-2"></i>
                <span>{{ get_label('clear_system_cache', 'Clear System Cache') }}</span>
            </a>
        </li>

        <li><hr class="dropdown-divider my-1"></li>

        <!-- Logout -->
        <li>
            <form action="{{ url('logout') }}" method="POST">
                @csrf
                <button type="submit" 
                        class="dropdown-item d-block py-2 text-danger border-0 bg-transparent w-100 text-start">
                    <i class="bx bx-log-out-circle me-2"></i>
                    <span><?= get_label('logout', 'Logout') ?></span>
                </button>
            </form>
        </li>

    </ul>
</div>
        </div>
    </header>
</div>

@else
@endauth
<script>
    var label_search = '<?= get_label('search', 'Search') ?>';
</script>
<script src="{{ asset('assets/js/pages/navbar.js') }}"></script>
<!-- / Navbar -->
