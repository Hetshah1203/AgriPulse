<?php
include 'db.php';

// Fetch sales data ordered by Sale ID
$query = "SELECT sale_id, sale_price, sale_date FROM sales ORDER BY sale_id ASC";
$result = $conn->query($query);

$saleIds = [];
$salePrices = [];
$saleDates = [];

// Store data
while ($row = $result->fetch_assoc()) {
    $saleIds[] = $row['sale_id'];
    $salePrices[] = $row['sale_price'];
    $saleDates[] = $row['sale_date'];
}

// Compute average between every 2 consecutive sales
$pairwiseAvg = [];
for ($i = 0; $i < count($salePrices) - 1; $i++) {
    $avg = ($salePrices[$i] + $salePrices[$i + 1]) / 2;
    $pairwiseAvg[] = $avg;
}

// Create sale ID pairs for labeling
$salePairs = [];
for ($i = 0; $i < count($saleIds) - 1; $i++) {
    $salePairs[] = $saleIds[$i] . " - " . $saleIds[$i + 1];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Average Between Two Sale Prices</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 30px;
            background: #0b1220;
            color: white;
        }
        h2 {
            text-align: center;
            color: #10b981;
            margin-top: 40px;
        }
        .chart-container {
            width: 80%;
            margin: 30px auto;
            background: #071026;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(16,185,129,0.3);
        }
        table {
            width: 80%;
            margin: 30px auto;
            border-collapse: collapse;
            background: #071026;
            color: white;
        }
        th, td {
            padding: 12px;
            border: 1px solid #10b981;
            text-align: center;
        }
        th {
            background-color: #10b981;
            color: #fff;
        }
        tr:hover {
            background-color: #1f2937;
        }
    </style>
</head>
<body>

<h2>📊 Sale Prices and Pairwise Average Comparison</h2>

Chart 1: Sale Prices vs Average
<div class="chart-container">
    <canvas id="salesChart"></canvas>
</div>

<!-- Chart 2: Pairwise Average Only -->
<h2>📈 Average Between Every Two Consecutive Sales</h2>
<div class="chart-container">
    <canvas id="averageChart"></canvas>
</div>

<!-- Average Table -->
<h2>🧮 Average Between Every Two Consecutive Sales</h2>
<table>
    <tr>
        <th>Sale ID Pair</th>
        <th>Average Price (₹)</th>
    </tr>
    <?php for ($i = 0; $i < count($pairwiseAvg); $i++): ?>
    <tr>
        <td><?= htmlspecialchars($salePairs[$i]) ?></td>
        <td><b><?= number_format($pairwiseAvg[$i], 2) ?></b></td>
    </tr>
    <?php endfor; ?>
</table>

<script>
// === Chart 1: Sale Prices + Pairwise Average ===
const ctx1 = document.getElementById('salesChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode($saleIds) ?>,
        datasets: [
            {
                label: 'Sale Price (₹)',
                data: <?= json_encode($salePrices) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.2)',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6'
            },
            {
                label: 'Pairwise Average (₹)',
                data: <?= json_encode($pairwiseAvg) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.3)',
                fill: false,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#10b981'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Sale Price vs Average Between Consecutive Sales',
                color: 'white',
                font: { size: 18 }
            },
            legend: { position: 'top', labels: { color: 'white' } }
        },
        scales: {
            x: { ticks: { color: 'white' }, grid: { color: '#1f2937' } },
            y: { ticks: { color: 'white' }, grid: { color: '#1f2937' }, beginAtZero: true }
        }
    }
});

// === Chart 2: Pairwise Average Only ===
const ctx2 = document.getElementById('averageChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode($salePairs) ?>,
        datasets: [{
            label: 'Average Between Two Consecutive Sales (₹)',
            data: <?= json_encode($pairwiseAvg) ?>,
            backgroundColor: 'rgba(16,185,129,0.7)',
            borderColor: '#10b981',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Average Between Every Two Consecutive Sales (₹)',
                color: 'white',
                font: { size: 18 }
            },
            legend: { position: 'top', labels: { color: 'white' } }
        },
        scales: {
            x: { ticks: { color: 'white', font: { size: 12 } }, grid: { color: '#1f2937' } },
            y: { ticks: { color: 'white' }, grid: { color: '#1f2937' }, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
