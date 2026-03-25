<?php
include "config.php";

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];

$success = false; $error = '';

// Handle new journal entry
if(isset($_POST['create_entry'])){
    $trip_id     = (int)$_POST['trip_id'];
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $content     = mysqli_real_escape_string($conn, $_POST['content']);
    $mood        = mysqli_real_escape_string($conn, $_POST['mood']);
    $is_public   = isset($_POST['is_public']) ? 1 : 0;
    $highlights  = mysqli_real_escape_string($conn, $_POST['highlights']);

    // Verify trip belongs to user
    $trip_check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT trip_id FROM trip WHERE trip_id='$trip_id' AND user_id='$user_id'"));
    if(!$trip_check){
        $error = "Invalid trip selected.";
    } else {
        $share_token = $is_public ? bin2hex(random_bytes(8)) : null;
        $token_sql = $share_token ? "'$share_token'" : "NULL";
        $q = mysqli_query($conn,
            "INSERT INTO journal_entry(user_id, trip_id, title, content, mood, highlights, is_public, share_token, entry_date)
             VALUES('$user_id', '$trip_id', '$title', '$content', '$mood', '$highlights', '$is_public', $token_sql, NOW())");
        if($q) $success = true;
        else $error = "Failed to save entry.";
    }
}

// Toggle public status
if(isset($_GET['toggle_public'])){
    $entry_id = (int)$_GET['toggle_public'];
    $entry = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM journal_entry WHERE entry_id='$entry_id' AND user_id='$user_id'"));
    if($entry){
        $new_public = $entry['is_public'] ? 0 : 1;
        $new_token = $new_public && !$entry['share_token'] ? "'".bin2hex(random_bytes(8))."'" : ($entry['share_token'] ? "'".$entry['share_token']."'" : "NULL");
        mysqli_query($conn, "UPDATE journal_entry SET is_public='$new_public', share_token=$new_token WHERE entry_id='$entry_id'");
    }
    header("Location: journal.php"); exit();
}

// Delete entry
if(isset($_GET['delete'])){
    $entry_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM journal_entry WHERE entry_id='$entry_id' AND user_id='$user_id'");
    header("Location: journal.php"); exit();
}

// User's trips for dropdown
$user_trips = mysqli_fetch_all(mysqli_query($conn,
    "SELECT trip_id, trip_name, trip_status FROM trip
     WHERE user_id='$user_id' ORDER BY trip_id DESC"), MYSQLI_ASSOC);

// User's journal entries
$entries = mysqli_fetch_all(mysqli_query($conn,
    "SELECT je.*, t.trip_name, t.start_date, t.end_date
     FROM journal_entry je
     JOIN trip t ON je.trip_id = t.trip_id
     WHERE je.user_id = '$user_id'
     ORDER BY je.entry_date DESC"), MYSQLI_ASSOC);

$moods = ['😊' => 'Amazing', '😌' => 'Relaxing', '🤩' => 'Mind-Blowing', '😅' => 'Adventurous', '🥰' => 'Romantic', '😐' => 'Mixed Feelings'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Travel Journal — Voyager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>

<div class="section">
  <div class="section-title">📓 My Travel Journal</div>
  <p class="section-subtitle">Document your travel stories and share them with the world</p>

  <?php if($success): ?>
  <div class="alert alert-success" style="margin-bottom:2rem;">✓ Journal entry saved!</div>
  <?php endif; ?>
  <?php if($error): ?>
  <div class="alert alert-error" style="margin-bottom:2rem;">⚠ <?php echo $error; ?></div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:2rem; align-items:start;">

    <!-- Write New Entry -->
    <div class="card" style="position:sticky; top:100px;">
      <h3 style="font-size:1.1rem; margin-bottom:1.5rem;">✍️ New Entry</h3>
      <?php if(empty($user_trips)): ?>
        <div style="text-align:center; padding:1.5rem 0; color:var(--muted);">
          <div style="font-size:2rem; margin-bottom:0.5rem;">🧳</div>
          <p>Book a trip first to start journaling!</p>
          <a href="packages.php" class="btn btn-primary" style="margin-top:1rem; font-size:0.85rem;">Browse Packages</a>
        </div>
      <?php else: ?>
      <form method="POST">
        <div class="form-group">
          <label>Trip</label>
          <select name="trip_id" required style="width:100%; padding:0.85rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:6px; color:var(--white); font-family:'Lato',sans-serif; font-size:0.95rem; outline:none;">
            <option value="" style="background:var(--deep);">-- Select Trip --</option>
            <?php foreach($user_trips as $t): ?>
            <option value="<?php echo $t['trip_id']; ?>" style="background:var(--deep);">
              <?php echo htmlspecialchars($t['trip_name']); ?> (<?php echo $t['trip_status']; ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" placeholder="e.g. Sunset at Goa Beach" required>
        </div>

        <div class="form-group">
          <label>Your Story</label>
          <textarea name="content" rows="5" placeholder="What made this trip special?" required></textarea>
        </div>

        <div class="form-group">
          <label>Highlights (comma-separated)</label>
          <input type="text" name="highlights" placeholder="e.g. Scuba diving, Street food, Temples">
        </div>

        <div class="form-group">
          <label>Mood</label>
          <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <?php foreach($moods as $emoji => $label): ?>
            <label style="cursor:pointer; text-align:center;">
              <input type="radio" name="mood" value="<?php echo $emoji; ?>" style="display:none;" <?php if($emoji === '😊') echo 'checked'; ?>>
              <div class="mood-option" style="font-size:1.5rem; padding:0.5rem 0.75rem; border-radius:8px; border:1px solid var(--border); transition:all 0.2s;">
                <?php echo $emoji; ?>
              </div>
              <div style="font-size:0.7rem; color:var(--muted); margin-top:0.2rem;"><?php echo $label; ?></div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group" style="display:flex; align-items:center; gap:0.75rem;">
          <input type="checkbox" name="is_public" id="isPublic" style="width:auto; cursor:pointer;">
          <label for="isPublic" style="text-transform:none; letter-spacing:0; font-size:0.9rem; cursor:pointer; color:var(--gold);">
            🌐 Make this entry public (shareable link)
          </label>
        </div>

        <button type="submit" name="create_entry" class="btn btn-primary" style="width:100%;">Save Journal Entry</button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Journal Entries -->
    <div>
      <h3 style="font-size:1.2rem; margin-bottom:1.5rem;">📖 My Entries (<?php echo count($entries); ?>)</h3>
      <?php if(empty($entries)): ?>
        <div class="card" style="text-align:center; padding:3rem; color:var(--muted);">
          <div style="font-size:2.5rem; margin-bottom:1rem;">📓</div>
          No journal entries yet — start writing about your adventures!
        </div>
      <?php else: ?>
        <?php foreach($entries as $e): ?>
        <div class="card" style="margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
            <div>
              <div style="font-size:1.5rem; display:inline; margin-right:0.5rem;"><?php echo $e['mood']; ?></div>
              <h3 style="display:inline; font-size:1.1rem;"><?php echo htmlspecialchars($e['title']); ?></h3>
              <div style="color:var(--gold); font-size:0.85rem; margin-top:0.3rem;">
                🧳 <?php echo htmlspecialchars($e['trip_name']); ?>
                <span style="color:var(--muted);">| 📅 <?php echo date('M d, Y', strtotime($e['entry_date'])); ?></span>
              </div>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
              <?php if($e['is_public']): ?>
              <span style="background:rgba(100,200,120,0.15); color:#64c878; padding:0.2rem 0.6rem; border-radius:50px; font-size:0.7rem; font-weight:700;">PUBLIC</span>
              <?php else: ?>
              <span style="background:rgba(255,255,255,0.08); color:var(--muted); padding:0.2rem 0.6rem; border-radius:50px; font-size:0.7rem; font-weight:700;">PRIVATE</span>
              <?php endif; ?>
            </div>
          </div>

          <p style="color:rgba(255,255,255,0.75); font-size:0.9rem; line-height:1.7; margin-bottom:1rem;">
            <?php echo nl2br(htmlspecialchars($e['content'])); ?>
          </p>

          <?php if(!empty($e['highlights'])): ?>
          <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1rem;">
            <?php foreach(explode(',', $e['highlights']) as $tag): ?>
            <span style="background:rgba(201,168,76,0.12); color:var(--gold); padding:0.2rem 0.7rem; border-radius:50px; font-size:0.78rem;">
              #<?php echo trim(htmlspecialchars($tag)); ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
            <a href="journal.php?toggle_public=<?php echo $e['entry_id']; ?>" class="btn btn-outline" style="font-size:0.73rem; padding:0.4rem 0.8rem;">
              <?php echo $e['is_public'] ? '🔒 Make Private' : '🌐 Make Public'; ?>
            </a>
            <?php if($e['is_public'] && $e['share_token']): ?>
            <span style="font-size:0.8rem; color:var(--muted);">
              Share: <a href="journal_view.php?token=<?php echo $e['share_token']; ?>" style="color:var(--gold); word-break:break-all;">
                journal_view.php?token=<?php echo $e['share_token']; ?>
              </a>
            </span>
            <?php endif; ?>
            <a href="journal.php?delete=<?php echo $e['entry_id']; ?>" class="btn btn-danger" style="font-size:0.73rem; padding:0.4rem 0.8rem;" onclick="return confirm('Delete this entry?');">🗑 Delete</a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<style>
input[type="radio"]:checked + .mood-option {
  border-color: var(--gold) !important;
  background: rgba(201,168,76,0.15) !important;
  transform: scale(1.1);
}
.mood-option:hover {
  border-color: rgba(201,168,76,0.5) !important;
  transform: scale(1.05);
}
</style>

</body>
</html>
