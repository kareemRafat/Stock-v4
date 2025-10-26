<div>
    <div @class([
        'text-sm font-medium',
        'text-rose-600' => $color === 'rose',
        'text-success-600' => $color === 'success',
    ])>
        {{ $sign }}{{ $content }}
    </div>
    <div class=" text-gray-500 dark:text-gray-400 mt-3 flex flex-column gap-4">
        <div> ملاحظة: الرقم الموجب يمثل رصيداً لك عند المورد.</div>

    </div>
    <div> الرقم السالب يمثل مديونية عليك للمورد.</div>
</div>
