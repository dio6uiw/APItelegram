<?php
// Configurações
$telegramToken = "7678474536:AAGXosVM8zjMf7uk_f8krfbLZcx5q6g2c_o";
$openaiApiKey = "sk-m7thyu3eFUb_GApSzr-0kt8s-hziVg4NBdUurnhp3VT3BlbkFJb15J_2dprHssKKDpY3joyEeox8QXn2bd4H6fT4wm0A";

// Função para registrar logs
function logMessage($message) {
    file_put_contents('bot.log', date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

// Recebe o conteúdo da requisição
$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);
logMessage("Recebido update: " . json_encode($update));

// Verifica se há uma mensagem
if(isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $message = trim($update["message"]["text"]);

    logMessage("Mensagem recebida de chat_id $chatId: $message");

    // Envia a mensagem para a API do ChatGPT
    $response = getChatGPTResponse($message, $openaiApiKey);
    logMessage("Resposta do ChatGPT: $response");

    // Envia a resposta de volta para o Telegram
    $sendResult = sendMessage($telegramToken, $chatId, $response);
    logMessage("Resultado do sendMessage: " . json_encode($sendResult));
}

/**
 * Função para enviar mensagem para o Telegram
 */
function sendMessage($token, $chat_id, $text) {
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = array(
        'chat_id' => $chat_id,
        'text' => $text
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if(curl_errno($ch)){
        logMessage("cURL erro: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($result, true);
}

/**
 * Função para obter resposta da API do ChatGPT
 */
function getChatGPTResponse($prompt, $apiKey) {
    $url = "https://api.openai.com/v1/chat/completions";
    $data = array(
        "model" => "gpt-4", // ou outro modelo disponível
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => 150,
        "temperature" => 0.7
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if(curl_errno($ch)){
        logMessage("cURL erro: " . curl_error($ch));
        curl_close($ch);
        return "Desculpe, ocorreu um erro ao processar sua solicitação.";
    }
    curl_close($ch);

    $response = json_decode($result, true);
    if (isset($response['choices'][0]['message']['content'])) {
        return trim($response['choices'][0]['message']['content']);
    } else {
        logMessage("Resposta inválida da API do OpenAI: " . $result);
        return "Desculpe, não consegui obter uma resposta.";
    }
}
?>
