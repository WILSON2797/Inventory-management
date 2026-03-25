<?php

return [
    // SMTP Configuration - Niagahoster settings
    'smtp' => [
        'host' => 'srv184.niagahoster.com',
        'port' => 465,
        'username' => 'system@example.com',
        'password' => 'example', 
        'secure' => 'ssl', 
        'timeout' => 30,
    ],
    
    // Sender Information
    'sender' => [
        'email' => 'system@example.com',
        'name' => 'FIS - System Notification',
    ],
    
    // Recipients - Sekarang hanya sebagai fallback jika database error
    'recipients' => [
        'to' => [
            // Kosongkan - tidak digunakan karena menggunakan BCC only
        ],
        'cc' => [
            // Kosongkan - tidak digunakan karena menggunakan BCC only
        ],
        'bcc' => [
            // Fallback email jika database tidak bisa diakses
            'wilsongurning@fanindonesialogistics.com' => 'Wilson Gurning',
        ],
    ],
    
    // Database Settings - TAMBAHAN BARU
    'database' => [
        'use_database_recipients' => true, // Set false jika ingin kembali ke config file
        'recipients_table' => 'notification_mail',
        'fallback_to_config' => true, // Fallback ke config jika database error
    ],
    
    // Email Content Settings
    'content' => [
        'subject_prefix' => '',
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'd F Y',
        'time_format' => 'H:i',
    ],
    
    // File Settings
    'files' => [
        'temp_directory' => dirname(__DIR__) . '/cron/temp',
        'log_directory'  => dirname(__DIR__) . '/cron/logs',
        'filename_prefix' => '[Daily_Stock_Report]',
        'cleanup_temp_files' => true,
        'max_file_size_mb' => 10, // Maximum attachment size
    ],
    
    // Report Settings
    'report' => [
        'include_summary' => true,
        'include_warehouse_breakdown' => true,
        'show_zero_stock' => false, // Hide items dengan stock 0
        'sort_by' => 'Inbound_date', // Options: Inbound_date, po_number, supplier, item_code, last_updated
        'sort_order' => 'ASC', // ASC atau DESC
    ],
    
    // Notification Settings
    'notifications' => [
        'send_success_notification' => false,
        'send_error_notification' => true,
        'error_recipients' => [
            'wilsongurning@fanindonesialogistics.com' => 'System Admin',
            
        ],
    ],
    
    // Advanced Settings
    'advanced' => [
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds
        'debug_mode' => false, // Set true untuk debug SMTP
        'save_sent_emails_log' => true,
    ],
];
?>