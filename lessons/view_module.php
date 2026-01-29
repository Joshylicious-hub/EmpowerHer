<?php
session_start();
include '../db_connect.php';

// Get module ID from URL
$module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch module
$stmt = $conn->prepare("SELECT * FROM user_modules WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $module_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$module = $result->fetch_assoc();

if (!$module) {
  die("<h2 class='text-center text-red-500 mt-10'>Module not found or you don’t have access.</h2>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($module['title']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Include same CSS from lesson1.php */
    html { scroll-behavior: smooth; }
    .slide-left, .slide-bottom { opacity:0; transform:translateX(-50px); transition:all .8s ease-out; }
    .slide-bottom { transform:translateY(50px); }
    .slide-left.active, .slide-bottom.active { opacity:1; transform:translate(0,0); }
    .correct { background:#d1fae5; border-color:#10b981; }
    .incorrect { background:#fee2e2; border-color:#ef4444; }

    /* --- Dark mode variables --- */
    :root { --bg-main:#fff; --text-main:#000; --card-bg:#f9f9f9; --card-border:#e0e0e0; }
    body.dark { --bg-main:#121212; --text-main:#fff; --card-bg:#1e1e1e; --card-border:#333; background:#121212; color:#fff; }
    body { background:linear-gradient(to bottom right,#fff5f7,#ffe6f0,#ffe9f2); color:var(--text-main); transition:.3s; }
    .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:1rem; padding:1.5rem; transition:.3s; }

    /* Hero section */
    .hero { background:linear-gradient(rgba(255,245,247,.7),rgba(255,230,240,.7)),url('<?= $module['image_url'] ?: '../images/lesson-bg.jpg' ?>');
      background-size:cover; background-position:center; }
    body.dark .hero { background:linear-gradient(rgba(0,0,0,.7),rgba(30,30,30,.7)),url('<?= $module['image_url'] ?: '../images/lesson-bg.jpg' ?>'); }

    /* Highlighted text when reading */
    .reading-highlight { background:linear-gradient(90deg,#ffe0ec 0%,#ffd1dc 100%); border-radius:6px; padding:4px; }
    .dark .reading-highlight { background:linear-gradient(90deg,#ff80b5 0%,#ffb6c9 100%); }
  </style>
</head>

<body class="overflow-y-auto">

<!-- Hero Section -->
<div class="hero relative w-full h-[60vh] flex items-center justify-center">
  <!-- Utility Toolbar -->
  <div id="utilityPanel" class="fixed top-4 right-4 flex items-center gap-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border border-pink-300 dark:border-gray-700 shadow-lg rounded-full px-4 py-2 z-50">
    <button id="darkModeToggle" class="w-8 h-8 rounded-full bg-pink-500 text-white">🌙</button>
    <div class="w-[1px] h-6 bg-pink-200 dark:bg-gray-600"></div>
    <button id="readLessonBtn" class="bg-pink-500 text-white rounded-full px-3 py-1 text-xs">Read</button>
  </div>

  <!-- Back Button -->
  <a href="../modules.php" class="absolute top-4 left-4 bg-pink-500 text-white px-4 py-2 rounded-full">← Back</a>
  
  <div class="text-center max-w-2xl px-6">
    <h1 class="text-5xl font-bold mb-4 slide-left"><?= htmlspecialchars($module['title']) ?></h1>
    <p class="text-lg opacity-90 mb-6 slide-bottom"><?= htmlspecialchars($module['description']) ?></p>
    <a href="#content" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-3 rounded-full">Start Lesson</a>
  </div>
</div>

<!-- Lesson Content -->
<div id="content" class="max-w-5xl mx-auto px-6 py-12 space-y-10 text-gray-700 dark:text-gray-300">
  <?= nl2br($module['content']) ?>
  
  <?php if (!empty($module['video_url'])): ?>
  <div class="max-w-4xl mx-auto text-center">
    <iframe class="w-full rounded-2xl shadow-lg aspect-video"
            src="<?= htmlspecialchars($module['video_url']) ?>"
            title="Lesson video" allowfullscreen></iframe>
  </div>
  <?php endif; ?>
</div>

<!-- CTA -->
<div class="cta-section w-full bg-pink-500 py-12 mt-12 text-center text-white">
  <h2 class="text-3xl font-bold mb-4">You’re Amazing 💖</h2>
  <p class="text-lg mb-6 opacity-90">Keep learning and loving your child! You can now unlock your next module.</p>
  <a href="../modules.php" class="bg-white text-pink-600 px-6 py-3 rounded-full text-lg font-semibold">Back to Modules</a>
</div>

<script>
const observer = new IntersectionObserver(entries=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting) entry.target.classList.add('active');
  });
},{threshold:0.2});
document.querySelectorAll('.slide-left,.slide-bottom').forEach(el=>observer.observe(el));

// Dark Mode
const darkModeToggle = document.getElementById('darkModeToggle');
darkModeToggle.addEventListener('click',()=>{
  document.body.classList.toggle('dark');
  darkModeToggle.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
});

// Simple AI Reader
const readLessonBtn = document.getElementById('readLessonBtn');
let speech = new SpeechSynthesisUtterance();
readLessonBtn.onclick = ()=>{
  if(speechSynthesis.speaking){speechSynthesis.cancel();readLessonBtn.textContent='Read';return;}
  const text = document.getElementById('content').innerText;
  speech.text = text;
  speech.lang = 'en-US';
  speech.rate = 1;
  speechSynthesis.speak(speech);
  readLessonBtn.textContent='Stop';
  speech.onend=()=>readLessonBtn.textContent='Read';
};
</script>

</body>
</html>
