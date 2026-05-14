@props([
    'name' => 'circle',
    'class' => 'w-5 h-5',
    'strokeWidth' => 2,
])

{{-- Lucide icon. The lucide JS library (loaded in app.blade.php) replaces this
     element with an inline SVG after DOM ready. Use stroke-current for color. --}}
<i
    data-lucide="{{ $name }}"
    data-stroke-width="{{ $strokeWidth }}"
    {{ $attributes->merge(['class' => $class]) }}
></i>
