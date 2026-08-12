<flux:text class="text-right {{ $data < 0 ? 'text-rose-500' : 'text-lime-500' }}">
    {{ $data < 0 ? '-' : '' }} R$ {{ number_format(abs($data/100), 2, '.', ',') }}
</flux:text>