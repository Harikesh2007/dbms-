<?php
// nav.php - include this at the top of every page
$current = basename($_SERVER['PHP_SELF']);
?>
<nav>
  <a href="index.php" class="nav-brand">✦ Voyager</a>
  <div class="nav-links">
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
      <a href="dashboard.php"   <?php if($current=='dashboard.php')   echo 'style="color:var(--gold)"'; ?>>Dashboard</a>
      <a href="packages.php"    <?php if($current=='packages.php')    echo 'style="color:var(--gold)"'; ?>>Packages</a>
      <a href="recommend.php"   <?php if($current=='recommend.php')   echo 'style="color:var(--gold)"'; ?>>Find by Budget</a>
      <a href="custom_trip.php" <?php if($current=='custom_trip.php') echo 'style="color:var(--gold)"'; ?>>Custom Trip</a>
      <a href="review.php"      <?php if($current=='review.php')      echo 'style="color:var(--gold)"'; ?>>Reviews</a>
      <a href="expense_charts.php" <?php if($current=='expense_charts.php') echo 'style="color:var(--gold)"'; ?>>📊 Charts</a>
      <a href="journal.php"   <?php if($current=='journal.php')   echo 'style="color:var(--gold)"'; ?>>📓 Journal</a>
      <a href="logout.php" class="btn btn-danger" style="padding:0.4rem 1rem; font-size:0.8rem; border-radius:4px; display:inline-block;">Logout</a>

    <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <a href="admin_dashboard.php" <?php if($current=='admin_dashboard.php') echo 'style="color:var(--gold)"'; ?>>Overview</a>
      <a href="logout.php" class="btn btn-danger" style="padding:0.4rem 1rem; font-size:0.8rem; border-radius:4px; display:inline-block;">Logout</a>

    <?php else: ?>
      <a href="packages.php"  <?php if($current=='packages.php')  echo 'style="color:var(--gold)"'; ?>>Packages</a>
      <a href="recommend.php" <?php if($current=='recommend.php') echo 'style="color:var(--gold)"'; ?>>Find by Budget</a>
      <a href="login.php"     <?php if($current=='login.php')      echo 'style="color:var(--gold)"'; ?>>Login</a>
      <a href="register.php"  <?php if($current=='register.php')   echo 'style="color:var(--gold)"'; ?>>Register</a>
      <a href="admin_login.php" <?php if($current=='admin_login.php') echo 'style="color:var(--gold)"'; ?>>Staff Login</a>
    <?php endif; ?>
  </div>
</nav>