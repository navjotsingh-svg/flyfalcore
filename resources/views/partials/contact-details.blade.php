@php
    $usa = $falcore['phones']['usa'];
    $india = $falcore['phones']['india'];
@endphp
<div class="{{ $class ?? 'space-y-4 text-sm' }}">
    <p>
        <span class="font-semibold">{{ $usa['label'] }}</span><br>
        <a href="tel:{{ $usa['href'] }}" class="text-brand-600">{{ $usa['display'] }}</a>
    </p>
    <p>
        <span class="font-semibold">{{ $india['label'] }}</span><br>
        <a href="tel:{{ $india['href'] }}" class="text-brand-600">{{ $india['display'] }}</a>
    </p>
    <p>
        <span class="font-semibold">Email</span><br>
        <a href="mailto:{{ $falcore['email'] }}" class="text-brand-600">{{ $falcore['email'] }}</a>
    </p>
</div>
