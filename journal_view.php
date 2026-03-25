<?php
include "config.php";

$token = mysqli_real_escape_string($conn, $_GET['token'] ?? '');
if(empty($token)){ header("Location: index.php"); exit(); }

$entry = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT je.*, u.full_name, t.trip_name, t.start_date, t.end_date,
            t.total_budget, t.trip_status
     FROM journal_entry je
     JOIN users u ON je.user_id = u.user_id
     JOIN trip t ON je.trip_id = t.trip_id
     WHERE je.share_token = '$token' AND je.is_public = 1"));

if(!$entry){
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Not Found — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div style="text-align:center; padding:8rem 2rem;">
  <div style="font-size:4rem; margin-bottom:1rem;">🔒</div>
  <h1 style="font-family:'Playfair Display',serif; font-size:2rem; margin-bottom:1rem;">Entry Not Found</h1>
  <p style="color:var(--muted); margin-bottom:2rem;">This journal entry doesn't exist or has been made private.</p>
  <a href="index.php" class="btn btn-primary">Go Home</a>
</div>
</body>
</html>
<?php exit(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($entry['title']); ?> — Voyager Journal</title>
  <link rel="stylesheet" href="style.css">
  <meta name="description" content="<?php echo htmlspecialchars(substr($entry['content'], 0, 155)); ?>">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section" style="max-width:750px; margin:0 auto;">

  <!-- Header -->
  <div style="text-align:center; margin-bottom:3rem; animation: fadeUp 0.5s ease both;">
    <div style="font-size:3.5rem; margin-bottom:1rem;"><?php echo $entry['mood']; ?></div>
    <h1 style="font-family:'Playfair Display',serif; font-size:2.5rem; margin-bottom:0.5rem;">
      <?php echo htmlspecialchars($entry['title']); ?>
    </h1>
    <div style="color:var(--muted); font-size:0.9rem;">
      by <strong style="color:var(--gold);"><?php echo htmlspecialchars($entry['full_name']); ?></strong>
      · <?php echo date('F d, Y', strtotime($entry['entry_date'])); ?>
    </div>
  </div>

  <!-- Trip Info Card -->
  <div class="card" style="margin-bottom:2.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; animation: fadeUp 0.5s 0.1s ease both;">
    <div>
      <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:2px; margin-bottom:0.3rem;">Trip</div>
      <div style="font-size:1.1rem; font-weight:700; color:var(--gold);">🧳 <?php echo htmlspecialchars($entry['trip_name']); ?></div>
    </div>
    <div style="display:flex; gap:2rem; color:var(--muted); font-size:0.85rem;">
      <span>📅 <?php echo $entry['start_date']; ?> → <?php echo $entry['end_date']; ?></span>
      <span>💰 ₹<?php echo number_format($entry['total_budget']); ?></span>
    </div>
  </div>

  <!-- Content -->
  <div class="card" style="animation: fadeUp 0.5s 0.2s ease both;">
    <div style="font-size:1.05rem; line-height:2; color:rgba(255,255,255,0.85);">
      <?php echo nl2br(htmlspecialchars($entry['content'])); ?>
    </div>

    <?php if(!empty($entry['highlights'])): ?>
    <div style="border-top:1px solid var(--border); margin-top:2rem; padding-top:1.5rem;">
      <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:2px; margin-bottom:0.75rem;">Highlights</div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <?php foreach(explode(',', $entry['highlights']) as $tag): ?>
        <span style="background:rgba(201,168,76,0.12); color:var(--gold); padding:0.3rem 1rem; border-radius:50px; font-size:0.85rem; border:1px solid rgba(201,168,76,0.25);">
          #<?php echo trim(htmlspecialchars($tag)); ?>
        </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div style="text-align:center; margin-top:3rem; color:var(--muted); font-size:0.85rem;">
    <div style="margin-bottom:1rem;">Shared via <strong style="color:var(--gold);">✦ Voyager</strong> Travel Journal</div>
    <a href="index.php" class="btn btn-outline" style="font-size:0.8rem;">Explore Voyager →</a>
  </div>
</div>

</body>
</html>
