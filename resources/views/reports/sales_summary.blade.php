<!DOCTYPE html>
<html>
<head>
<style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        thead { background-color: #f2f2f2; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 18px; font-weight: bold; }
        .header-row { background-color: #e0e0e0; font-weight: bold; }
        .total-row { background-color: #d3d3d3; font-weight: bold; }
        .price-row { font-weight: bold; }
        .category-row { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="{{ (count($productNames ?? []) + 1) }}" class="header-row">EGG SALES</th>
                <th colspan="2" class="header-row">TOTAL</th>
            </tr>
            <tr class="price-row">
                <td></td>
                @foreach($productNames ?? [] as $productName)
                    <td>{{ number_format($prices[$productName] ?? 0, 2) }}</td>
                @endforeach
                <td colspan="2"></td>
            </tr>
            <tr class="header-row">
                <th>DAYS</th>
                @foreach($productNames ?? [] as $productName)
                    <th>{{ $productName }}</th>
                @endforeach
                <th>EGGS</th>
                <th>SALES</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="{{ $item['day'] === 'TOTAL' ? 'total-row' : '' }}">
                    <td>{{ $item['day'] }}</td>
                    @if($item['day'] === 'TOTAL')
                        @foreach($productNames ?? [] as $productName)
                            <td>{{ number_format($item[$productName]['quantity'] ?? 0, 0) }}</td>
                        @endforeach
                    @else
                        @foreach($productNames ?? [] as $productName)
                            <td>{{ number_format($item[$productName]['revenue'] ?? 0, 2) }}</td>
                        @endforeach
                    @endif
                    <td>{{ number_format($item['total_eggs'] ?? 0, 0) }}</td>
                    <td>{{ number_format($item['total_sales'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>