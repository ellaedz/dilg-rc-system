@props([
    'status',
    'size' => 'md',
])

@php
    $label = trim((string) $status);
    $key = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label));
    $key = trim($key, '-');
    $knownStatuses = [
        'submitted', 'for-verification', 'verified', 'assigned', 'in-progress',
        'action-taken', 'resolved', 'rejected', 'closed', 'pending', 'unassigned',
        'needs-barangay-review',
    ];
    if (! in_array($key, $knownStatuses, true)) $key = 'default';
@endphp

<span {{ $attributes->class(['gov-status', 'gov-status--'.$key, 'gov-status--'.$size]) }}>
    <span class="gov-status-dot" aria-hidden="true"></span>
    {{ $label !== '' ? $label : 'Unknown' }}
</span>
