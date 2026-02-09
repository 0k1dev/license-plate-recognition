<?php

/**
 * Script để generate Postman Collection với Full Documentation
 */

$collection = [
    'info' => [
        '_postman_id' => 'bds-full-documented-v1',
        'name' => '🏠 BDS API - Full Documentation',
        'description' => file_get_contents(__DIR__ . '/postman_description_main.md'),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'item' => [],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{token}}', 'type' => 'string']
        ]
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000/api/v1', 'type' => 'string'],
        ['key' => 'apiKey', 'value' => 'bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb', 'type' => 'string']
    ]
];

// Định nghĩa các folders và requests
$endpoints = [
    [
        'folder' => '🔐 Authentication',
        'description' => file_get_contents(__DIR__ . '/postman_auth_folder.md'),
        'requests' => [
            [
                'name' => 'Login',
                'method' => 'POST',
                'path' => '/auth/login',
                'description' => file_get_contents(__DIR__ . '/postman_login.md'),
                'body' => [
                    'email' => 'admin@example.com',
                    'password' => 'password',
                    'device_name' => 'Postman',
                    'remember' => true
                ],
                'test_script' => [
                    "var jsonData = pm.response.json();",
                    "if (jsonData.access_token) {",
                    "    pm.environment.set('token', jsonData.access_token);",
                    "    pm.environment.set('refresh_token', jsonData.refresh_token);",
                    "    console.log('✅ Token saved');",
                    "}"
                ],
                'examples' => [
                    [
                        'name' => 'Success - Admin Login',
                        'status' => 200,
                        'body' => [
                            'message' => 'Đăng nhập thành công!',
                            'access_token' => '1|abc...',
                            'refresh_token' => '2|def...',
                            'expires_in' => 3600,
                            'user' => ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'SUPER_ADMIN']
                        ]
                    ]
                ]
            ]
        ]
    ]
];

function buildRequest($req)
{
    $request = [
        'name' => $req['name'],
        'request' => [
            'method' => $req['method'],
            'header' => [
                ['key' => 'X-API-KEY', 'value' => '{{apiKey}}', 'type' => 'text']
            ],
            'url' => [
                'raw' => '{{baseUrl}}' . $req['path'],
                'host' => ['{{baseUrl}}'],
                'path' => array_filter(explode('/', $req['path']))
            ],
            'description' => $req['description'] ?? ''
        ]
    ];

    if (isset($req['body'])) {
        $request['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($req['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'options' => ['raw' => ['language' => 'json']]
        ];
    }

    if (isset($req['test_script'])) {
        $request['event'] = [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => $req['test_script'],
                    'type' => 'text/javascript'
                ]
            ]
        ];
    }

    if (isset($req['examples'])) {
        $request['response'] = array_map(function ($ex) use ($req) {
            return [
                'name' => $ex['name'],
                'originalRequest' => $request['request'],
                'status' => $ex['status'] >= 200 && $ex['status'] < 300 ? 'OK' : 'Error',
                'code' => $ex['status'],
                '_postman_previewlanguage' => 'json',
                'header' => [['key' => 'Content-Type', 'value' => 'application/json']],
                'body' => json_encode($ex['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ];
        }, $req['examples']);
    }

    return $request;
}

foreach ($endpoints as $folder) {
    $folderItem = [
        'name' => $folder['folder'],
        'description' => $folder['description'] ?? '',
        'item' => array_map('buildRequest', $folder['requests'])
    ];

    $collection['item'][] = $folderItem;
}

// Save collection
file_put_contents(
    __DIR__ . '/BDS_Generated.postman_collection.json',
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "✅ Generated Postman Collection with Documentation!\n";
echo "File: BDS_Generated.postman_collection.json\n";
