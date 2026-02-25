<?php

return [
    //If you wish to customise the table name change this before migration
    'table_name'       => 'vb_email_templates',

    //If you want to use your own resource for email templates,
    //you can set this to true and use `php artisan email-template:publish` to publish the resource
    "publish_resource" => false,

    //Email templates will be copied to resources/views/vendor/vb-email-templates/email
    //default.blade.php is base view that can be customised below
    'default_view'     => 'default',

    'template_view_path'   => 'vb-email-templates::email',

    //Default Email Styling
    'logo'             => 'media/email-templates/logo.png',

    //Logo size in pixels
    'logo_width'       => '180',
    'logo_height'      => 'auto',

    //Content Width in Pixels
    'content_width'    => '600',

    //Background Colours
    'header_bg_color'  => '#4f46e5',   // Indigo - brand color
    'body_bg_color'    => '#f8fafc',   // Light gray
    'content_bg_color' => '#ffffff',   // White
    'footer_bg_color'  => '#1e293b',   // Dark slate
    'callout_bg_color' => '#f1f5f9',   // Light blue-gray
    'button_bg_color'  => '#4f46e5',   // Indigo

    //Text Colours
    'body_color'       => '#334155',   // Slate
    'callout_color'    => '#1e293b',   // Dark
    'button_color'     => '#ffffff',   // White text on button
    'anchor_color'     => '#4f46e5',   // Indigo

    //Contact details included in default email templates
    'customer-services-email' => env('MAIL_FROM_ADDRESS', 'support@appbds.com'),
    'customer-services-phone' => '',

    //Footer Links
    'links' => [
        ['name' => 'Website', 'url' => env('APP_URL', 'http://localhost'), 'title' => 'Truy cập website'],
        ['name' => 'Chính sách bảo mật', 'url' => env('APP_URL', 'http://localhost') . '/privacy', 'title' => 'Xem chính sách bảo mật'],
    ],

    //Options for alternative languages
    'default_locale'   => 'vi',

    //These will be included in the language picker when editing an email template
    'languages'        => [
        'vi'    => ['display' => 'Tiếng Việt', 'flag-icon' => 'vn'],
        'en_GB' => ['display' => 'English', 'flag-icon' => 'gb'],
    ],

    //Notifiable Models who can receive emails
    'recipients'       => [
        '\\App\\Models\\User',
    ],

    //Guards who are authorised to edit email templates
    'editor_guards'    => ['web'],

    /**
     * Allowed config keys which can be inserted into email templates
     */
    'config_keys'      => [
        'app.name',
        'app.url',
        'email-templates.customer-services'
    ],

    //Most built-in emails can be automatically sent with minimal setup
    'send_emails'      => [
        'new_user_registered'    => false,
        'verification'           => false,
        'user_verified'          => false,
        'login'                  => false,
        'password_reset_success' => false,
    ],
];
