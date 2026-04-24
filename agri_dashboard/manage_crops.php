<?php
include 'db.php';
if(!isset($_SESSION['user'])) header('Location: login.php');
$role = $_SESSION['role'];

// ---- DELETE CROP ----
if(isset($_GET['del']) && $role=='admin'){
    $id = (int)$_GET['del'];
    $stmt = $conn->prepare('DELETE FROM crops WHERE crop_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: manage_crops.php?success=1');
    exit;
}

// ---- ADD CROP ----
if(isset($_POST['add']) && $role=='admin'){
    $crop_name = $_POST['crop_name'];
    $crop_type = $_POST['crop_type'];
    $description = $_POST['description'];

    $stmt = $conn->prepare('INSERT INTO crops (crop_name, crop_type, description) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $crop_name, $crop_type, $description);
    $stmt->execute();

    header('Location: manage_crops.php?success=1');
    exit;
}

// ---- GRAPH DATA ----
$chart_labels = [];
$chart_counts = [];
$resChart = $conn->query("SELECT crop_type, COUNT(*) AS count FROM crops GROUP BY crop_type");
while($row = $resChart->fetch_assoc()){
    $chart_labels[] = $row['crop_type'];
    $chart_counts[] = $row['count'];
}

// ---- ORDER GRAPH DATA ----
$order_labels = [];
$order_counts = [];
$resOrders = $conn->query("SELECT MONTHNAME(order_date) AS month, COUNT(*) AS total FROM supplychainorders GROUP BY MONTH(order_date)");
while($row = $resOrders->fetch_assoc()){
    $order_labels[] = $row['month'];
    $order_counts[] = $row['total'];
}
?>
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<title>Manage Crops - Agri Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
body {
  font-family: 'Poppins', sans-serif;
  background: #0b0f19;
  color: #f1f1f1;
}
.sidebar {
  min-height: 100vh;
  background: #111827;
  color: #fff;
  padding-top: 20px;
}
.logo {
  font-weight: 700;
  padding: 10px 20px;
  font-size: 1.1rem;
  color: #10b981;
  display: flex;
  align-items: center;
  gap: 8px;
}
.nav-link {
  color: #9ca3af;
  padding: 10px 16px;
  display: block;
  text-decoration: none;
  transition: 0.3s;
}
.nav-link:hover {
  background: #1f2937;
  color: #fff;
}
.card {
  background: #1e293b;
  border: none;
  border-radius: 12px;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
}
.card-header {
  background: #10b981;
  color: #fff;
  font-weight: 600;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}
.btn-success {
  background: #10b981;
  border: none;
  transition: 0.3s;
}
.btn-success:hover {
  background: #059669;
}
.btn-outline-success {
  border-color: #10b981;
  color: #10b981;
}
.btn-outline-success:hover {
  background: #10b981;
  color: #fff;
}

/* ✅ TABLE STYLES */
.table {
  color: #ffffff !important;
  font-size: 15px;
}
.table thead th {
  color: #10b981 !important;
}
.table-striped>tbody>tr:nth-of-type(odd)>* {
  background-color: #1e293b !important;
  color: #ffffff !important;
}
.table-striped>tbody>tr:nth-of-type(even)>* {
  background-color: #111827 !important;
  color: #ffffff !important;
}
.table-hover tbody tr:hover>* {
  background-color: #374151 !important;
  color: #ffffff !important;
}

.modal-content {
  background: #111827;
  color: #fff;
}
footer {
  color: #9ca3af;
}

/* ✅ Improved Chart Layout */
.chart-card {
  background: #111827;
  border: none;
  border-radius: 12px;
  box-shadow: 0 0 12px rgba(0,0,0,0.4);
  padding: 20px;
}

.chart-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 25px;
  justify-content: center;
  align-items: start;
}
.chart-container canvas {
  width: 100% !important;
  height: 350px !important;
  background-color: #0b0f19;
  border-radius: 12px;
  padding: 10px;
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
        <h4 class="text-success">Crops</h4>
        <?php if($role=='admin'): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Crop</button>
        <?php endif; ?>
      </div>

      <!-- 🌿 Charts Section -->
      <div class="chart-card mt-3">
        <div class="chart-container">
          <div>
            <h5 class="text-success mb-3">Crop Type Distribution</h5>
            <canvas id="cropChart"></canvas>
          </div>
          <div>
            <h5 class="text-success mb-3">Monthly Orders</h5>
            <canvas id="orderChart"></canvas>
          </div>
        </div>
      </div>

      <!-- 🌱 Crop Table -->
      <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <input id="searchBox" class="form-control form-control-sm w-25" placeholder="Search...">
          <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('crops.csv')">Export CSV</button>
        </div>

        <div class="card-body table-responsive">
          <table id="dataTable" class="table table-striped align-middle table-hover">
            <thead>
              <tr>
                <th>Crop ID</th>
                <th>Crop Name</th>
                <th>Type</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $res = $conn->query('SELECT * FROM crops ORDER BY crop_id DESC');
              while($r = $res->fetch_assoc()):
              ?>
              <tr>
                <td><?= htmlspecialchars($r['crop_id']); ?></td>
                <td><?= htmlspecialchars($r['crop_name']); ?></td>
                <td><?= htmlspecialchars($r['crop_type']); ?></td>
                <td><?= htmlspecialchars($r['description']); ?></td>
                <td>
                  <?php if($role=='admin'): ?>
                    <a href="?del=<?= $r['crop_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this crop?')">Delete</a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ Add Crop Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title text-success">Add New Crop</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label>Crop Name:</label>
            <input name="crop_name" class="form-control" placeholder="Enter Crop Name" required>
          </div>
          <div class="mb-2">
            <label>Type:</label>
            <input name="crop_type" class="form-control" placeholder="Enter Crop Type" required>
          </div>
          <div class="mb-2">
            <label>Description:</label>
            <textarea name="description" class="form-control" placeholder="Enter Description" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-success" name="add">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="text-center mt-4 mb-3 small">
  Developed by <span class="text-success">Het Shah, Raam Bhanushali, Ishan Sharma</span> | 2025
</footer>

<script>
document.getElementById('searchBox').addEventListener('keyup', function(){
  let q = this.value.toLowerCase();
  document.querySelectorAll('#dataTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(q)?'':'none';
  });
});

function exportTableToCSV(filename){
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
}

// 🌾 Crop Type Doughnut Chart
const ctx = document.getElementById('cropChart').getContext('2d');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($chart_labels); ?>,
    datasets: [{
      data: <?= json_encode($chart_counts); ?>,
      backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#14b8a6'],
      borderColor: '#0b0f19',
      borderWidth: 2
    }]
  },
  options: {
    plugins:{
      legend:{labels:{color:'#fff'}},
      title:{display:true,text:'Crop Type Distribution',color:'#10b981'}
    }
  }
});

// 📊 Orders Bar Chart
const ctx2 = document.getElementById('orderChart').getContext('2d');
new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: <?= json_encode($order_labels); ?>,
    datasets: [{
      label: 'Total Orders',
      data: <?= json_encode($order_counts); ?>,
      backgroundColor: '#3b82f6',
      borderColor: '#10b981',
      borderWidth: 1
    }]
  },
  options: {
    scales:{
      x:{ticks:{color:'#fff'}, grid:{color:'#333'}},
      y:{ticks:{color:'#fff'}, grid:{color:'#333'}}
    },
    plugins:{
      legend:{labels:{color:'#fff'}},
      title:{display:true,text:'Monthly Orders',color:'#10b981'}
    }
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
