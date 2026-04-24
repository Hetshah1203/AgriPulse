<?php 
include 'db.php'; 
if(!isset($_SESSION['user'])) header('Location: login.php'); 
$role = $_SESSION['role'];

// Delete retailer
if(isset($_GET['del']) && $role=='admin'){ 
    $id = (int)$_GET['del']; 
    $stmt = $conn->prepare('DELETE FROM retailers WHERE retailer_id=?'); 
    $stmt->bind_param('i',$id); 
    $stmt->execute(); 
    header('Location: manage_retailers.php?success=1'); 
    exit; 
}

// Add retailer
if(isset($_POST['add']) && $role=='admin'){ 
    $name = $_POST['name']; 
    $phone = $_POST['phone']; 
    $address = $_POST['address']; 
    $email = $_POST['email']; 
    $source_type = $_POST['source_type']; 
    $warehouse_id = $_POST['warehouse_id']; 
    $stmt = $conn->prepare('INSERT INTO retailers (name,phone,address,email,source_type,warehouse_id) VALUES (?,?,?,?,?,?)'); 
    $stmt->bind_param('ssssss', $name, $phone, $address, $email, $source_type, $warehouse_id); 
    $stmt->execute(); 
    header('Location: manage_retailers.php?success=1'); 
    exit; 
}
?>
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<title>Manage - Retailers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
  background: #0b1220;
  color: #e5e7eb;
  font-family: 'Poppins', sans-serif;
}
.card {
  background: #111827;
  border: none;
  color: #e5e7eb;
}
.card-header {
  background: #10b981;
  color: white;
  font-weight: 600;
}
.table {
  color: #e5e7eb;
}
.table-striped tbody tr:nth-of-type(odd) {
  background-color: #1f2937;
}
.btn-success, .btn-primary {
  background-color: #10b981;
  border: none;
}
.btn-success:hover, .btn-primary:hover {
  background-color: #059669;
}
footer {
  color: #000;
}
canvas {
  background: #111827;
  border-radius: 10px;
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
        <h4>Retailers</h4>
        <?php if($role=='admin') echo '<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add</button>'; ?>
      </div>

      <!-- Chart Section -->
      <div class="card mt-3">
        <div class="card-header">Retailers by Source Type</div>
        <div class="card-body">
          <canvas id="sourceTypeChart" height="100"></canvas>
        </div>
      </div>

      <!-- Retailers Table -->
      <div class="card mt-3">
        <div class="card-header d-flex justify-content-between">
          <input id="searchBox" class="form-control form-control-sm" placeholder="Search...">
          <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('retailers.csv')">Export CSV</button>
        </div>
        <div class="card-body table-responsive">
          <table id="dataTable" class="table table-striped">
            <thead>
              <tr>
                <th>Name</th><th>Phone</th><th>Address</th><th>Email</th><th>Source Type</th><th>Warehouse ID</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $res = $conn->query('SELECT * FROM retailers'); 
              while($r = $res->fetch_assoc()){ 
                echo "<tr>";
                echo '<td>'.htmlspecialchars($r['name']).'</td>';
                echo '<td>'.htmlspecialchars($r['phone']).'</td>';
                echo '<td>'.htmlspecialchars($r['address']).'</td>';
                echo '<td>'.htmlspecialchars($r['email']).'</td>';
                echo '<td>'.htmlspecialchars($r['source_type']).'</td>';
                echo '<td>'.htmlspecialchars($r['warehouse_id']).'</td>';
                echo '<td>'; 
                if($role=='admin') echo "<a href='?del={$r['retailer_id']}' class='btn btn-sm btn-danger'>Delete</a>"; 
                echo '</td></tr>'; 
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
          <h5 class='modal-title'>Add Retailer</h5>
          <button class='btn-close' data-bs-dismiss='modal'></button>
        </div>
        <div class='modal-body'>
          <div class='mb-2'><input name='name' class='form-control' placeholder='Name' required></div>
          <div class='mb-2'><input name='phone' class='form-control' placeholder='Phone' required></div>
          <div class='mb-2'><input name='address' class='form-control' placeholder='Address' required></div>
          <div class='mb-2'><input name='email' class='form-control' placeholder='Email' required></div>
          <div class='mb-2'><input name='source_type' class='form-control' placeholder='Source Type' required></div>
          <div class='mb-2'><input name='warehouse_id' class='form-control' placeholder='Warehouse ID' required></div>
        </div>
        <div class='modal-footer'>
          <button class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
          <button class='btn btn-primary' name='add'>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Search filter
document.getElementById('searchBox').addEventListener('keyup', function(){
  let q = this.value.toLowerCase();
  document.querySelectorAll('#dataTable tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// Export to CSV
function exportTableToCSV(filename){
  let csv = [];
  document.querySelectorAll('#dataTable tr').forEach(tr => {
    let row = Array.from(tr.querySelectorAll('td,th')).map(td => '"' + td.innerText.replace(/"/g,'""') + '"');
    csv.push(row.join(','));
  });
  let blob = new Blob([csv.join('\n')], {type:'text/csv'});
  let a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
}

// Chart Data
<?php
$data = [];
$res2 = $conn->query("SELECT source_type, COUNT(*) as total FROM retailers GROUP BY source_type");
while($row = $res2->fetch_assoc()) {
  $data[$row['source_type']] = $row['total'];
}
?>

const ctx = document.getElementById('sourceTypeChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode(array_keys($data)); ?>,
    datasets: [{
      label: 'Retailers by Source Type',
      data: <?php echo json_encode(array_values($data)); ?>,
      backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6']
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { labels: { color: '#e5e7eb' } },
      title: {
        display: true,
        text: 'Retailer Distribution by Source Type',
        color: '#e5e7eb',
        font: { size: 16 }
      }
    },
    scales: {
      x: { ticks: { color: '#e5e7eb' }, grid: { color: '#1f2937' } },
      y: { ticks: { color: '#e5e7eb' }, grid: { color: '#1f2937' } }
    }
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
