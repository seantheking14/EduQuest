<?php
require_once __DIR__ . '/config/smtp.php';
require_once __DIR__ . '/utils/SMTPClient.php';

$result = sendEmailViaSMTP(
    'memajustine3@gmail.com',
    'Test User',
    'EduQuest Test Email',
    '<h1>Test email from EduQuest!</h1>'
);

echo json_encode($result);
