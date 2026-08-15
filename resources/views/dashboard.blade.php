<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Vendor Performance — {{ $shop }}</title>
  <style>
    body { font-family: -apple-system, Arial, sans-serif; margin: 40px; color: #1f1b1a; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 10px 14px; border-bottom: 1px solid #e5e5e5; text-align: left; }
    th { text-transform: uppercase; font-size: 11px; letter-spacing: 0.04em; color: #6b6b6b; }
    .badge { padding: 2px 8px; border-radius: 999px; font-size: 12px; }
    .badge--low { background: #fdf0e0; color: #9a5b12; }
    .badge--out { background: #fbe9e9; color: #9c2c2c; }
  </style>
</head>
<body>
  <h1>Vendor performance — {{ $shop }}</h1>
  <table>
    <thead>
      <tr>
        <th>Vendor</th>
        <th>SKUs</th>
        <th>Low stock</th>
        <th>Out of stock</th>
        <th>Inventory value</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($vendors as $vendor)
        <tr>
          <td>{{ $vendor['vendor'] }}</td>
          <td>{{ $vendor['sku_count'] }}</td>
          <td>
            @if ($vendor['low_stock_count'] > 0)
              <span class="badge badge--low">{{ $vendor['low_stock_count'] }}</span>
            @else
              0
            @endif
          </td>
          <td>
            @if ($vendor['out_of_stock_count'] > 0)
              <span class="badge badge--out">{{ $vendor['out_of_stock_count'] }}</span>
            @else
              0
            @endif
          </td>
          <td>${{ number_format($vendor['inventory_value'], 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
