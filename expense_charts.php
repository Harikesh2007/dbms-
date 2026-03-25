<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Get all expenses grouped by type for this user
$expense_data = mysqli_fetch_all(mysqli_query($conn,
    "SELECT e.expense_type, SUM(e.amount) AS total_amount, COUNT(*) AS count
     FROM expense e
     JOIN trip t ON e.trip_id = t.trip_id
     WHERE t.user_id = '$user_id'
     GROUP BY e.expense_type
     ORDER BY total_amount DESC"), MYSQLI_ASSOC);

// Monthly spending trend (last 12 months)
$monthly_data = mysqli_fetch_all(mysqli_query($conn,
    "SELECT DATE_FORMAT(e.expense_date, '%Y-%m') AS month,
            SUM(e.amount) AS total
     FROM expense e
     JOIN trip t ON e.trip_id = t.trip_id
     WHERE t.user_id = '$user_id'
       AND e.expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"), MYSQLI_ASSOC);

// Per-trip spending comparison
$trip_spending = mysqli_fetch_all(mysqli_query($conn,
    "SELECT t.trip_name, t.total_budget, t.total_expense
     FROM trip t
     WHERE t.user_id = '$user_id' AND t.trip_status != 'Cancelled'
     ORDER BY t.trip_id DESC
     LIMIT 8"), MYSQLI_ASSOC);

$total_spent = array_sum(array_column($expense_data, 'total_amount'));

// Color palette for charts
$colors = ['#c9a84c','#e8c97e','#64c878','#64a0ff','#e07070','#b06ec9','#50c8c8','#ff9f43','#a0a4b8','#f06595'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expense Analytics — Voyager</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">📊 Expense Analytics</div>
  <p class="section-subtitle">Visual breakdown of your travel spending</p>

  <?php if(empty($expense_data)): ?>
  <div class="card" style="text-align:center; padding:4rem 2rem;">
    <div style="font-size:3rem; margin-bottom:1rem;">📊</div>
    <h3 style="margin-bottom:0.5rem;">No expense data yet</h3>
    <p style="color:var(--muted); margin-bottom:2rem;">Add expenses to your trips to see charts and insights here.</p>
    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
  </div>
  <?php else: ?>

  <!-- Summary Stats -->
  <div class="stat-grid" style="margin-bottom:2.5rem;">
    <div class="stat-card">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value">₹<?php echo number_format($total_spent); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Expense Categories</div>
      <div class="stat-value"><?php echo count($expense_data); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Top Category</div>
      <div style="font-family:'Playfair Display',serif; font-size:1.2rem; color:var(--gold); margin-top:0.3rem;">
        <?php echo htmlspecialchars($expense_data[0]['expense_type'] ?? 'N/A'); ?>
      </div>
      <div style="color:var(--muted); font-size:0.8rem;">
        ₹<?php echo number_format($expense_data[0]['total_amount'] ?? 0); ?>
        (<?php echo $total_spent > 0 ? round(($expense_data[0]['total_amount']/$total_spent)*100) : 0; ?>%)
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Entries</div>
      <div class="stat-value"><?php echo array_sum(array_column($expense_data, 'count')); ?></div>
    </div>
  </div>

  <!-- Charts Grid -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem;">

    <!-- Pie Chart -->
    <div class="card">
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">🥧 Spending by Category</h3>
      <div style="position:relative; max-width:350px; margin:0 auto;">
        <canvas id="pieChart"></canvas>
      </div>
    </div>

    <!-- Bar Chart -->
    <div class="card">
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">📊 Category Comparison</h3>
      <div style="position:relative;">
        <canvas id="barChart"></canvas>
      </div>
    </div>

  </div>

  <!-- Monthly Trend -->
  <?php if(!empty($monthly_data)): ?>
  <div class="card" style="margin-bottom:2.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">📈 Monthly Spending Trend</h3>
    <div style="position:relative; height:300px;">
      <canvas id="lineChart"></canvas>
    </div>
  </div>
  <?php endif; ?>

  <!-- Trip Budget vs Spent -->
  <?php if(!empty($trip_spending)): ?>
  <div class="card" style="margin-bottom:2.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">💰 Budget vs Actual per Trip</h3>
    <div style="position:relative; height:300px;">
      <canvas id="tripChart"></canvas>
    </div>
  </div>
  <?php endif; ?>

  <!-- Detailed Table -->
  <div class="card">
    <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">📋 Expense Breakdown</h3>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>Category</th><th>Entries</th><th>Total Amount</th><th>% of Total</th><th>Visualization</th></tr>
        </thead>
        <tbody>
          <?php foreach($expense_data as $i => $e):
            $pct = $total_spent > 0 ? ($e['total_amount']/$total_spent)*100 : 0;
            $color = $colors[$i % count($colors)];
          ?>
          <tr>
            <td style="font-weight:700;">
              <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo $color; ?>; margin-right:0.5rem;"></span>
              <?php echo htmlspecialchars($e['expense_type']); ?>
            </td>
            <td style="color:var(--muted);"><?php echo $e['count']; ?></td>
            <td style="color:var(--gold); font-weight:700;">₹<?php echo number_format($e['total_amount']); ?></td>
            <td><?php echo round($pct, 1); ?>%</td>
            <td style="width:200px;">
              <div style="background:rgba(255,255,255,0.08); border-radius:50px; height:8px; overflow:hidden;">
                <div style="height:100%; width:<?php echo $pct; ?>%; background:<?php echo $color; ?>; border-radius:50px; transition:width 0.5s;"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
const colors = <?php echo json_encode(array_slice($colors, 0, count($expense_data))); ?>;

// Pie Chart
new Chart(document.getElementById('pieChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode(array_column($expense_data, 'expense_type')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_map('floatval', array_column($expense_data, 'total_amount'))); ?>,
      backgroundColor: colors,
      borderColor: '#1a1a2e',
      borderWidth: 2,
      hoverBorderColor: '#ffffff',
      hoverBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom', labels: { color: '#8a8fa8', padding: 15, font: { family: 'Lato' } } }
    },
    cutout: '55%'
  }
});

// Bar Chart
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode(array_column($expense_data, 'expense_type')); ?>,
    datasets: [{
      label: 'Amount (₹)',
      data: <?php echo json_encode(array_map('floatval', array_column($expense_data, 'total_amount'))); ?>,
      backgroundColor: colors.map(c => c + '99'),
      borderColor: colors,
      borderWidth: 1,
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8a8fa8' } },
      x: { grid: { display: false }, ticks: { color: '#8a8fa8' } }
    },
    plugins: { legend: { display: false } }
  }
});

<?php if(!empty($monthly_data)): ?>
// Line Chart
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
    datasets: [{
      label: 'Monthly Spending (₹)',
      data: <?php echo json_encode(array_map('floatval', array_column($monthly_data, 'total'))); ?>,
      borderColor: '#c9a84c',
      backgroundColor: 'rgba(201,168,76,0.1)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#c9a84c',
      pointBorderColor: '#1a1a2e',
      pointBorderWidth: 2,
      pointRadius: 5
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8a8fa8' } },
      x: { grid: { display: false }, ticks: { color: '#8a8fa8' } }
    },
    plugins: { legend: { labels: { color: '#8a8fa8' } } }
  }
});
<?php endif; ?>

<?php if(!empty($trip_spending)): ?>
// Trip Comparison Chart
new Chart(document.getElementById('tripChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode(array_column($trip_spending, 'trip_name')); ?>,
    datasets: [
      {
        label: 'Budget (₹)',
        data: <?php echo json_encode(array_map('floatval', array_column($trip_spending, 'total_budget'))); ?>,
        backgroundColor: 'rgba(100,160,255,0.3)',
        borderColor: '#64a0ff',
        borderWidth: 1,
        borderRadius: 6
      },
      {
        label: 'Spent (₹)',
        data: <?php echo json_encode(array_map('floatval', array_column($trip_spending, 'total_expense'))); ?>,
        backgroundColor: 'rgba(201,168,76,0.3)',
        borderColor: '#c9a84c',
        borderWidth: 1,
        borderRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8a8fa8' } },
      x: { grid: { display: false }, ticks: { color: '#8a8fa8' } }
    },
    plugins: { legend: { labels: { color: '#8a8fa8', padding: 15 } } }
  }
});
<?php endif; ?>
</script>

</body>
</html>
