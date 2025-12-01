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
            <tr class="sub-header-row">
                <th rowspan="2">DAYS</th>
                <th rowspan="2">HENS</th>
                <th colspan="7">EGG SIZES</th>
            </tr>
            <tr class="sub-header-row">
                <th>40-49</th>
                <th>50-54</th>
                <th>55-59</th>
                <th>60-64</th>
                <th>65-69</th>
                <th>70 up</th>
                <th>DAMAGED</th>
            </tr>
            <tr class="header-row">
                <th>DAYS</th>
                <th>HENS</th>
                <th>PULLETS</th>
                <th>SMALL</th>
                <th>MEDIUM</th>
                <th>LARGE</th>
                <th>X-LARGE</th>
                <th>JUMBO</th>
                <th>DAMAGED</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="{{ $item['day'] === 'TOTAL' ? 'total-row' : '' }}">
                    <td>{{ $item['day'] }}</td>
                    <td>{{ $item['hens'] }}</td>
                    <td>{{ $item['PULLETS'] }}</td>
                    <td>{{ $item['SMALL'] }}</td>
                    <td>{{ $item['MEDIUM'] }}</td>
                    <td>{{ $item['LARGE'] }}</td>
                    <td>{{ $item['X-LARGE'] }}</td>
                    <td>{{ $item['JUMBO'] }}</td>
                    <td>{{ $item['DAMAGED'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>