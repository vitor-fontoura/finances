<flux:badge size="sm" color="{{ $data instanceof \App\Types\Contracts\ColoredContract ? $data->getColor() : 'mist'}}">
    {{ $data instanceof \App\Types\Contracts\LabeledContract ? $data->getLabel() : $data }}
</flux:badge>