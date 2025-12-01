<!DOCTYPE html>
<html>
<head>
<style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        thead { background-color: #f2f2f2; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 18px; font-weight: bold; }
        h2 { text-align: center; margin-top: 5px; margin-bottom: 20px; font-size: 14px; font-weight: bold; }
        h3 { font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; color: #333; }
        .header-row { background-color: #e0e0e0; font-weight: bold; }
        .total-row { background-color: #d3d3d3; font-weight: bold; }
        .summary-table { margin-bottom: 30px; }
        .summary-table td { padding: 12px; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ strtoupper($monthYear ?? '') }}</h1>
    <h2>INCOME STATEMENT (P&L)</h2>
    
    <!-- Financial Summary -->
    <div class="summary-section">
        <h3>FINANCIAL SUMMARY</h3>
        <table class="summary-table">
            <tbody>
                <tr>
                    <td class="font-semibold">Total Revenue</td>
                    <td class="text-right font-semibold positive">₱{{ number_format($total_revenue ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="font-semibold">Total Expenses</td>
                    <td class="text-right font-semibold negative">(₱{{ number_format($total_expenses ?? 0, 2) }})</td>
                </tr>
                <tr class="total-row">
                    <td class="font-bold" style="font-size: 16px;">Net Income</td>
                    <td class="text-right font-bold" style="font-size: 16px;" class="{{ ($net_income ?? 0) >= 0 ? 'positive' : 'negative' }}">
                        ₱{{ number_format($net_income ?? 0, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Revenue Breakdown -->
    @if(isset($revenue_breakdown) && count($revenue_breakdown) > 0)
    <div class="summary-section">
        <h3>REVENUE BREAKDOWN</h3>
        <table>
            <thead>
                <tr class="header-row">
                    <th>Product</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenue_breakdown as $product => $amount)
                <tr>
                    <td>{{ $product }}</td>
                    <td class="text-right">₱{{ number_format($amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="font-bold">Total Revenue</td>
                    <td class="text-right font-bold positive">₱{{ number_format($total_revenue ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
    
    <!-- Expense Breakdown -->
    @if(isset($expense_breakdown) && count($expense_breakdown) > 0)
    <div class="summary-section">
        <h3>EXPENSE BREAKDOWN</h3>
        <table>
            <thead>
                <tr class="header-row">
                    <th>Category</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expense_breakdown as $category => $amount)
                <tr>
                    <td>{{ $category ?: 'Uncategorized' }}</td>
                    <td class="text-right">₱{{ number_format($amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="font-bold">Total Expenses</td>
                    <td class="text-right font-bold negative">₱{{ number_format($total_expenses ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</body>
</html>
