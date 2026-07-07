<!DOCTYPE html>
<html>
<head>
<style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        thead { background-color: #f2f2f2; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 18px; font-weight: bold; }
        h2 { text-align: center; margin-top: 5px; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
        .header-row { background-color: #e0e0e0; font-weight: bold; }
        .total-row { background-color: #d3d3d3; font-weight: bold; }
        .sub-header-row { background-color: #e0e0e0; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ strtoupper($monthYear ?? $startDate) }}</h1>
    <h2>EGG PRODUCTION (Grams)</h2>
    <table>
        <thead>
            <tr class="header-row">
                <th>DAYS</th>
                <th>HENS</th>
                @foreach ($columnNames ?? [] as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="{{ ($item['day'] ?? '') === 'TOTAL' ? 'total-row' : '' }}">
                    <td>{{ $item['day'] ?? '' }}</td>
                    <td>{{ $item['hens'] ?? '' }}</td>
                    @foreach ($columnNames ?? [] as $col)
                        <td>{{ data_get($item, $col, '') }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>