<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$module_name = $_POST['module_name'];
$topic = $_POST['topic'];
$user_id = $_SESSION['user_id'];

// Connect to DB
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");

// 🧠 AI generate lessons
require 'vendor/autoload.php'; // composer require openai-php/client
$client = OpenAI::client('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');

$response = $client->chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'You are an educational assistant that creates learning modules/title. '],
        ['role' => 'user', 'content' => "Generate 1 concise lesson titles for a module about: $topic"]
    ],
]);

$generated_text = $response['choices'][0]['message']['content'] ?? 'Lesson 1: Introduction';
$lessons = preg_split("/\r\n|\n|\r|,|;/", $generated_text);
$lessons = array_filter(array_map('trim', $lessons));

// Take only the first lesson
$lessons = [reset($lessons) ?: "Lesson 1: Introduction"];

if (empty($lessons)) {
    $lessons = ["Lesson 1: Introduction", "Lesson 2: Main Topic"];
}

// Save to database
$lessons_json = json_encode($lessons);
$stmt = $conn->prepare("INSERT INTO custom_modules (user_id, module_name, lessons) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $module_name, $lessons_json);
$stmt->execute();

header("Location: modules.php");
exit();
?>
