<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!isset($input['messages']) || !is_array($input['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Messages parameter is required']);
    exit();
}

$video_title = isset($input['video_title']) ? substr(trim($input['video_title']), 0, 500) : "General Context";
$video_desc = isset($input['video_desc']) && $input['video_desc'] !== null ? substr(trim($input['video_desc']), 0, 2000) : "No description available.";
$subject_cat = isset($input['subject']) ? substr(trim($input['subject']), 0, 200) : "BCA Subjects";

$api_key = "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";
$model = "meta-llama/llama-3.2-3b-instruct";

$system_prompt = "You are a helpful AI tutor for students.
Explain everything in simple language with examples.
Keep answers short and clear.
Help students understand topics easily.

Current Topic:
Title: " . $video_title . "
Description: " . $video_desc . "
Category: " . $subject_cat;

$messages = [
    [
        "role" => "system",
        "content" => $system_prompt
    ]
];

// Combine history safely
foreach ($input['messages'] as $msg) {
    if (isset($msg['role']) && isset($msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
}

$data = [
    "model" => $model,
    "messages" => $messages
];

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if(curl_errno($ch)){
    http_response_code(500);
    echo json_encode(['error' => curl_error($ch)]);
} else {
    http_response_code($httpcode);
    echo $response;
}
curl_close($ch);
?>
