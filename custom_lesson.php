<?php
set_time_limit(300);
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get module ID
$moduleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($moduleId === 0) die("Invalid module ID.");

// Fetch custom module
$stmt = $conn->prepare("SELECT * FROM custom_modules WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $moduleId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$module = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$module) die("Module not found or you do not have permission to view it.");

$lessons = json_decode($module['lessons'], true);
$firstLesson = $lessons[0] ?? "Lesson content not available";

require 'vendor/autoload.php'; // Load OpenAI SDK once
$client = OpenAI::client('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'); // Replace with your key

// -------------------------
// 1. AI Image Generator
// -------------------------
function generateAIImage($prompt) {
    $apiKey = "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"; // Replace with your OpenAI key
    $url = "https://api.openai.com/v1/images/generations";

    $data = [
        "prompt" => $prompt,
        "n" => 1,
        "size" => "256x256"
    ];

    $options = [
        "http" => [
            "header" => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer $apiKey\r\n",
            "method" => "POST",
            "content" => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);

    return $response['data'][0]['url'] ?? '';
}

// Generate hero image
$heroPrompt = !empty($firstLesson) ? $firstLesson : "A parent bonding with their baby, warm and caring";
$heroImageUrl = generateAIImage($heroPrompt); // store the AI-generated image URL

// -------------------------
// 2. AI Lesson Content
// -------------------------
$sectionsToGenerate = [
    'hero' => $firstLesson, 
    'why_bonding' => $lessons[0] ?? '',
    'bonding_way_1' => $lessons[0] ?? '',
    'bonding_way_2' => $lessons[0] ?? '',
    'bonding_way_3' => $lessons[0] ?? '',
    'activities' => $lessons[0] ?? '',
    'dos_donts' => $lessons[0] ?? '',
    'video_section' => $lessons[0] ?? '',
    'reflection' => $lessons[0] ?? '',
    'questions_and_answers_1' => $lessons[0] ?? '',
    'questions_and_answers_2' => $lessons[0] ?? '',
];

$generatedContent = [];

foreach ($sectionsToGenerate as $key => $promptContent) {

    $prompt = "You are an parenting expert educator. Based on this content:\n\"$promptContent\"\n
Generate a JSON with:
{
  \"title\": \"Short, clear lesson title\",
  \"subtitle\": \"Brief explanation or introduction\",
  \"header1\": \"Lesson header\",
  \"content1\": \"Lesson discussion\",
  \"subcontent1\": \"Supporting discussion\",
  \"step1\": \"Step name\",
  \"dosanddonts1\": \"do's and don't name\",
  \"donts1\": \"wrong answer for don't to do of dosanddonts1\",
  \"donts2\": \"wrong answer for don't to do of dosanddonts1\",
  \"donts3\": \"wrong answer for don't to do of dosanddonts1\",
  \"stepcontent1\": \"Explanation for step1\",
  \"stepcontent2\": \"Explanation for step1\",
  \"stepcontent3\": \"Explanation for step1\",
  \"stepcontent4\": \"Explanation for step1\"
}
Ensure the output is STRICTLY valid JSON — no markdown, no comments.";

    try {
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an parenting expert educator. Make the output warm, clear, and engaging.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 400
        ]);

        $content = $response->choices[0]->message->content ?? '{}';

        // Parse JSON output for all sections
        $generatedContent[$key] = json_decode($content, true) ?: ['title' => 'Heading not available', 'content' => 'Content not available'];
        

    } catch (Exception $e) {
        $generatedContent[$key] = ['title' => 'Error', 'content' => $e->getMessage()];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" sizes="34x34" href="../images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="..images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="..images/logo7.png">
  <title>EmpowerHer - Personalized Learning Modules</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html {
      scroll-behavior: smooth;
    }
    .slide-left, .slide-bottom {
      opacity: 0;
      transform: translateX(-50px);
      transition: all 0.8s ease-out;
    }
    .slide-bottom {
      transform: translateY(50px);
    }
    .slide-left.active, .slide-bottom.active {
      opacity: 1;
      transform: translateX(0) translateY(0);
    }
    .correct { background-color: #d1fae5; border-color: #10b981; }
    .incorrect { background-color: #fee2e2; border-color: #ef4444; }



    
    /* Pang dark mode toggle */
    #theme-toggle {
      position: fixed;
      top: 1rem;
      right: 1rem;
      padding: 0.5rem 1rem;
      border-radius: 9999px;
      cursor: pointer;
      background: var(--card-bg);
      color: var(--text-main);
      border: 1px solid var(--card-border);
      transition: background 0.3s, color 0.3s;
      z-index: 50;
    }

     /* Dark mode overrides */
    body.dark {
      --bg-main: #121212;
      --text-main: #ffffff;
      --card-bg: #1e1e1e;
      --card-border: #333333;
    }

    :root {
      --bg-main: #ffffff;
      --text-main: #000000;
      --card-bg: #f9f9f9;
      --card-border: #e0e0e0;
    }

    body {
      background: var(--bg-main);
      color: var(--text-main);
      transition: background 0.3s, color 0.3s;
    }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 1rem;
      padding: 1.5rem;
      transition: background 0.3s, border 0.3s;
    }

    /* Default hero (light mode) */
.hero {
  background: linear-gradient(rgba(255,245,247,0.7), rgba(255,230,240,0.7)),
              url('https://images.unsplash.com/photo-1616400619175-5f19c92f0a9b');
  background-size: cover;
  background-position: center;
}

/* Dark mode hero */
body.dark .hero {
  background: linear-gradient(rgba(0,0,0,0.7), rgba(30,30,30,0.7)),
              url('https://images.unsplash.com/photo-1616400619175-5f19c92f0a9b');
  background-size: cover;
  background-position: center;
}


/* Dark mode for lesson content */
body.dark #content {
  background: #121212; /* dark background */
  color: #ffffff; /* white text */
}

body.dark #content h2,
body.dark #content h3 {
  color: #ffffff;
}

body.dark #content p,
body.dark #content li {
  color: #e0e0e0; /* softer gray for readability */
}

/* Dark mode for white cards */
body.dark #content .bg-white {
  background: #1e1e1e !important;
  border-color: #333333 !important;
}
/* Light mode body (default) */
body {
  background: linear-gradient(to bottom right, #fff5f7, #ffe6f0, #ffe9f2);
  color: var(--text-main);
  transition: background 0.3s, color 0.3s;
}

/* Dark mode body */
body.dark {
  background: #121212;
  color: #ffffff;
}

/* Dark mode CTA section */
body.dark .cta-section {
  background: #1e1e1e;   /* dark background */
  color: #ffffff;        /* white text */
}

body.dark .cta-section h2,
body.dark .cta-section p {
  color: #ffffff;
}

body.dark .cta-section a {
  background: #ffffff;   /* keep button light */
  color: #1e1e1e;        /* dark text for contrast */
}
/*hanggang dito lang yung dark mode*/



  /* CHATBOT DESIGN TO */
 /* Fade-in animation */
@keyframes fade-in {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fade-in 0.4s ease-out;
}

/* Tooltip visibility */
#chat-head:hover + #chat-tooltip,
#chat-tooltip:hover {
  opacity: 1;
  transform: translateX(0);
}

/* Chat bubbles */
.user-msg {
  align-self: flex-end;
  background: linear-gradient(135deg, #f472b6, #ec4899);
  color: white;
  border-radius: 18px 18px 0 18px;
  padding: 10px 14px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  max-width: 80%;
  word-wrap: break-word;
}

.bot-msg {
  align-self: flex-start;
  background: rgba(255,255,255,0.8);
  color: #333;
  border-radius: 18px 18px 18px 0;
  padding: 10px 14px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.05);
  max-width: 80%;
  word-wrap: break-word;
}

.dark .bot-msg {
  background: rgba(255,255,255,0.1);
  color: #fff;
}

/* Scrollbar aesthetic */
#chat-messages::-webkit-scrollbar {
  width: 6px;
}
#chat-messages::-webkit-scrollbar-thumb {
  background-color: #f472b6;
  border-radius: 10px;
}

 /* Smooth animation for new messages */
  .bot-msg, .user-msg {
    animation: fadeIn 0.3s ease-in-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Scrollbar aesthetics */
  #chat-messages::-webkit-scrollbar {
    width: 6px;
  }
  #chat-messages::-webkit-scrollbar-thumb {
    background: rgba(236, 72, 153, 0.5);
    border-radius: 10px;
  }
/*hanggang dito lang*/
  










/*TYPING TO NG AI*/

.typing-dots::after {
  content: " .";
  animation: dots 1.5s steps(3, end) infinite;
}
@keyframes dots {
  0%, 20% { content: " "; }
  40% { content: " ."; }
  60% { content: " .."; }
  80%, 100% { content: " ..."; }
}



/*ai reader to*/
#aiReader {
  font-family: 'Inter', sans-serif;
}
body.dark #aiReader {
  background: rgba(30, 30, 30, 0.85);
  color: white;
  border-color: #444;
}
#aiReader select {
  cursor: pointer;
}


/* Hide voice name text visually in closed state */
#voiceSelect {
  color: transparent !important;
  text-shadow: 0 0 0 transparent;
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
  appearance: none;
  position: relative;
  z-index: 10;
}

/* When dropdown is opened (focused), restore visible text */
#voiceSelect:focus,
#voiceSelect:active {
  color: #333 !important;
  text-shadow: none !important;
  background: #fff !important;
  border-radius: 6px;
  padding: 2px 4px;
  width: auto;
  min-width: 120px;
  z-index: 50;
}

/* Dark mode fix */
.dark #voiceSelect:focus,
.dark #voiceSelect:active {
  background: #222 !important;
  color: #fff !important;
}

/* Dropdown container */
#utilityPanel .relative {
  width: 1.8rem;
  height: 1.8rem;
  position: relative;
}

/* Center arrow */
#utilityPanel .relative div {
  font-size: 0.8rem;
  pointer-events: none;
}

/* Mobile tweaks */
@media (max-width: 640px) {
  #utilityPanel {
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.35rem 0.6rem;
    gap: 0.25rem;
  }

  #utilityPanel .relative {
    width: 1.6rem;
    height: 1.6rem;
  }

  #utilityPanel .relative div {
    font-size: 0.7rem;
  }
}

.reading-highlight {
  background: linear-gradient(90deg, #ffe0ec 0%, #ffd1dc 100%);
  border-radius: 6px;
  transition: background 0.3s ease, transform 0.3s ease;
  padding: 4px;
}

.dark .reading-highlight {
  background: linear-gradient(90deg, #ff80b5 0%, #ffb6c9 100%);
}








/* 🌸 EmpowerHer Loading Overlay */
#pageLoadingOverlay {
  display: none;
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 182, 193, 0.35); /* soft pink transparent */
  backdrop-filter: blur(6px) saturate(140%);
  z-index: 9999;
  overflow: hidden;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  font-family: 'Poppins', sans-serif;
  text-align: center;
  transition: opacity 0.4s ease;
}

/* 🌸 Floating Bubbles */
.floating-shapes .bubble {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    rgba(255, 110, 140, 0.9) 0%,
    rgba(255, 65, 100, 0.8) 40%,
    rgba(255, 180, 190, 0.5) 80%);
  box-shadow: 0 0 25px rgba(255, 90, 120, 0.7);
  animation: floatUp 10s infinite ease-in-out, shimmer 3s infinite ease-in-out;
  opacity: 0.9;
  filter: brightness(1.1);
  backdrop-filter: blur(2px);
}

/* 🌸 Bubble sizes and positions */
.floating-shapes .bubble:nth-child(1) {
  width: 180px; height: 180px; left: 10%; bottom: -20%; animation-delay: 0s;
}
.floating-shapes .bubble:nth-child(2) {
  width: 140px; height: 140px; left: 60%; bottom: -25%; animation-delay: 2s;
}
.floating-shapes .bubble:nth-child(3) {
  width: 200px; height: 200px; left: 80%; bottom: -15%; animation-delay: 4s;
}
.floating-shapes .bubble:nth-child(4) {
  width: 120px; height: 120px; left: 30%; bottom: -10%; animation-delay: 1s;
}
.floating-shapes .bubble:nth-child(5) {
  width: 100px; height: 100px; left: 75%; bottom: -5%; animation-delay: 3s;
}

/* 🌸 Bubble float animation */
@keyframes floatUp {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0.9;
  }
  50% {
    transform: translateY(-40vh) scale(1.05);
    opacity: 1;
  }
  100% {
    transform: translateY(-90vh) scale(1);
    opacity: 0.4;
  }
}

/* 🌸 Bubble shimmer effect */
@keyframes shimmer {
  0%, 100% {
    box-shadow: 0 0 25px rgba(255, 120, 150, 0.6), inset -8px -8px 20px rgba(255, 200, 210, 0.2);
    filter: brightness(1.1);
  }
  50% {
    box-shadow: 0 0 40px rgba(255, 130, 160, 0.9), inset 5px 5px 25px rgba(255, 255, 255, 0.4);
    filter: brightness(1.3);
  }
}

/* 🌸 Center Content */
.loading-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: fadeInUp 0.8s ease;
}

/* 🌸 Spinner */
.spinner {
  border: 6px solid rgba(255, 192, 203, 0.3);
  border-top: 6px solid #ff4e6d;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  animation: spin 1.2s linear infinite, glowPulse 2s ease-in-out infinite;
  box-shadow: 0 0 25px rgba(255, 107, 129, 0.4);
}

/* 🌸 Typing Text */
.typing-text {
  margin-top: 25px;
  color: #ff4e6d;
  font-size: 1.3rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  border-right: 3px solid #ff4e6d;
  animation: blinkCursor 0.8s infinite;
  text-shadow: 0 0 8px rgba(255, 107, 129, 0.4);
  background: rgba(255, 255, 255, 0.25);
  padding: 12px 25px;
  border-radius: 25px;
  backdrop-filter: blur(8px);
}

/* 🌸 Animations */
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes glowPulse {
  0%, 100% { box-shadow: 0 0 20px rgba(255, 107, 129, 0.3); }
  50% { box-shadow: 0 0 40px rgba(255, 107, 129, 0.6); }
}
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(30px); }
  100% { opacity: 1; transform: translateY(0); }
}
@keyframes blinkCursor {
  0%, 100% { border-color: transparent; }
  50% { border-color: #ff4e6d; }
}

/* 🌙 Dark Mode */
body.dark-mode #pageLoadingOverlay {
  background: rgba(25, 25, 25, 0.65);
  backdrop-filter: blur(8px);
}
body.dark-mode .typing-text {
  color: #ff9aad;
  border-right-color: #ff9aad;
}
body.dark-mode .spinner {
  border: 6px solid #333;
  border-top: 6px solid #ff9aad;
}

  </style>
</head>




<!-- eto pinalitan ko -->
<body class="overflow-y-auto">
    
 <!-- 🌸 EmpowerHer AI Loading Overlay -->
    <div id="pageLoadingOverlay">
      <div class="floating-shapes">
        <span class="bubble"></span>
            <span class="bubble"></span>
             <span class="bubble"></span>
                 <span class="bubble"></span>
                     <span class="bubble"></span>
                        </div>
    
                      <div class="loading-content">
                    <div class="spinner"></div>
                <div id="typingText" class="typing-text"></div>
            </div>
         </div>



<!-- eto din-->
<!-- Hero Section -->
<div class="hero relative w-full h-[60vh] flex items-center justify-center">
      <!-- Dark/Light Toggle -->
         <!-- Utility Toolbar (Dark Mode + AI Reader) -->
             <div id="utilityPanel" 
                class="fixed top-4 right-4 flex items-center gap-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md 
                 border border-pink-300 dark:border-gray-700 shadow-lg rounded-full px-4 py-2 z-50 text-sm 
                    transition-all duration-300 hover:shadow-xl">

                    <!-- Dark Mode -->
                 <button id="darkModeToggle" 
             class="flex items-center justify-center w-8 h-8 rounded-full bg-pink-500 hover:bg-pink-600 text-white shadow transition">
     🌙 </button>

  <!-- Divider -->
  <div class="w-[1px] h-6 bg-pink-200 dark:bg-gray-600"></div>

  <!-- Read Button -->
  <button id="readLessonBtn" 
          class="bg-pink-500 hover:bg-pink-600 text-white rounded-full px-3 py-1 text-xs font-medium transition">
     Read
  </button>

  <!-- Voice Dropdown -->
  <div class="relative">
    <select id="voiceSelect" 
            class="text-gray-700 dark:text-gray-200 bg-transparent border-none focus:ring-0 outline-none text-xs 
                   appearance-none cursor-pointer w-6 h-8">
      <option>Loading...</option>
    </select>
    <!-- Dropdown arrow -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none text-pink-500">
      ▼
    </div>
  </div>
</div>

  <!-- Back Button -->
  <a href="../modules.php" 
     class="absolute top-4 left-4 flex items-center gap-2 bg-gradient-to-r from-pink-400 to-purple-400 text-white px-4 py-2 rounded-full shadow-lg hover:scale-105 transition">
    <!-- Arrow Left Icon -->
    <svg xmlns="http://www.w3.org/2000/svg" 
         fill="none" 
         viewBox="0 0 24 24" 
         stroke-width="2" 
         stroke="currentColor" 
         class="w-5 h-5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back
  </a>
  <div class="relative z-10 text-center max-w-2xl px-6 top-6">
    <h1 class="text-5xl font-bold mb-4 slide-left">
    <?= htmlspecialchars($generatedContent['hero']['title'] ?? 'Lesson Title') ?>
</h1>

<p class="text-lg opacity-90 mb-6 slide-bottom">
    <?= htmlspecialchars($generatedContent['hero']['subtitle'] ?? 'Lesson description not available') ?>
</p>
    <a href="#content" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-3 rounded-full text-lg inline-block slide-bottom">Start Lesson</a>
  </div>
</div>
<!-- hanggang dito yung hero -->




<!-- wala ako pinalitan dito -->
  <!-- Lesson Content -->
  

  <div id="content" class="max-w-6xl mx-auto px-6 py-12 space-y-20">

    <!-- Section 1: Why Bonding Matters -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
      <div>
       <h2 class="text-3xl font-semibold text-gray-800 mb-4 slide-left">
    <?= htmlspecialchars($generatedContent['why_bonding']['header1'] ?? 'Heading not available') ?>
</h2>

<p class="text-gray-600 leading-relaxed mb-6 slide-bottom">
    <?= nl2br(htmlspecialchars($generatedContent['why_bonding']['content1'] ?? 'Content not available')) ?>
</p>

       <p class="text-gray-600 leading-relaxed slide-bottom">
    <?= nl2br(htmlspecialchars($generatedContent['why_bonding']['subcontent1'] ?? 'content not available')) ?>
</p>
      </div>
      <div class="flex justify-center slide-left">
       <img src="<?= htmlspecialchars($heroImageUrl) ?>" alt="Parent holding baby" class="rounded-2xl shadow-lg w-full h-72 object-cover">
      </div>
    </div>

    <!-- Section 2: Simple Ways to Bond -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
      <div class="bg-white p-6 rounded-2xl shadow-lg slide-bottom">
        <h3 class="text-xl font-semibold text-pink-500 mb-2">
    <?= htmlspecialchars($generatedContent['bonding_way_1']['header1'] ?? 'Heading not available') ?>
</h3>
<p class="text-gray-600 leading-relaxed">
    <?= nl2br(htmlspecialchars($generatedContent['bonding_way_1']['subcontent1'] ?? 'Content not available')) ?>
</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-lg slide-bottom">
        <h3 class="text-xl font-semibold text-pink-500 mb-2"> <?= htmlspecialchars($generatedContent['bonding_way_2']['header1'] ?? 'Heading not available') ?></h3>
        <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($generatedContent['bonding_way_2']['subcontent1'] ?? 'Content not available')) ?></p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-lg slide-bottom">
        <h3 class="text-xl font-semibold text-pink-500 mb-2"><?= htmlspecialchars($generatedContent['bonding_way_3']['subtitle'] ?? 'Heading not available') ?></h3>
        <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($generatedContent['bonding_way_3']['stepcontent1'] ?? 'Content not available')) ?></p>
      </div>
    </div>

    <!-- Section 3: Everyday Bonding Activities -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
      <div class="flex justify-center slide-left">
        <img src="<?= htmlspecialchars($heroImageUrl) ?>" alt="Parent playing with baby" class="rounded-2xl shadow-lg w-full h-72 object-cover">
      </div>
      <div>
        <h2 class="text-3xl font-semibold text-gray-800 mb-4 slide-left"><?= htmlspecialchars($generatedContent['activities']['header1'] ?? 'Heading not available') ?></h2>
        <ul class="space-y-3 text-gray-700 slide-bottom">
          <li class="flex items-center"><span class="text-pink-500 mr-2">✔</span> <?= nl2br(htmlspecialchars($generatedContent['activities']['content1'] ?? 'Content not available')) ?></li>
          <li class="flex items-center"><span class="text-pink-500 mr-2">✔</span> <?= nl2br(htmlspecialchars($generatedContent['activities']['stepcontent3'] ?? 'Content not available')) ?></li>
          <li class="flex items-center"><span class="text-pink-500 mr-2">✔</span> <?= nl2br(htmlspecialchars($generatedContent['activities']['subcontent1'] ?? 'Content not available')) ?></li>
          <li class="flex items-center"><span class="text-pink-500 mr-2">✔</span> <?= nl2br(htmlspecialchars($generatedContent['activities']['stepcontent4'] ?? 'Content not available')) ?></li>
        </ul>
      </div>
    </div>

        <!-- Section 4: Do's and Don'ts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
      <div>
        <h2 class="text-3xl font-semibold text-gray-800 mb-4 slide-left"><?= htmlspecialchars($generatedContent['dos_donts']['dosanddonts1'] ?? 'Heading not available') ?></h2>
        <ul class="space-y-3 text-gray-700 slide-bottom">
          <li><span class="text-green-600 font-bold">✔ Do:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['stepcontent1'] ?? 'Content not available')) ?></li>
          <li><span class="text-green-600 font-bold">✔ Do:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['stepcontent2'] ?? 'Content not available')) ?></li>
          <li><span class="text-green-600 font-bold">✔ Do:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['stepcontent3'] ?? 'Content not available')) ?></li>
          <li><span class="text-red-500 font-bold">✘ Don’t:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['donts1'] ?? 'Content not available')) ?></li>
          <li><span class="text-red-500 font-bold">✘ Don’t:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['donts2'] ?? 'Content not available')) ?></li>
          <li><span class="text-red-500 font-bold">✘ Don’t:</span> <?= nl2br(htmlspecialchars($generatedContent['dos_donts']['donts3'] ?? 'Content not available')) ?></li>
        </ul>
      </div>
      <div class="flex justify-center slide-left">
        <img src="<?= htmlspecialchars($heroImageUrl) ?>" alt="Mother bonding with baby" class="rounded-2xl shadow-lg w-full h-72 object-cover">
      </div>
    </div>


    <!-- Insert after Section 3: Responding Effectively -->

<!-- Section 4: Video Demo -->
<div class="max-w-4xl mx-auto slide-bottom">
  <h2 class="text-3xl font-semibold text-gray-800 mb-6 text-center slide-left">
    <?= htmlspecialchars($generatedContent['video_section']['title'] ?? 'Heading not available') ?>
  </h2>
  <iframe class="w-full rounded-2xl shadow-lg aspect-video"
          src="https://www.youtube.com/embed/7AiF7Id3yAQ?autoplay=1&mute=1&vq=hd1080"
          title="Safe Skin to Skin with your Baby after Birth"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen></iframe>
  <p class="text-gray-600 mt-4 text-center slide-bottom">
   <?= nl2br(htmlspecialchars($generatedContent['video_section']['subtitle'] ?? 'Content not available')) ?>
  </p>
</div>




    <!-- Section 5: Reflection Questions -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-8 space-y-6 slide-bottom">
      <h2 class="text-3xl font-semibold text-gray-800 mb-4 text-center slide-left">Reflect & Apply</h2>
      <p class="text-gray-600 leading-relaxed slide-bottom">
        <?= nl2br(htmlspecialchars($generatedContent['reflection']['subtitle'] ?? 'Content not available')) ?>
      </p>
      <ul class="space-y-3 text-gray-700 slide-bottom">
        <li class="flex items-start"><span class="text-pink-500 mr-2 mt-1">✔</span> <?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_1']['stepcontent1'] ?? 'Content not available')) ?></li>
        <li class="flex items-start"><span class="text-pink-500 mr-2 mt-1">✔</span> <?= nl2br(htmlspecialchars($generatedContent['reflection']['stepcontent2'] ?? 'Content not available')) ?></li>
        <li class="flex items-start"><span class="text-pink-500 mr-2 mt-1">✔</span> <?= nl2br(htmlspecialchars($generatedContent['reflection']['stepcontent3'] ?? 'Content not available')) ?></li>
      </ul>
    </div>

    <!-- Section 6: Interactive Quiz -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-8 space-y-6 slide-bottom">
      <h2 class="text-3xl font-semibold text-gray-800 mb-4 text-center slide-left">Test Your Knowledge</h2>

      <!-- Question 1 -->
      <div class="space-y-4">
        <p class="text-gray-700 font-medium">1. <?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_1']['header1'] ?? 'Content not available')) ?></p>
        <div class="space-y-2">
          <button onclick="checkAnswer(this,true)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_1']['stepcontent1'] ?? 'Content not available')) ?></button>
          <button onclick="checkAnswer(this,false)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_1']['donts1'] ?? 'Content not available')) ?></button>
          <button onclick="checkAnswer(this,false)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_1']['donts2'] ?? 'Content not available')) ?></button>
        </div>
      </div>

      <!-- Question 2 -->
      <div class="space-y-4">
        <p class="text-gray-700 font-medium">2. <?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_2']['title'] ?? 'Content not available')) ?></p>
        <div class="space-y-2">
          <button onclick="checkAnswer(this,true)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_2']['stepcontent3'] ?? 'Content not available')) ?></button>
          <button onclick="checkAnswer(this,false)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_2']['donts3'] ?? 'Content not available')) ?></button>
          <button onclick="checkAnswer(this,false)" class="w-full border rounded-lg px-4 py-2 text-left hover:bg-pink-50"><?= nl2br(htmlspecialchars($generatedContent['questions_and_answers_2']['donts1'] ?? 'Content not available')) ?></button>
        </div>
      </div>
    </div>

  </div>

  <!-- hanggang dito yun wala ako pinalitan -->

  


<!-- 🌸 Floating Chat Head -->
<div class="fixed bottom-6 right-6 z-50 flex items-center gap-2">
  <!-- Tooltip -->
  <span id="chat-tooltip" 
        class="opacity-0 translate-x-2 transition-all duration-300 bg-gray-800 text-white text-sm px-3 py-1 rounded-full shadow-lg whitespace-nowrap">
    Ask EmpowerHer Assistant 💕
  </span>

  <!-- Chat Head -->
  <div id="chat-head" 
       class="relative w-14 h-14 rounded-full bg-gradient-to-r from-pink-400 to-pink-600 shadow-lg flex flex-col items-center justify-center cursor-pointer hover:scale-110 transition-transform text-white animate-pulse">
    <i class="fa-solid fa-robot text-xl"></i>
    <span class="text-[10px] font-semibold mt-0.5">Ask</span>

    <!-- AI Badge -->
    <span class="absolute -top-1 -right-1 bg-white text-pink-600 text-[10px] font-bold px-1 py-0.5 rounded-full shadow">
      AI
    </span>
  </div>
</div>

<!-- 🌷 Chat Window -->
<div id="chat-window" 
     class="hidden fixed bottom-24 right-6 w-72 sm:w-80 h-[400px] bg-white/60 dark:bg-gray-900/60 backdrop-blur-2xl border border-pink-200/50 dark:border-gray-700/50 rounded-2xl shadow-[0_6px_25px_rgba(31,38,135,0.25)] z-50 flex flex-col overflow-hidden animate-fade-in">

  <!-- Header -->
  <div class="flex justify-between items-center px-3 py-2 bg-gradient-to-r from-pink-400/90 to-pink-600/90 text-white backdrop-blur-md shadow">
    <span class="font-semibold text-base">EmpowerHer 💬</span>
    <button id="close-chat" class="text-white font-bold text-base hover:scale-110 transition">✖</button>
  </div>

  <!-- Messages -->
  <div id="chat-messages" 
       class="flex-1 p-3 space-y-3 overflow-y-auto text-[13px] bg-gradient-to-b from-pink-50/50 to-white/60 dark:from-gray-800/60 dark:to-gray-900/60 scroll-smooth">
    
    <!-- Example Bot Message -->
    <div class="flex items-start gap-2">
      <img src="https://cdn-icons-png.flaticon.com/512/4712/4712109.png" alt="AI Avatar" class="w-7 h-7 rounded-full shadow-md">
      <div class="bot-msg bg-white/80 dark:bg-gray-800/70 text-gray-800 dark:text-white px-3 py-2 rounded-2xl shadow-md w-fit max-w-[75%]">
        Hi Mama! 💖 What’s on your mind?
      </div>
    </div>

  </div>

  <!-- Input -->
  <div class="p-2 border-t border-pink-200/50 dark:border-gray-700/50 flex gap-2 bg-white/70 dark:bg-gray-800/70 backdrop-blur-md">
    <input id="chat-input" type="text" placeholder="Type your question..." 
           class="flex-1 border border-pink-200/50 dark:border-gray-600/50 rounded-full px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-pink-400 dark:bg-gray-900/60 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition">
    <button id="send-btn" 
            class="bg-gradient-to-r from-pink-400 to-pink-600 text-white px-3 py-1.5 rounded-full shadow-md hover:opacity-90 active:scale-95 transition text-sm">
      ➤
    </button>
  </div>
</div>
<!-- hanggang dito lang yung ai -->







<!-- etong cta pinalitan ko -->
  <!-- CTA Section with Locked Button -->
<div class="cta-section w-full bg-pink-500 py-12 mt-12 text-center text-white slide-bottom">
  <h2 class="text-3xl font-bold mb-4 slide-left">Great Work!💖</h2>
  <p class="text-lg mb-6 opacity-90 slide-bottom">
    You may now go back to modules.
  </p>
  <a id="lesson2Btn" href="modules.php" 
     class="bg-white text-pink-600 px-6 py-3 rounded-full text-lg font-semibold slide-bottom pointer-events-none opacity-50"> Go to modules</a>
</div>
<!-- hanggang dito -->



  <!-- Scripts -->
  <script>
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if(entry.isIntersecting){
          entry.target.classList.add('active');
        } else {
          entry.target.classList.remove('active');
        }
      });
    }, { threshold: 0.2 });

    document.querySelectorAll('.slide-left, .slide-bottom').forEach(el => observer.observe(el));

    function checkAnswer(button, isCorrect){
      if(isCorrect){
        button.classList.add('correct');
        button.classList.remove('incorrect');
        alert("Correct! Great job, Mama!");
      } else {
        button.classList.add('incorrect');
        button.classList.remove('correct');
        alert("Oops! Try again.");
      }
    }

    // Make sure the "Go to Lesson 2" button has this ID
  const lesson2Btn = document.getElementById('lesson2Btn');

  window.addEventListener('scroll', () => {
    const scrollTop = window.scrollY || window.pageYOffset;
    const viewportHeight = window.innerHeight;
    const fullHeight = document.documentElement.scrollHeight;

    // Trigger when user reaches bottom
    if(scrollTop + viewportHeight >= fullHeight - 5) {
      // Enable Lesson 2 button visually
      if(lesson2Btn){
        lesson2Btn.classList.remove('opacity-50', 'pointer-events-none');
      }

      // Send AJAX request to mark Lesson 1 complete
      fetch('../mark_lesson_completed.php', {  // note: ../ because lesson1 is inside lessons/
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({lesson_id: 1})
      })
      .then(res => res.json())
      .then(data => {
        if(data.success){
          console.log("Lesson 1 marked complete");
        }
      });
    }
  });



  // === HERO TOOLBAR: DARK MODE + AI READER ===
const darkModeToggle = document.getElementById('darkModeToggle');
const readLessonBtn = document.getElementById('readLessonBtn');
const voiceSelect = document.getElementById('voiceSelect');
const lessonContent = document.getElementById('content');

// ---- DARK MODE ----
darkModeToggle.addEventListener('click', () => {
  document.body.classList.toggle('dark');
  const isDark = document.body.classList.contains('dark');
  darkModeToggle.textContent = isDark ? '☀️' : '🌙';
  localStorage.setItem('darkMode', isDark);
});

if (localStorage.getItem('darkMode') === 'true') {
  document.body.classList.add('dark');
  darkModeToggle.textContent = '☀️';
}

// ---- AI READER ----
let voices = [];
let isSpeaking = false;
let currentUtterance = null;
let stopRequested = false;
let elementsToRead = [];
let currentIndex = 0;

// Load voices
function loadVoices() {
  voices = window.speechSynthesis.getVoices();
  voiceSelect.innerHTML = voices
    .filter(v => v.lang.startsWith('en'))
    .map(v => `<option value="${v.name}">${v.name.replace('Google', '')}</option>`)
    .join('');
}
window.speechSynthesis.onvoiceschanged = loadVoices;
loadVoices();

// ---- Highlight helpers ----
function highlightElement(index) {
  elementsToRead.forEach((el, i) => {
    el.classList.toggle('reading-highlight', i === index);
  });

  if (index >= 0 && elementsToRead[index]) {
    elementsToRead[index].scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }
}

function resetHighlights() {
  elementsToRead.forEach(el => el.classList.remove('reading-highlight'));
}

// ---- Read / Stop ----
readLessonBtn.addEventListener('click', () => {
  if (!('speechSynthesis' in window)) {
    alert("Your browser doesn't support text-to-speech.");
    return;
  }

  if (isSpeaking) {
    stopRequested = true;
    window.speechSynthesis.cancel();
    resetHighlights();
    readLessonBtn.textContent = 'Read';
    isSpeaking = false;
    return;
  }

  // ✅ Collect readable text from both hero and content sections
  const heroSection = document.querySelector('.hero');
  const contentSection = document.getElementById('content');

  elementsToRead = [];

  [heroSection, contentSection].forEach(section => {
    if (section) {
      section.querySelectorAll('h1, h2, h3, h4, h5, h6, p, li, button').forEach(el => {
        // ✅ Skip dark mode & read buttons
        if (
          el === darkModeToggle ||
          el === readLessonBtn ||
          el.closest('#darkModeToggle') ||
          el.closest('#readLessonBtn')
        ) {
          return;
        }

        // ✅ Only include visible text
        if (el.innerText.trim().length > 0) {
          elementsToRead.push(el);
        }
      });
    }
  });

  if (!elementsToRead.length) {
    alert('No readable text found.');
    return;
  }

  stopRequested = false;
  isSpeaking = true;
  currentIndex = 0;
  readLessonBtn.textContent = 'Stop';
  readNextElement();
});

// ---- Read elements one by one ----
function readNextElement() {
  if (stopRequested) {
    resetHighlights();
    readLessonBtn.textContent = ' Read';
    isSpeaking = false;
    return;
  }

  // ✅ When finished reading all elements
  if (currentIndex >= elementsToRead.length) {
    resetHighlights();
    readLessonBtn.textContent = 'Read';
    isSpeaking = false;

    // ✨ Final message after reading
    const finalMessage = "Thank you for listening 💖 You may now proceed to the next lesson!";
    const closingUtterance = new SpeechSynthesisUtterance(finalMessage);
    const selectedVoice = voices.find(v => v.name === voiceSelect.value);
    if (selectedVoice) closingUtterance.voice = selectedVoice;
    closingUtterance.rate = 1;
    closingUtterance.pitch = 1;
    window.speechSynthesis.speak(closingUtterance);

    // Optional: display message visually too
    const doneMsg = document.createElement('div');
    doneMsg.textContent = finalMessage;
    doneMsg.style.textAlign = "center";
    doneMsg.style.fontWeight = "600";
    doneMsg.style.marginTop = "10px";
    doneMsg.style.color = "var(--color-primary, #e91e63)";
    lessonContent.appendChild(doneMsg);

    return;
  }

  const text = elementsToRead[currentIndex].innerText.trim();
  if (!text) {
    currentIndex++;
    readNextElement();
    return;
  }

  highlightElement(currentIndex);

  currentUtterance = new SpeechSynthesisUtterance(text);
  const selectedVoice = voices.find(v => v.name === voiceSelect.value);
  if (selectedVoice) currentUtterance.voice = selectedVoice;
  currentUtterance.rate = 1;
  currentUtterance.pitch = 1;

  currentUtterance.onend = () => {
    if (stopRequested) {
      resetHighlights();
      readLessonBtn.textContent = 'Read';
      isSpeaking = false;
      return;
    }
    highlightElement(-1);
    currentIndex++;
    readNextElement();
  };

  currentUtterance.onerror = () => {
    currentIndex++;
    readNextElement();
  };

  window.speechSynthesis.speak(currentUtterance);
}

// ---- Highlight style ----
const style = document.createElement('style');
style.textContent = `
.reading-highlight {
  background-color: rgba(255, 182, 193, 0.4);
  transition: background-color 0.3s ease;
  border-radius: 8px;
}
body.dark .reading-highlight {
  background-color: rgba(255, 105, 180, 0.25);
}
`;
document.head.appendChild(style);





//para sa ai chatbot to
    const chatHead = document.getElementById("chat-head");
  const chatWindow = document.getElementById("chat-window");
  const closeChat = document.getElementById("close-chat");
  const sendBtn = document.getElementById("send-btn");
  const chatInput = document.getElementById("chat-input");
  const chatMessages = document.getElementById("chat-messages");

  // Toggle chat window
  chatHead.addEventListener("click", () => {
    chatWindow.classList.toggle("hidden");
    chatInput.focus();
  });

  closeChat.addEventListener("click", () => {
    chatWindow.classList.add("hidden");
  });

  // Append message bubbles
  function appendMessage(text, sender="user") {
    const msg = document.createElement("div");
    msg.classList.add("px-3", "py-2", "rounded-2xl", "max-w-[75%]", "shadow-sm");
    if (sender === "user") {
      msg.classList.add("bg-gradient-to-r", "from-pink-400", "to-pink-600", "text-white", "ml-auto");
    } else {
      msg.classList.add("bg-gray-100", "text-gray-900", "dark:bg-gray-700", "dark:text-white");
    }
    msg.textContent = text;
    chatMessages.appendChild(msg);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // Send message
  function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;
    appendMessage(message, "user");
    chatInput.value = "";

    fetch("../chatbot_api.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ history: [{ role: "user", content: message }] })
    })
    .then(res => res.json())
    .then(data => {
      appendMessage(data.reply, "bot");
    })
    .catch(() => {
      appendMessage("⚠️ Error: Could not reach AI.", "bot");
    });
  }

  sendBtn.addEventListener("click", sendMessage);
  chatInput.addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
  });












  // TYPING TO NG AI
function appendMessage(text, sender="user", isTemp=false) {
  const msg = document.createElement("div");
  msg.classList.add(
    "px-3", "py-2", "rounded-2xl", "max-w-[75%]", "shadow-sm", "animate-fade-in"
  );

  if (sender === "user") {
    msg.classList.add("bg-gradient-to-r", "from-pink-400", "to-pink-600", "text-white", "ml-auto");
  } else {
    msg.classList.add("bg-gray-100", "text-gray-900", "dark:bg-gray-700", "dark:text-white");
    if (isTemp) {
      msg.classList.add("italic", "opacity-70");
    }
  }

  msg.textContent = text;
  chatMessages.appendChild(msg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return msg; // return node (so we can remove/replace it)
}

// Send message
function sendMessage() {
  const message = chatInput.value.trim();
  if (!message) return;
  appendMessage(message, "user");
  chatInput.value = "";

  const typingBubble = appendMessage("Nova is typing", "bot", true);
typingBubble.classList.add("typing-dots");


  fetch("../chatbot_api.php", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ history: [{ role: "user", content: message }] })
  })
  .then(res => res.json())
  .then(data => {
    typingBubble.remove(); // remove "Typing…" bubble
    appendMessage(data.reply || "⚠️ No response", "bot");
  })
  .catch(() => {
    typingBubble.remove();
    appendMessage("⚠️ Error: Could not reach AI.", "bot");
  });
}


//loading

document.addEventListener("DOMContentLoaded", function() {
  const overlay = document.getElementById("pageLoadingOverlay");
  const typingText = document.getElementById("typingText");

  const messages = [
    "🌸 EmpowerHer AI is preparing your personalized module...",
    "💖 Understanding your learning style...",
    "✨ Designing something meaningful for you...",
    "🌷 Almost done — your EmpowerHer journey begins soon!"
  ];

  let msgIndex = 0;
  let charIndex = 0;
  let isDeleting = false;

  function typeEffect() {
    const currentMsg = messages[msgIndex];
    if (!isDeleting) {
      typingText.textContent = currentMsg.substring(0, charIndex++);
      if (charIndex <= currentMsg.length) {
        setTimeout(typeEffect, 50);
      } else {
        setTimeout(() => (isDeleting = true, typeEffect()), 1200);
      }
    } else {
      typingText.textContent = currentMsg.substring(0, charIndex--);
      if (charIndex >= 0) {
        setTimeout(typeEffect, 25);
      } else {
        isDeleting = false;
        msgIndex = (msgIndex + 1) % messages.length;
        setTimeout(typeEffect, 400);
      }
    }
  }

  // Show overlay on navigation to certain pages
  document.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function() {
      const href = this.getAttribute("href");
      if (href && (href.includes("custom_lesson.php") || href.includes("modules.php"))) {
        overlay.style.display = "flex";
        typeEffect();
      }
    });
  });

  window.addEventListener("beforeunload", () => {
    overlay.style.display = "flex";
    typeEffect();
  });
});


  </script>

</body>
</html>
