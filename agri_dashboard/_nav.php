<?php if(!isset($_SESSION['user'])) header('Location: login.php'); $user=$_SESSION['user']; $role=$_SESSION['role']; ?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="index.php"><img src="logo.svg" height="30" alt="logo" class="me-2">Agri SCM</a>
    <div class="d-flex align-items-center">
      <button id="themeToggle" class="btn btn-outline-secondary btn-sm me-2">🌙</button>
      <span class="me-3 text-muted small">Signed in as <strong><?php echo $user; ?></strong> (<?php echo $role; ?>)</span>
      <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>
  </div>
</nav>
<script>document.addEventListener('DOMContentLoaded', ()=>{ const t=document.getElementById('themeToggle'); t.addEventListener('click', ()=>{ document.body.classList.toggle('dark'); t.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙'; }); });</script>