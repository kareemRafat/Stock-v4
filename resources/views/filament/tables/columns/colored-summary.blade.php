<div>
    <div @class([
        'text-sm font-medium',
        'text-rose-600' => $color === 'rose',
        'text-success-600' => $color === 'success',
    ])>
        {{ $sign }}{{ $content }}
    </div>

    {{-- عرض الملاحظة بناءً على نوع المحفظة --}}
    <div class=" text-gray-500 dark:text-gray-400 mt-3">
        @if ($wallet_type === 'customer')
            <div class=" text-gray-500 dark:text-gray-400 mt-3 flex flex-column gap-4">
                <div> ملاحظة: الرقم الموجب يمثل مديونية على العميل مستحقة لك.</div>
            </div>
            <div> الرقم السالب يمثل رصيداً دائنًا مستحقاً للعميل.</div>
        @elseif ($wallet_type === 'supplier')
            <div class=" text-gray-500 dark:text-gray-400 mt-3 flex flex-column gap-4">
                <div> ملاحظة: الرقم الموجب يمثل رصيداً لك عند المورد.</div>
            </div>
            <div> الرقم السالب يمثل مديونية عليك للمورد.</div>
        @else
            ملاحظة: قواعد الإشارة غير محددة لهذا الجدول.
        @endif
    </div>
</div>
