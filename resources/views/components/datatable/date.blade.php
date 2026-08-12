<div>
    {{ $data instanceof \Carbon\CarbonImmutable ? $data->format('d/m/Y') : $data }}
</div>