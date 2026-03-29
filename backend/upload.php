<?php

// 🔐 CORS (viktig hvis du tester fra annet sted)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// ❌ Ikke vis errors i production
error_reporting(0);
ini_set('display_errors', 0);

// 🔑 Hent API key fra environment (Render)
$apiKey = getenv("OPENAI_API_KEY");

if (!$apiKey) {
    echo json_encode(["error" => "API key not set"]);
    exit;
}

// 📁 Sjekk om fil finnes
if (!isset($_FILES['audio'])) {
    echo json_encode(["error" => "No audio file uploaded"]);
    exit;
}

// 📁 Lag uploads mappe hvis ikke finnes
$uploadDir = __DIR__ . "/uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 📁 Lagre fil
$fileName = uniqid() . ".webm";
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
    echo json_encode(["error" => "Failed to save file"]);
    exit;
}

// 🎤 Send til OpenAI Whisper
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey
    ],
    CURLOPT_POSTFIELDS => [
        "file" => new CURLFile($filePath),
        "model" => "whisper-1",
        "language" => "no"
    ]
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["error" => curl_error($curl)]);
    curl_close($curl);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);
$text = $result["text"] ?? null;

if (!$text) {
    echo json_encode([
        "error" => "Transcription failed",
        "response" => $result
    ]);
    exit;
}

// 💾 (VALGFRITT) Database – kun hvis du faktisk bruker det
/*
require 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn->connect_error) {
    $stmt = $conn->prepare("INSERT INTO transcriptions (filename, text, user_id) VALUES (?, ?, ?)");
    $user_id = 1;
    $stmt->bind_param("ssi", $fileName, $text, $user_id);
    $stmt->execute();
}
*/

// ✅ Returner resultat
echo json_encode([
    "text" => $text
]);