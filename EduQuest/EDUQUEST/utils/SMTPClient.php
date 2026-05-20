<?php
/**
 * Simple SMTP Email Sender
 * No external dependencies - uses PHP sockets directly
 */

function sendEmailViaSMTP(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $textBody = null
): array {
    try {
        $config = getSMTPConfig();

        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $fromEmail = $config['from_email'];
        $fromName = $config['from_name'];
        $encryption = $config['encryption'] ?? 'tls';

        // Connect to SMTP server
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Failed to connect to SMTP server: $errstr");
        }

        // Read server response
        $response = fgets($socket, 1024);
        if (strpos($response, '220') === false) {
            throw new Exception("Unexpected SMTP response: $response");
        }

        // Send EHLO
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 1024);
        // Consume multi-line EHLO response
        while (strpos($response, '-') === 3 && !feof($socket)) {
            $response = fgets($socket, 1024);
        }

        // Start TLS if configured
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 1024);
            
            if (strpos($response, '220') === false) {
                // Gmail returns different response, that's okay
                if (strpos($response, '250') === false) {
                    throw new Exception("STARTTLS failed: $response");
                }
            }

            // Enable crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS");
            }

            // Send EHLO again after TLS
            fputs($socket, "EHLO localhost\r\n");
            $response = fgets($socket, 1024);
            // Consume multi-line response
            while (strpos($response, '-') === 3 && !feof($socket)) {
                $response = fgets($socket, 1024);
            }
        }

        // Authenticate
        if (!empty($username) && !empty($password)) {
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 1024);

            fputs($socket, base64_encode($username) . "\r\n");
            fgets($socket, 1024);

            fputs($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket, 1024);

            if (strpos($response, '235') === false) {
                throw new Exception("Authentication failed: $response");
            }
        }

        // Set From
        fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
        fgets($socket, 1024);

        // Set To
        fputs($socket, "RCPT TO: <$toEmail>\r\n");
        fgets($socket, 1024);

        // Send message
        fputs($socket, "DATA\r\n");
        fgets($socket, 1024);

        // Build email message
        $message = "From: $fromName <$fromEmail>\r\n";
        $message .= "To: $toName <$toEmail>\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;

        // Send message data
        fputs($socket, $message . "\r\n.\r\n");
        $response = fgets($socket, 1024);

        if (strpos($response, '250') === false) {
            throw new Exception("Failed to send message: $response");
        }

        // Close connection
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
    }
}
