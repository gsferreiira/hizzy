<?php
// Habilita CORS para requisições AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Recebe os dados do formulário
$data = json_decode(file_get_contents('php://input'), true);

// Se não conseguir ler como JSON, tenta ler como POST normal
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Valida e sanitiza os dados
$nome = filter_var($data['nome'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$telefone = filter_var($data['telefone'] ?? '', FILTER_SANITIZE_STRING);
$mensagem = filter_var($data['mensagem'] ?? '', FILTER_SANITIZE_STRING);

// Validações
$errors = [];

if (empty($nome)) {
    $errors[] = 'Por favor, informe seu nome';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Por favor, informe um e-mail válido';
}

if (empty($mensagem)) {
    $errors[] = 'Por favor, escreva sua mensagem';
}

// Se houver erros, retorna
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit;
}

// Configurações do e-mail
$para = "hizzy.com.br@gmail.com"; // SEU E-MAIL AQUI
$assunto = "Novo contato do site Hizzy - " . $nome;

// Corpo do e-mail em HTML
$corpo = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>$assunto</title>
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { color: #0A2463; border-bottom: 2px solid #3E92CC; padding-bottom: 10px; }
        .dados { background-color: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .dados p { margin: 10px 0; }
        .label { font-weight: 600; color: #0A2463; display: inline-block; width: 100px; }
        .footer { margin-top: 20px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Novo contato recebido</h2>
            <p>Você recebeu uma nova mensagem através do site Hizzy</p>
        </div>
        
        <div class='dados'>
            <p><span class='label'>Nome:</span> $nome</p>
            <p><span class='label'>E-mail:</span> $email</p>
            <p><span class='label'>Telefone:</span> " . ($telefone ? $telefone : 'Não informado') . "</p>
            <p><span class='label'>Mensagem:</span></p>
            <p>" . nl2br($mensagem) . "</p>
        </div>
        
        <div class='footer'>
            <p>Este e-mail foi enviado automaticamente pelo formulário de contato do site Hizzy.</p>
            <p>Não responda diretamente este e-mail. Para responder, utilize o e-mail do remetente acima.</p>
        </div>
    </div>
</body>
</html>
";

// Cabeçalhos do e-mail
$headers = "From: $nome <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Tenta enviar o e-mail
try {
    $envio = mail($para, $assunto, $corpo, $headers);
    
    if ($envio) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso! Entraremos em contato em breve.'
        ]);
    } else {
        throw new Exception('O servidor não conseguiu enviar o e-mail.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
    ]);
}
?>