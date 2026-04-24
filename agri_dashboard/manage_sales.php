<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])) header('Location: login.php'); 
$role = $_SESSION['role'];

// DELETE
if(isset($_GET['del']) && $role=='admin'){ 
  $id=(int)$_GET['del']; 
  $stmt=$conn->prepare('DELETE FROM sales WHERE sale_id=?'); 
  $stmt->bind_param('i',$id); 
  $stmt->execute(); 
  header('Location: manage_sales.php?success=1'); 
  exit; 
}

// ADD
if(isset($_POST['add']) && $role=='admin'){ 
  $order_id = $_POST['order_id']; 
  $retailer_id = $_POST['retailer_id']; 
  $quantity_kg = $_POST['quantity_kg']; 
  $sale_price = $_POST['sale_price']; 
  $sale_date = $_POST['sale_date']; 
  $stmt=$conn->prepare('INSERT INTO sales (order_id,retailer_id,quantity_kg,sale_price,sale_date) VALUES (?,?,?,?,?)'); 
  $stmt->bind_param('sssss', $order_id, $retailer_id, $quantity_kg, $sale_price, $sale_date); 
  $stmt->execute(); 
  header('Location: manage_sales.php?success=1'); 
  exit; 
}

// MONTHLY SALES DATA
$query = "
  SELECT DATE_FORMAT(sale_date, '%Y-%m') AS month, 
         SUM(quantity_kg * sale_price) AS total_sales
  FROM sales 
  GROUP BY month
  ORDER BY month ASC";
$result = $conn->query($query);

$months = [];
$totals = [];
while($row = $result->fetch_assoc()){
  $months[] = $row['month'];
  $totals[] = $row['total_sales'];
}

// CUMULATIVE AVERAGE PRICE BY DATE
$query2 = "
  SELECT sale_date, sale_price
  FROM sales
  ORDER BY sale_date ASC";
$res2 = $conn->query($query2);

$dates = [];
$prices = [];
$cum_avg = [];
$sum = 0; $count = 0;

while($row = $res2->fetch_assoc()){
  $dates[] = $row['sale_date'];
  $prices[] = (float)$row['sale_price'];
  $sum += $row['sale_price'];
  $count++;
  $cum_avg[] = round($sum / $count, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { background-color: #0b1220; color: #e5e7eb; font-family: 'Poppins', sans-serif; }
    .card { background-color: #111827; border: none; color: #e5e7eb; border-radius: 10px; }
    .card-header { background-color: #10b981; color: white; font-weight: 600; border-top-left-radius: 10px; border-top-right-radius: 10px; }
    .table-dark th { background-color: #1f2937; color: #fff; }
    footer { color: #e5e7eb; }
    .chart-container { position: relative; height: 400px; }
  </style>
</head>
<body>

<?php include '_nav.php'; include 'toast.php'; ?>

<div class="container-fluid mt-3">
  <div class="row">
    <?php include '_sidebar.php'; ?>
    <div class="col-md-10">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="fw-bold">Manage Sales</h4>
        <?php if($role=='admin') echo '<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Sale</button>'; ?>
      </div>

      <!-- Monthly Sales Graph -->
      <div class="card mt-4">
        <div class="card-header">📊 Monthly Sales Trend</div>
        <div class="card-body chart-container">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <!-- Cumulative Average Graph -->
      <div class="card mt-4">
        <div class="card-header">📈 Cumulative Average Price</div>
        <div class="card-body chart-container">
          <canvas id="avgChart"></canvas>
        </div>
      </div>

      <!-- Sales Table -->
      <div class="card mt-3 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Sales Records</span>
          <div class="d-flex align-items-center gap-2">
            <input id="searchBox" class="form-control form-control-sm" placeholder="Search..." style="width:200px;">
            <button class="btn btn-light btn-sm" onclick="exportTableToCSV('sales.csv')">Export CSV</button>
          </div>
        </div>

        <div class="card-body table-responsive">
          <table id="dataTable" class="table table-dark table-striped align-middle">
            <thead>
              <tr>
                <th>Sale ID</th>
                <th>Order ID</th>
                <th>Retailer ID</th>
                <th>Quantity (kg)</th>
                <th>Sale Price</th>
                <th>Sale Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $res=$conn->query('SELECT * FROM sales ORDER BY order_id ASC');
              while($r=$res->fetch_assoc()){ 
                echo "<tr>
                  <td>".htmlspecialchars($r['sale_id'])."</td>
                  <td>".htmlspecialchars($r['order_id'])."</td>
                  <td>".htmlspecialchars($r['retailer_id'])."</td>
                  <td>".htmlspecialchars($r['quantity_kg'])."</td>
                  <td>".htmlspecialchars($r['sale_price'])."</td>
                  <td>".htmlspecialchars($r['sale_date'])."</td>
                  <td>";
                if($role=='admin') echo "<a href='?del={$r['sale_id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this record?');\">Delete</a>";
                echo "</td></tr>";
              } 
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <footer class="text-center mt-4 mb-2 small">
        Developed by Het Shah, Raam Bhanushali, Ishan Sharma | 2025
      </footer>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-light">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Add Sale Record</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><input name="order_id" class="form-control" placeholder="Order ID" required></div>
          <div class="mb-2"><input name="retailer_id" class="form-control" placeholder="Retailer ID" required></div>
          <div class="mb-2"><input name="quantity_kg" type="number" class="form-control" placeholder="Quantity (kg)" required></div>
          <div class="mb-2"><input name="sale_price" type="number" class="form-control" placeholder="Sale Price" required></div>
          <div class="mb-2"><input name="sale_date" type="date" class="form-control" required></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" name="add">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// SEARCH FUNCTION
document.getElementById('searchBox').addEventListener('keyup', function(){
  let q = this.value.toLowerCase();
  document.querySelectorAll('#dataTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// EXPORT CSV
function exportTableToCSV(filename){
  let csv=[];
  document.querySelectorAll('#dataTable tr').forEach(tr=>{
    let row = Array.from(tr.querySelectorAll('td,th')).map(td=>'"'+td.innerText.replace(/"/g,'""')+'"');
    csv.push(row.join(','));
  });
  let blob = new Blob([csv.join('\n')], {type:'text/csv'});
  let a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
}

// --- CHART 1: Monthly Sales ---
const ctx1 = document.getElementById('salesChart');
new Chart(ctx1, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($months); ?>,
    datasets: [{
      label: 'Total Monthly Sales (₹)',
      data: <?php echo json_encode($totals); ?>,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.3)',
      fill: true,
      tension: 0.4,
      borderWidth: 3,
      pointRadius: 5,
      pointHoverRadius: 8,
      pointBackgroundColor: '#10b981',
      pointHoverBackgroundColor: '#34d399',
    }]
  },
  options: {
    plugins: {
      legend: { labels: { color: '#e5e7eb' } },
    },
    scales: {
      x: { ticks: { color: '#e5e7eb' } },
      y: { ticks: { color: '#e5e7eb' } }
    },
    responsive: true,
    maintainAspectRatio: false
  }
});

// --- CHART 2: Cumulative Average Price ---
const ctx2 = document.getElementById('avgChart');
new Chart(ctx2, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($dates); ?>,
    datasets: [
      {
        label: 'Sale Price (₹)',
        data: <?php echo json_encode($prices); ?>,
        borderColor: '#3b82f6',
        tension: 0.3,
        borderWidth: 2,
        fill: false,
      },
      {
        label: 'Cumulative Average (₹)',
        data: <?php echo json_encode($cum_avg); ?>,
        borderColor: '#ef4444',
        tension: 0.3,
        borderWidth: 3,
        fill: false,
      }
    ]
  },
  options: {
    plugins: {
      legend: { labels: { color: '#e5e7eb' } },
    },
    scales: {
      x: { ticks: { color: '#e5e7eb' } },
      y: { ticks: { color: '#e5e7eb' } }
    },
    responsive: true,
    maintainAspectRatio: false
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
