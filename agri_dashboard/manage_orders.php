<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['user'])) header('Location: login.php');
$role = $_SESSION['role'];

// DELETE
if(isset($_GET['del']) && $role == 'admin'){
    $id = (int)$_GET['del'];
    $stmt = $conn->prepare('DELETE FROM supplychainorders WHERE order_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: manage_orders.php?success=1');
    exit;
}

// ADD (only 4 fields)
if(isset($_POST['add']) && $role == 'admin'){
    $quantity = $_POST['quantity'];
    $order_date = $_POST['order_date'];
    $status = $_POST['status'];
    $price_per_kg = $_POST['price_per_kg'];

    $farm_id = 1;
    $crop_id = 1;

    $stmt = $conn->prepare('INSERT INTO supplychainorders (farm_id, crop_id, quantity, order_date, status, price_per_kg) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iissss', $farm_id, $crop_id, $quantity, $order_date, $status, $price_per_kg);
    $stmt->execute();

    header('Location: manage_orders.php?success=1');
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<title>Manage - Supplychain Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="style.css">

<style>
body {
  background-color: #0f172a;
  color: #f1f5f9;
  font-family: 'Poppins', sans-serif;
}
.card {
  background-color: #1e293b !important;
  color: #f8fafc;
  border: none !important;
  box-shadow: 0 0 15px rgba(0,0,0,0.4);
}
.card-header {
  background-color: #334155 !important;
  color: #f8fafc !important;
  font-weight: 600;
}
.table {
  color: #e2e8f0;
}
.table thead {
  background-color: #0ea5e9;
  color: #fff;
}
.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255,255,255,0.05);
}
.btn {
  border-radius: 8px;
}
.btn-primary {
  background-color: #2563eb !important;
  border: none !important;
}
.btn-success {
  background-color: #22c55e !important;
  border: none !important;
}
.btn-outline-success {
  color: #22c55e;
  border-color: #22c55e;
}
.btn-outline-success:hover {
  background-color: #22c55e;
  color: #0f172a;
}
footer {
  color: #000; /* black font for footer */
}
</style>
</head>
<body>
<?php include '_nav.php'; include 'toast.php'; ?>
<div class="container-fluid mt-3">
<div class="row">
<?php include '_sidebar.php'; ?>
<div class="col-md-10">

<div class="d-flex justify-content-between align-items-center">
  <h4>Supplychain Orders</h4>
  <?php if($role=='admin') echo '<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add</button>'; ?>
</div>

<!-- Chart Section -->
<div class="card mt-3">
  <div class="card-header">Monthly Orders Trend</div>
  <div class="card-body"><canvas id="ordersChart" height="100"></canvas></div>
</div>

<!-- Table -->
<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <input id="searchBox" class="form-control form-control-sm bg-dark text-light border-0" style="width:220px" placeholder="Search...">
    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('supplychainorders.csv')">Export CSV</button>
  </div>
  <div class="card-body table-responsive">
    <table id="dataTable" class="table table-striped">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Farm ID</th>
          <th>Crop ID</th>
          <th>Quantity</th>
          <th>Order Date</th>
          <th>Status</th>
          <th>Price/kg</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $res = $conn->query('SELECT * FROM supplychainorders');
      while($r = $res->fetch_assoc()){
          echo "<tr>";
          echo "<td>".htmlspecialchars($r['order_id'])."</td>";
          echo "<td>".htmlspecialchars($r['farm_id'])."</td>";
          echo "<td>".htmlspecialchars($r['crop_id'])."</td>";
          echo "<td>".htmlspecialchars($r['quantity'])."</td>";
          echo "<td>".htmlspecialchars($r['order_date'])."</td>";
          echo "<td>".htmlspecialchars($r['status'])."</td>";
          echo "<td>".htmlspecialchars($r['price_per_kg'])."</td>";
          echo "<td>";
          if($role == 'admin')
              echo "<a href='?del={$r['order_id']}' class='btn btn-sm btn-danger'>Delete</a>";
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
<div class='modal fade' id='addModal' tabindex='-1'>
<div class='modal-dialog'>
<div class='modal-content'>
<form method='post'>
  <div class='modal-header'>
    <h5 class='modal-title'>Add Order</h5>
    <button class='btn-close' data-bs-dismiss='modal'></button>
  </div>
  <div class='modal-body'>
    <div class='mb-2'>
      <input name='quantity' class='form-control' placeholder='Quantity' required>
    </div>
    <div class='mb-2'>
      <input name='order_date' type='date' class='form-control' required>
    </div>
    <div class='mb-2'>
      <input name='status' class='form-control' placeholder='Status' required>
    </div>
    <div class='mb-2'>
      <input name='price_per_kg' class='form-control' placeholder='Price per kg' required>
    </div>
  </div>
  <div class='modal-footer'>
    <button class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
    <button class='btn btn-primary' name='add'>Save</button>
  </div>
</form>
</div>
</div>
</div>

<?php
// Fetch data for Monthly Orders Graph
$chartData = $conn->query("
  SELECT DATE_FORMAT(order_date,'%b') as month, COUNT(*) as total_orders, SUM(quantity) as total_quantity
  FROM supplychainorders
  GROUP BY month ORDER BY MIN(order_date)
");
$months=[]; $orders=[]; $quantities=[];
while($r=$chartData->fetch_assoc()){
  $months[]=$r['month'];
  $orders[]=(int)$r['total_orders'];
  $quantities[]=(int)$r['total_quantity'];
}
?>

<script>
document.addEventListener('DOMContentLoaded', ()=>{

  // Search Table
  document.getElementById('searchBox').addEventListener('keyup', function(){
      let q = this.value.toLowerCase();
      document.querySelectorAll('#dataTable tbody tr').forEach(tr=>{
          tr.style.display = tr.textContent.toLowerCase().includes(q)?'':'none';
      });
  });

  // Export CSV
  window.exportTableToCSV = function(filename){
      let csv = [];
      document.querySelectorAll('#dataTable tr').forEach(tr=>{
          let row = Array.from(tr.querySelectorAll('td,th')).map(td=>'"'+td.innerText.replace(/"/g,'""')+'"');
          csv.push(row.join(','));
      });
      let blob = new Blob([csv.join('\n')], {type:'text/csv'});
      let a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename;
      a.click();
  };

  // Monthly Orders Trend Chart
  new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($months); ?>,
      datasets: [
        {
          label: 'Total Orders',
          data: <?php echo json_encode($orders); ?>,
          backgroundColor: 'rgba(34,197,94,0.8)',
          borderRadius: 6
        },
        {
          label: 'Total Quantity (kg)',
          data: <?php echo json_encode($quantities); ?>,
          backgroundColor: 'rgba(59,130,246,0.8)',
          borderRadius: 6
        }
      ]
    },
    options: {
      scales: {
        x: { ticks: { color: '#cbd5e1' } },
        y: { ticks: { color: '#cbd5e1' }, beginAtZero: true }
      },
      plugins: {
        legend: { labels: { color: '#f8fafc' } },
        title: {
          display: true,
          text: 'Monthly Supply Chain Orders Overview',
          color: '#f8fafc'
        }
      }
    }
  });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
