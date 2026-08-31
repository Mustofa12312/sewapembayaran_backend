<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-size: 28px; font-weight: bold; }
        .details { margin-bottom: 30px; }
        .details th, .details td { padding: 5px; text-align: left; }
        .items { width: 100%; border-collapse: collapse; }
        .items th, .items td { border: 1px solid #ddd; padding: 10px; }
        .items th { background-color: #f4f4f4; }
        .total { font-weight: bold; font-size: 18px; text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">INVOICE</div>
        <div>
            <strong>Order No:</strong> {{ $order->order_number }}<br>
            <strong>Date:</strong> {{ $order->created_at->format('d M Y') }}
        </div>
    </div>

    <table class="details">
        <tr>
            <th>Billed To:</th>
            <td>{{ $order->customer_name }} ({{ $order->customer_email }})</td>
        </tr>
        <tr>
            <th>Status:</th>
            <td>{{ $order->status }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>Package</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $order->product->name ?? 'N/A' }}</td>
                <td>{{ $order->package->name ?? 'N/A' }}</td>
                <td>Rp{{ number_format($order->snapshot_price, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($order->snapshot_price - $order->discount_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Grand Total: Rp{{ number_format($order->snapshot_price - $order->discount_amount, 0, ',', '.') }}
    </div>
</body>
</html>
