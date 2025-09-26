@php
    $id = $getId();
    $name = $getName();
    $statePath = $getStatePath();
@endphp

<div x-data="{ datetime: '' }" x-init="if (!datetime) {
    $nextTick(() => {
        const now = new Date();
        datetime = now.getFullYear() + '-' +
            (now.getMonth() + 1).toString().padStart(2, '0') + '-' +
            now.getDate().toString().padStart(2, '0') + ' ' +
            now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0') + ':' +
            now.getSeconds().toString().padStart(2, '0');
        $refs.input.value = datetime;
        $refs.input.dispatchEvent(new Event('input'));
    });
}">
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" x-ref="input"
        wire:model.live="{{ $statePath }}" x-bind:value="datetime" />
</div>
