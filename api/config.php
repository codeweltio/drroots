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

    // Database (local Docker defaults; update on cPanel)
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306,
    'DB_NAME' => 'drrootsdc_dashboard',
    'DB_USER' => 'drrootsdc_drrootsdc_dashboard',
    'DB_PASS' => '5]Fs}H7{tkH~7oaR',
    // Use utf8mb4 as the connection charset. Your DB collation is utf8mb4_unicode_ci.
    'DB_CHARSET' => 'utf8mb4',

    // Controls whether the patient receives an email immediately on booking.
    // When false, only the clinic is notified at booking; the patient is emailed
    // on admin confirmation/reschedule/cancel actions.
    'PATIENT_EMAIL_ON_CREATE' => false,
];
