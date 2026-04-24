<?php
include 'db.php';
if(!isset($_SESSION['user'])) header('Location: login.php');
?>
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<title>Dashboard - Agri SCM v3</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
  background-color: #22c55e;
  color: #fff;
}
.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255,255,255,0.05);
}
input, .btn {
  border-radius: 8px;
}
.btn-outline-success {
  color: #22c55e;
  border-color: #22c55e;
}
.btn-outline-success:hover {
  background-color: #22c55e;
  color: #0f172a;
}
.count {
  color: #22c55e;
}
footer {
  color: #94a3b8;
}
canvas {
  background: transparent;
}
</style>
</head>
<body>
<?php include '_nav.php'; ?>
<div class="container-fluid mt-3">
  <div class="row">
    <?php include '_sidebar.php'; ?>
    <div class="col-md-10">

      <!-- Summary Cards -->
      <div class="row g-3">
        <?php
        $cards = [
          ['Total Farmers', 'SELECT COUNT(*) as c FROM farmers'],
          ['Total Crops', 'SELECT COUNT(*) as c FROM crops'],
          ['Total Warehouses', 'SELECT COUNT(*) as c FROM warehouses'],
          ['Total Sales', 'SELECT COUNT(*) as c FROM sales']
        ];
        foreach($cards as $cd){
          $res = $conn->query($cd[1]);
          $r = $res->fetch_assoc();
          echo "<div class='col-md-3'>
                  <div class='card text-center'>
                    <div class='card-body'>
                      <h6 class='text-secondary'>{$cd[0]}</h6>
                      <h3 class='count fw-bold'>{$r['c']}</h3>
                    </div>
                  </div>
                </div>";
        }
        ?>
      </div>

      <!-- Graphs -->
      <div class="row mt-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">Monthly Sales Trend</div>
            <div class="card-body"><canvas id="salesTrend"></canvas></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">Orders by Status</div>
            <div class="card-body"><canvas id="statusChart"></canvas></div>
          </div>
        </div>
      </div>

      <!-- Overview Graph -->
      <div class="card mt-4">
        <div class="card-header">Comprehensive Supply Chain Overview</div>
        <div class="card-body"><canvas id="overviewChart"></canvas></div>
      </div>

      <!-- Table -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Recent Sales</span>
          <div>
            <input id="tableSearch" class="form-control form-control-sm d-inline-block bg-dark text-light border-0" style="width:220px" placeholder="Search table...">
            <button class="btn btn-outline-success btn-sm ms-2" onclick="exportTableToCSV('sales.csv')">Export CSV</button>
          </div>
        </div>
        <div class="card-body table-responsive">
          <table id="mainTable" class="table table-striped">
            <thead>
              <tr><th>Sale ID</th><th>Retailer</th><th>Qty (kg)</th><th>Price</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php
            $q = "SELECT s.sale_id,r.name,s.quantity_kg,s.sale_price,s.sale_date
                  FROM sales s JOIN retailers r ON s.retailer_id=r.retailer_id
                  ORDER BY s.sale_date DESC LIMIT 10";
            $res = $conn->query($q);
            while($row = $res->fetch_assoc()){
              echo "<tr>
                      <td>{$row['sale_id']}</td>
                      <td>{$row['name']}</td>
                      <td>{$row['quantity_kg']}</td>
                      <td>₹{$row['sale_price']}</td>
                      <td>{$row['sale_date']}</td>
                    </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>

      <footer class="text-center mt-4 mb-2 small" 
        style="color:#000; background:#f8fafc; padding:10px; border-radius:6px;">
  Developed by Het Shah, Raam Bhanushali, Ishan Sharma | 2025
</footer>

    </div>
  </div>
</div>

<?php
// Chart Data
$salesChart = $conn->query("SELECT DATE_FORMAT(sale_date,'%b') as mon, SUM(sale_price) as total FROM sales GROUP BY mon ORDER BY MIN(sale_date)");
$months=[]; $totals=[];
while($r=$salesChart->fetch_assoc()){ $months[]=$r['mon']; $totals[]=(int)$r['total']; }

$statusData = $conn->query("SELECT status, COUNT(*) as total FROM supplychainorders GROUP BY status");
$sn=[]; $st=[];
while($r=$statusData->fetch_assoc()){ $sn[]=$r['status']; $st[]=(int)$r['total']; }

$overview = $conn->query("
  SELECT DATE_FORMAT(o.order_date,'%b') AS mon,
         COUNT(o.order_id) AS total_orders,
         IFNULL(SUM(s.sale_price),0) AS total_sales,
         ROUND(AVG(o.price_per_kg),2) AS avg_price
  FROM supplychainorders o
  LEFT JOIN sales s ON DATE_FORMAT(s.sale_date,'%b') = DATE_FORMAT(o.order_date,'%b')
  GROUP BY mon ORDER BY MIN(o.order_date)
");
$o_months=[]; $o_orders=[]; $o_sales=[]; $o_price=[];
while($r=$overview->fetch_assoc()){
  $o_months[]=$r['mon'];
  $o_orders[]=(int)$r['total_orders'];
  $o_sales[]=(int)$r['total_sales'];
  $o_price[]=(float)$r['avg_price'];
}
?>

<script>
document.addEventListener('DOMContentLoaded', ()=>{

  // Animate Counters
  document.querySelectorAll('.count').forEach(el=>{
    let end=+el.textContent; el.textContent='0';
    let step=Math.ceil(end/50)||1; let cur=0;
    let t=setInterval(()=>{cur+=step; el.textContent=cur;
      if(cur>=end){ el.textContent=end; clearInterval(t);} },20);
  });

// Enhanced Monthly Sales Trend (Gradient + Dual Type)
const salesCtx = document.getElementById('salesTrend').getContext('2d');
const gradient = salesCtx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(34,197,94,0.5)');
gradient.addColorStop(1, 'rgba(34,197,94,0.05)');

new Chart(salesCtx, {
  data: {
    labels: <?php echo json_encode($months); ?>,
    datasets: [
      {
        type: 'bar',
        label: 'Sales (₹)',
        data: <?php echo json_encode($totals); ?>,
        backgroundColor: 'rgba(59,130,246,0.4)',
        borderColor: '#3b82f6',
        borderRadius: 8,
        barThickness: 24
      },
      {
        type: 'line',
        label: 'Growth Trend',
        data: <?php echo json_encode($totals); ?>,
        borderColor: '#22c55e',
        backgroundColor: gradient,
        tension: 0.35,
        fill: true,
        pointBackgroundColor: '#22c55e',
        pointRadius: 4
      }
    ]
  },
  options: {
    scales: {
      x: {
        ticks: { color: '#cbd5e1' },
        grid: { color: 'rgba(255,255,255,0.05)' }
      },
      y: {
        beginAtZero: true,
        ticks: { color: '#cbd5e1' },
        grid: { color: 'rgba(255,255,255,0.05)' }
      }
    },
    plugins: {
      legend: {
        labels: { color: '#f8fafc' },
        position: 'bottom'
      },
      title: {
        display: true,
        text: 'Enhanced Monthly Sales Overview',
        color: '#f8fafc',
        padding: 10,
        font: { size: 16, weight: 'bold' }
      },
      tooltip: {
        backgroundColor: '#1e293b',
        borderColor: '#22c55e',
        borderWidth: 1,
        titleColor: '#fff',
        bodyColor: '#e2e8f0'
      }
    },
    interaction: { mode: 'index', intersect: false }
  }
});

  // Chart: Orders by Status
  new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data:{
      labels:<?php echo json_encode($sn); ?>,
      datasets:[{
        data:<?php echo json_encode($st); ?>,
        backgroundColor:['#22c55e','#3b82f6','#facc15','#ef4444','#a855f7','#14b8a6'],
        borderColor:'#1e293b',
        borderWidth:2
      }]
    },
    options:{
      plugins:{
        legend:{labels:{color:'#f1f5f9'},position:'bottom'},
        title:{display:true,text:'Orders by Status',color:'#f1f5f9'}
      }
    }
  });

  // Chart: Comprehensive Overview
  const ctx=document.getElementById('overviewChart').getContext('2d');
  new Chart(ctx,{
    data:{
      labels:<?php echo json_encode($o_months); ?>,
      datasets:[
        {type:'bar',label:'Total Orders',data:<?php echo json_encode($o_orders); ?>,backgroundColor:'rgba(34,197,94,0.7)',borderRadius:6},
        {type:'bar',label:'Total Sales (₹)',data:<?php echo json_encode($o_sales); ?>,backgroundColor:'rgba(59,130,246,0.7)',borderRadius:6},
        {type:'line',label:'Avg Price/kg (₹)',data:<?php echo json_encode($o_price); ?>,borderColor:'#facc15',borderWidth:3,tension:0.4,yAxisID:'y1'}
      ]
    },
    options:{
      interaction:{mode:'index',intersect:false},
      stacked:false,
      scales:{
        x:{ticks:{color:'#cbd5e1'},grid:{color:'rgba(255,255,255,0.1)'}},
        y:{beginAtZero:true,ticks:{color:'#cbd5e1'},grid:{color:'rgba(255,255,255,0.1)'}},
        y1:{beginAtZero:true,position:'right',ticks:{color:'#facc15'},grid:{drawOnChartArea:false}}
      },
      plugins:{
        legend:{labels:{color:'#f1f5f9'},position:'bottom'},
        title:{display:true,text:'Comprehensive Supply Chain Overview',color:'#f1f5f9'}
      }
    }
  });

  // Table Search
  document.getElementById('tableSearch').addEventListener('keyup', function(){
    let q=this.value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(tr=>{
      tr.style.display = tr.textContent.toLowerCase().includes(q)?'':'none';
    });
  });
});

// CSV Export
function exportTableToCSV(filename){
  let csv=[];
  document.querySelectorAll('#mainTable tr').forEach(tr=>{
    let row = Array.from(tr.querySelectorAll('td,th')).map(td=>'"'+td.innerText.replace(/"/g,'""')+'"');
    csv.push(row.join(','));
  });
  let blob=new Blob([csv.join('\n')],{type:'text/csv'});
  let a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download=filename;
  a.click();
}
</script>
</body>
</html>
 <!-- #region -->