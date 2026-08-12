@if($data)
    @php
        $relation = \Str::beforeLast($column['field'], '.');
    @endphp
    <flux:badge size="sm" class="cursor-pointer" as="button" rounded color="lime" icon:trailing="arrow-top-right-on-square">
        <!--{{ $item->$relation->getKey() }}-->
        {{ data_get($item, $column['field']) }}
    </flux:badge>
@endif