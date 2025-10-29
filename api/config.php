<?php
// Basic mail configuration for cPanel hosting.
// Set MAIL_DELIVERY to 'mail' to send via PHP's mail() (recommended on cPanel),
// or 'log' to log emails to data/email.log without sending.
return [
    'MAIL_DELIVERY' => 'mail', // 'mail' | 'log' | 'smtp' (future)

    // The address must be a valid mailbox on your cPanel domain to avoid DMARC issues.
    // Send from the main clinic mailbox to satisfy DMARC alignment
    'MAIL_FROM' => 'info@drrootsdc.in',
    'MAIL_FROM_NAME' => 'Dr. Roots Dental Clinic',
    'MAIL_REPLY_TO' => 'info@drrootsdc.in',

    // If you later want SMTP, switch MAIL_DELIVERY to 'smtp' and fill below.
    'SMTP_HOST' => '',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls', // 'ssl' | 'tls'
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
];
