<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Simple Mailer Class
 * A lightweight email sending class for development purposes
 */
class Mailer {
    // Email properties
    private $to = '';
    private $subject = '';
    private $message = '';
    private $from = '';
    private $fromName = '';
    private $cc = [];
    private $bcc = [];
    private $attachments = [];
    private $isHtml = false;
    private $errorInfo = '';
    private $logDir = '';
    
    // SMTP settings
    private $smtpEnabled = false;
    private $smtpHost = '';
    private $smtpPort = 25;
    private $smtpUsername = '';
    private $smtpPassword = '';
    private $smtpSecure = '';
    
    /**
     * Constructor
     * 
     * @param string $logDir Directory to save email logs
     */
    public function __construct($logDir = null) {
        if ($logDir === null) {
            $this->logDir = dirname(__DIR__) . '/logs/emails';
        } else {
            $this->logDir = $logDir;
        }
        
        // Create log directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Initialize SMTP settings from config if available
        $this->initSmtpConfig();
    }
    
    /**
     * Initialize SMTP settings from configuration constants
     */
    private function initSmtpConfig() {
        if (defined('SMTP_HOST') && !empty(SMTP_HOST)) {
            $this->smtpEnabled = true;
            $this->smtpHost = SMTP_HOST;
            
            if (defined('SMTP_PORT')) {
                $this->smtpPort = SMTP_PORT;
            }
            
            if (defined('SMTP_USERNAME')) {
                $this->smtpUsername = SMTP_USERNAME;
            }
            
            if (defined('SMTP_PASSWORD')) {
                $this->smtpPassword = SMTP_PASSWORD;
            }
            
            if (defined('SMTP_SECURE')) {
                $this->smtpSecure = SMTP_SECURE;
            }
            
            // Default From settings from config
            if (defined('SMTP_FROM_EMAIL') && empty($this->from)) {
                $this->from = SMTP_FROM_EMAIL;
                
                if (defined('SMTP_FROM_NAME')) {
                    $this->fromName = SMTP_FROM_NAME;
                }
            }
        }
    }
    
    /**
     * Set email sender
     * 
     * @param string $email Sender email address
     * @param string $name Sender name
     * @return $this for method chaining
     */
    public function setFrom($email, $name = '') {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }
    
    /**
     * Set email recipient(s)
     * 
     * @param string|array $to Recipient email address(es)
     * @return $this for method chaining
     */
    public function setTo($to) {
        $this->to = is_array($to) ? implode(', ', $to) : $to;
        return $this;
    }
    
    /**
     * Set email subject
     * 
     * @param string $subject Email subject
     * @return $this for method chaining
     */
    public function setSubject($subject) {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Set email message
     * 
     * @param string $message Email message
     * @param bool $isHtml Whether the message is HTML
     * @return $this for method chaining
     */
    public function setMessage($message, $isHtml = false) {
        $this->message = $message;
        $this->isHtml = $isHtml;
        return $this;
    }
    
    /**
     * Add CC recipient(s)
     * 
     * @param string|array $cc CC recipient(s)
     * @return $this for method chaining
     */
    public function addCC($cc) {
        if (is_array($cc)) {
            $this->cc = array_merge($this->cc, $cc);
        } else {
            $this->cc[] = $cc;
        }
        return $this;
    }
    
    /**
     * Add BCC recipient(s)
     * 
     * @param string|array $bcc BCC recipient(s)
     * @return $this for method chaining
     */
    public function addBCC($bcc) {
        if (is_array($bcc)) {
            $this->bcc = array_merge($this->bcc, $bcc);
        } else {
            $this->bcc[] = $bcc;
        }
        return $this;
    }
    
    /**
     * Add file attachment
     * 
     * @param string $path File path
     * @return $this for method chaining
     */
    public function addAttachment($path) {
        if (file_exists($path)) {
            $this->attachments[] = $path;
        }
        return $this;
    }
    
    /**
     * Send the email
     * 
     * @return bool Whether the email was sent
     */
    public function send() {
        // Log the email first
        $logFile = $this->logEmail();
        
        // In development environment, just log the email
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_log("Email logged to: $logFile (not sent in development mode)");
            return true;
        }
        
        // Use PHPMailer for all sending
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            // Gmail-specific options
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            // From
            if (!empty($this->fromName)) {
                $mail->setFrom($this->from, $this->fromName);
            } else {
                $mail->setFrom($this->from);
            }
            // To
            foreach (explode(',', $this->to) as $to) {
                $to = trim($to);
                if ($to) $mail->addAddress($to);
            }
            // CC
            foreach ($this->cc as $cc) {
                $mail->addCC($cc);
            }
            // BCC
            foreach ($this->bcc as $bcc) {
                $mail->addBCC($bcc);
            }
            // Attachments
            foreach ($this->attachments as $file) {
                $mail->addAttachment($file);
            }
            // Subject & Body
            $mail->Subject = $this->subject;
            if ($this->isHtml) {
                $mail->isHTML(true);
                $mail->Body = $this->message;
                $mail->AltBody = strip_tags($this->message);
            } else {
                $mail->isHTML(false);
                $mail->Body = $this->message;
            }
            $mail->send();
            error_log("Email sent to: {$this->to} and logged to: $logFile");
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->errorInfo = $e->getMessage();
            error_log("Failed to send email to: {$this->to}. Error: " . $this->errorInfo);
            return false;
        }
    }
    
    /**
     * Get last error message
     * 
     * @return string Error message
     */
    public function getErrorInfo() {
        return $this->errorInfo;
    }
    
    /**
     * Log the email to a file
     * 
     * @return string Path to the log file
     */
    private function logEmail() {
        // Create a unique filename
        $filename = $this->logDir . '/' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.txt';
        
        // Format email content for logging
        $content = "To: {$this->to}\n";
        if (!empty($this->cc)) {
            $content .= "CC: " . implode(', ', $this->cc) . "\n";
        }
        if (!empty($this->bcc)) {
            $content .= "BCC: " . implode(', ', $this->bcc) . "\n";
        }
        $content .= "From: ";
        $content .= !empty($this->fromName) ? "{$this->fromName} <{$this->from}>" : $this->from;
        $content .= "\n";
        $content .= "Subject: {$this->subject}\n";
        $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Format: " . ($this->isHtml ? 'HTML' : 'Plain Text') . "\n";
        
        if ($this->smtpEnabled) {
            $content .= "Sending Method: SMTP\n";
            $content .= "SMTP Host: {$this->smtpHost}:{$this->smtpPort}\n";
        } else {
            $content .= "Sending Method: PHP mail()\n";
        }
        
        if (!empty($this->attachments)) {
            $content .= "Attachments: " . implode(', ', array_map('basename', $this->attachments)) . "\n";
        }
        
        $content .= "----------------------------------------\n";
        $content .= $this->message . "\n";
        $content .= "----------------------------------------\n";
        
        // Write to file
        file_put_contents($filename, $content);
        
        return $filename;
    }
    
    /**
     * Prepare email headers
     * 
     * @return string Headers string
     */
    private function prepareHeaders() {
        $headers = [];
        
        // From header
        if (!empty($this->fromName)) {
            $headers[] = "From: {$this->fromName} <{$this->from}>";
        } else {
            $headers[] = "From: {$this->from}";
        }
        
        // Reply-To header
        $headers[] = "Reply-To: {$this->from}";
        
        // CC header
        if (!empty($this->cc)) {
            $headers[] = "Cc: " . implode(', ', $this->cc);
        }
        
        // BCC header
        if (!empty($this->bcc)) {
            $headers[] = "Bcc: " . implode(', ', $this->bcc);
        }
        
        // Content type header
        if ($this->isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        // X-Mailer header
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        return implode("\r\n", $headers);
    }
} 