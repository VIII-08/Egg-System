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
    </style>
</head>
<body>
    <h1>{{ $monthYear ?? '' }}</h1>
    <table>
        <thead>
            <tr>
                <th colspan="{{ count($categoryNames ?? []) + 1 }}" class="header-row">EXPENSES</th>
                <th class="header-row">TOTAL</th>
            </tr>
            <tr class="header-row">
                <th>DAYS</th>
                @foreach($categoryNames ?? [] as $categoryName)
                    <th>{{ $categoryName }}</th>
                @endforeach
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="{{ $item['day'] === 'TOTAL' ? 'total-row' : '' }}">
                    <td>{{ $item['day'] }}</td>
                    @foreach($categoryNames ?? [] as $categoryName)
                        <td>{{ number_format($item[$categoryName] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="font-semibold">{{ number_format($item['total_expenses'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>