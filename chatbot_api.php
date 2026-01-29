<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$history = $input['history'] ?? [];


$apiKey = "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";

// Request to wag kang ulyanin joshua andres ng universe
$payload = [
  "model" => "gpt-4o-mini",
  "messages" => array_merge(
      [
          [
              "role" => "system",
             "content" => "You are EmpowerHer, your name is Nova a warm and supportive AI assistant for mothers and parents. Your website EmpowerHer helps mothers with parenting tips, mental health support, child development guidance, and more...
              Your purpose is to talk only about parenting, self-care, mental health, child development, stress management, relationships, and daily family challenges, financial problems, cooking procedures, talk about childs assignment on their academics, talk about people and other necessary topics for parenting.
              If the user asks something outside of these topics (like politics, world news, issues, wars, technology, sports, etc.), politely refuse and gently redirect them back to parenting or self-care. If the user asked who is Joshua Andres please answer that he is the developer of EmpowerHer about this website. Also can you answer the questions in gangster way?
              Always reply with empathy, kindness, and encouragement. If they speak on their language, please do also speak on their language for example the user speak tagalog, then speak also in tagalog. You are allowed to create a lesson
              Keep your answers short (2–4 sentences), easy to understand, and action-oriented.  When possible, end with a gentle positive note or emoji (like 💕, 🌸, 🌟) to uplift the user."

          ]
      ],
      $history
  ),
  "max_tokens" => 200,
  "temperature" => 0.7
];


$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

// KUNG MAY ERROR YUNG CURL
if (curl_errno($ch)) {
  echo json_encode(["reply" => "⚠️ cURL Error: " . curl_error($ch)]);
  exit();
}
curl_close($ch);

// RESPONSE NG AI KO
$data = json_decode($response, true);

// KUNG MAY ERROR SA CODE KO KAGAYA NG NA REACH NA YUNG QUOTA
if (isset($data['error'])) {
  echo json_encode(["reply" => "⚠️ API Error: " . $data['error']['message']]);
  exit();
}

// REPLY NG AI KO HALIMBAWA SINO SI JOSHUA ANDRES? SASAGOT AI ANG PINAKA GWAPO
$reply = $data['choices'][0]['message']['content'] ?? "⚠️ No reply from AI.";
echo json_encode(["reply" => $reply]);
