<?php include "config.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voyager — Travel Management</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "nav.php"; ?>

<div class="hero">
  <div class="hero-label">✦ Premium Travel Experiences</div>
  <h1>Your World,<br><em>Beautifully</em> Planned.</h1>
  <p>Discover curated travel packages, manage your trips, and track every experience — all in one place.</p>
  <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; animation: fadeUp 0.6s 0.3s ease both;">
    <a href="packages.php" class="btn btn-primary">Explore Packages</a>
    <a href="register.php" class="btn btn-outline">Get Started</a>
  </div>
</div>

<div class="section">
  <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap:1.5rem; max-width:1100px; margin:0 auto;">

    <div class="card" style="text-align:center; animation: fadeUp 0.5s 0.1s ease both;">
      <div style="font-size:2.5rem; margin-bottom:1rem;">🌍</div>
      <h3>Curated Packages</h3>
      <p style="color:var(--muted); font-size:0.9rem;">Handpicked travel packages for every kind of adventurer.</p>
    </div>

    <div class="card" style="text-align:center; animation: fadeUp 0.5s 0.2s ease both;">
      <div style="font-size:2.5rem; margin-bottom:1rem;">📊</div>
      <h3>Expense Tracker</h3>
      <p style="color:var(--muted); font-size:0.9rem;">Log and monitor your travel spending in real time.</p>
    </div>

    <div class="card" style="text-align:center; animation: fadeUp 0.5s 0.3s ease both;">
      <div style="font-size:2.5rem; margin-bottom:1rem;">⭐</div>
      <h3>Destination Reviews</h3>
      <p style="color:var(--muted); font-size:0.9rem;">Share your experiences and discover hidden gems.</p>
    </div>

  </div>
</div>

</body>
</html>
