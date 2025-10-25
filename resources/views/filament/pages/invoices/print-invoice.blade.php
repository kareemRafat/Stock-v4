<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{$customer->name }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #fff;
            color: #333;
            line-height: 1.6;
        }

        .print-area {
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 15px;
        }

        .company-info {
            color: #1c1b1b;
            font-size: 14px;
            /* line-height: 1.8; */
        }

        .invoice-meta {
            text-align: left;
            direction: ltr;
        }

        .meta-box {
            display: flex;
            align-items: center;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 8px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            justify-content: flex-end;
            gap: 16px;
        }

        .meta-divider {
            width: 1px;
            height: 30px;
            background-color: #2a2e35;
            margin: 0 8px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .contact-info {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
        }

        .table-section {
            color: black !important;
        }

        .divider {
            height: 1px;
            background-color: #25282d;
            margin: 16px 0;
        }

        .customer-section {
            margin-bottom: 24px;
        }

        .customer-title {
            font-size: 16px;
            font-weight: 500;
            color: #1c222b;
            margin: 16px 0;
        }

        .customer-box {
            background-color: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #434953;
            margin: 16px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .customer-name {
            font-weight: 500;
            color: #4b5563;
        }

        .customer-address {
            color: #4b5563;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        th {
            background-color: #f9fafb;
            padding: 8px 12px;
            font-weight: 500;
            color: #1f242c;
            font-size: 14px;
            border: 1px solid #636870;
            text-align: right;
        }

        th.text-center {
            text-align: center;
        }

        td {
            padding: 8px 12px;
            color: #23272e;
            font-size: 14px;
            border: 1px solid #656b75;
            text-align: right;
        }

        td.text-center {
            text-align: center;
        }

        .totals-section {
            width: 100%;
            margin-top: 16px;
            font-weight: 600;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 8px;
            direction: ltr ;
            color: black ;
        }

        .total-divider {
            height: 1px;
            background-color: #31363e;
            margin: 8px 0;
        }

        .special-discount {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 6px;
        }

        .final-total {
            padding: 12px 16px;
            border-radius: 6px;
        }

        .text-sm {
            font-size: 14px;
        }

        .text-base {
            font-size: 16px;
        }

        .font-medium {
            font-weight: 500;
        }

        .font-semibold {
            font-weight: 600;
        }

        .text-black {
            color: #000;
        }

        .text-gray-600 {
            color: #23282f;
        }

        .text-gray-500 {
            color: #6b7280;
        }

        .text-primary-600 {
            color: #4f46e5;
        }

        .text-primary-900 {
            color: #312e81;
        }

        .bg-gray-50 {
            background-color: #f9fafb;
        }
    </style>
</head>

<body>
    <div class="print-area">
        <!-- Header -->
        <div class="header">
            <div>
                <h1 class="invoice-title">فاتورة</h1>
                <div class="company-info">
                    <span class="text-gray-600">شركة أحمد حسين</span>
                </div>
            </div>

            <div class="invoice-meta">
                <div class="meta-box">
                    <div class="meta-item">
                        <span class="mb-2">التاريخ:</span>
                        <span>{{ $created_at ?? now()->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Customer -->
        <div class="customer-section">
            <h3 class="customer-title">فاتورة لأمر :</h3>
            <div class="customer-box">
                <p class="customer-name">{{ $customer->name ?? '-' }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>مسلسل</th>
                        <th>المنتج</th>
                        <th class="text-center">الكمية</th>
                        <th>الخصم</th>
                        <th>السعر للوحدة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $isWholesale = $price_type === 'wholesale';
                        $totalBeforeSale = 0;
                        $totalDiscounts = 0;
                        $subtotal = 0;
                    @endphp

                    @foreach ($items as $item)
                        @php
                            // السعر الأصلي من الداتابيز (قبل الخصم)
                            $originalPrice = $isWholesale ? $item['wholesale_price'] : $item['retail_price'];

                            if ($isWholesale && $item['discount'] > 0) {
                                // السعر بعد الخصم
                                $priceAfterDiscount = $originalPrice * (1 - $item['discount'] / 100);
                                // الإجمالي بعد الخصم
                                $lineTotalAfterDiscount = $priceAfterDiscount * $item['quantity'];
                                // قيمة الخصم الفعلية
                                $lineDiscount = $originalPrice * $item['quantity'] - $lineTotalAfterDiscount;
                            } else {
                                $priceAfterDiscount = $originalPrice;
                                $lineTotalAfterDiscount = $originalPrice * $item['quantity'];
                                $lineDiscount = 0;
                            }

                            $totalBeforeSale += $originalPrice * $item['quantity'];
                            $totalDiscounts += $lineDiscount;
                            $subtotal += $lineTotalAfterDiscount;
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item['product_name'] }}</td>
                            <td class="text-center">{{ $item['quantity'] }} {{ $item['product_unit'] }}</td>
                            <td>{{ $item['discount'] > 0 ? number_format($item['discount'], 2) . '%' : '-' }}</td>
                            <td>{{ number_format($originalPrice, 2) }}</td> <!-- سعر الوحدة قبل الخصم -->
                            <td>{{ number_format($lineTotalAfterDiscount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span> الإجمالي قبل الخصم  : </span>
                <span>{{ number_format($totalBeforeSale, 2) }} ج.م</span>
            </div>

            @if ($isWholesale && $totalDiscounts > 0)
                <div class="total-row">
                    <span>إجمالي الخصومات  : </span>
                    <span>{{ number_format($totalDiscounts, 2) }} ج.م</span>
                </div>
            @endif

            <div class="total-row">
                <span class="text-sm text-black">المجموع : </span>
                <span class="font-medium text-sm text-black">{{ number_format($subtotal, 2) }} ج.م</span>
            </div>

            <div class="total-divider"></div>

            @if (($special_discount ?? 0) > 0)
                <div class="special-discount">
                    <div class="total-row">
                        <span class="text-sm font-medium text-black">خصم خاص:</span>
                        <span class="text-sm font-medium text-black">
                            - {{ number_format($special_discount, 2) }} ج.م
                        </span>
                    </div>

                    <div class="total-row">
                        <span class="text-base font-medium text-black"> الإجمالي النهائي : </span>
                        <span class="text-base font-medium text-black">
                            {{ number_format($subtotal - $special_discount, 2) }} ج.م
                        </span>
                    </div>
                </div>
            @else
                <div class="final-total">
                    <div class="total-row">
                        <span class="text-base font-medium text-black">الإجمالي النهائي : </span>
                        <span class="text-base font-medium text-black">
                            {{ number_format($subtotal, 2) }} ج.م
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>

</html>
