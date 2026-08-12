<span title="{{ $data instanceof \Carbon\CarbonImmutable ? $data->toIso8601String() : $data }}">
    {{ $data instanceof \Carbon\CarbonImmutable ? $data->diffForHumans() : $data }}
</span>