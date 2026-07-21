@props(['active' => false, 'icon' => '', 'label' => '', 'target' => ''])

<button
    type="button"
    class="tab nav-link {{ $active ? 'active' : '' }}"
    role="tab"
    data-bs-toggle="tab"
    data-bs-target="#{{ $target }}"
    aria-controls="{{ $target }}"
    aria-selected="{{ $active ? 'true' : 'false' }}">
    @if ($icon)
        <i class="menu-icon tf-icons {{ $icon }}" style="margin-right: 0;"></i>
    @endif
    {{ $label }}
</button>
