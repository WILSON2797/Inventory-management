<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

/**
 * Kirim email notifikasi dengan recipients dari database atau config
 * 
 * @param string $subject Subject email (akan ditambah prefix dari config)
 * @param string $bodyHtml Konten email dalam format HTML
 * @return bool True jika berhasil, false jika gagal
 */
function sendEmailNotification($subject, $bodyHtml)
{
    $config = require __DIR__ . '/email_config.php';
    $mail = new PHPMailer(true);

    try {
        // ============================
        // SMTP Setup
        // ============================
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['secure'];
        $mail->Port       = $config['smtp']['port'];
        $mail->Timeout    = $config['smtp']['timeout'];

        // Debug mode jika enabled
        if (!empty($config['advanced']['debug_mode'])) {
            $mail->SMTPDebug = 2;
        }

        // ============================
        // Sender Info
        // ============================
        $mail->setFrom($config['sender']['email'], $config['sender']['name']);

        // ============================
        // Recipients dari Database
        // ============================
        global $pdo;
        $recipientsAdded = false;

        if (!empty($config['database']['use_database_recipients'])) {
            try {
                // FIX: Gunakan kolom yang sesuai dengan schema database
                $stmt = $pdo->query("
                    SELECT email, nama 
                    FROM {$config['database']['recipients_table']} 
                    WHERE status = 'active'
                ");
                
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($recipients as $r) {
                    $mail->addBCC($r['email'], $r['nama']);
                    $recipientsAdded = true;
                }

                // Log jumlah recipients
                if (!empty($config['advanced']['save_sent_emails_log'])) {
                    error_log("Email recipients loaded from database: " . count($recipients));
                }

            } catch (Exception $e) {
                error_log("Failed to load recipients from database: " . $e->getMessage());
                
                // Fallback ke config jika enabled
                if (!empty($config['database']['fallback_to_config'])) {
                    foreach ($config['recipients']['bcc'] as $email => $name) {
                        $mail->addBCC($email, $name);
                        $recipientsAdded = true;
                    }
                    
                    if (!empty($config['advanced']['save_sent_emails_log'])) {
                        error_log("Email recipients fallback to config file");
                    }
                }
            }
        }

        // ============================
        // Fallback jika tidak ada recipients dari database
        // ============================
        if (!$recipientsAdded && !empty($config['recipients']['bcc'])) {
            foreach ($config['recipients']['bcc'] as $email => $name) {
                $mail->addBCC($email, $name);
                $recipientsAdded = true;
            }
        }

        // Validasi: harus ada minimal 1 recipient
        if (!$recipientsAdded) {
            throw new Exception("No email recipients configured");
        }

        // ============================
        // Email Content
        // ============================
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $config['content']['subject_prefix'] . " " . $subject;
        $mail->Body    = $bodyHtml;
        
        // Plain text alternative
        $mail->AltBody = strip_tags($bodyHtml);

        // ============================
        // Send Email
        // ============================
        $mail->send();

        // Log success
        if (!empty($config['advanced']['save_sent_emails_log'])) {
            error_log("Email sent successfully: {$subject}");
        }

        return true;

    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        
        // Send error notification jika enabled
        if (!empty($config['notifications']['send_error_notification'])) {
            sendErrorNotification($subject, $e->getMessage(), $config);
        }
        
        return false;
    }
}

/**
 * Kirim notifikasi error ke admin
 */
function sendErrorNotification($originalSubject, $errorMessage, $config)
{
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['secure'];
        $mail->Port       = $config['smtp']['port'];
        
        $mail->setFrom($config['sender']['email'], $config['sender']['name']);
        
        foreach ($config['notifications']['error_recipients'] as $email => $name) {
            $mail->addAddress($email, $name);
        }
        
        $mail->isHTML(true);
        $mail->Subject = "[ERROR] Email Sending Failed - " . $originalSubject;
        $mail->Body = "
            <h3>Email Sending Error</h3>
            <p><strong>Original Subject:</strong> {$originalSubject}</p>
            <p><strong>Error:</strong> {$errorMessage}</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send error notification: " . $e->getMessage());
    }
}