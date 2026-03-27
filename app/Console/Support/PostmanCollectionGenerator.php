<?php

declare(strict_types=1);

namespace App\Console\Support;

class PostmanCollectionGenerator
{
    private $collection;

    public function __construct()
    {
        $this->initCollection();
    }

    private function initCollection()
    {
        $this->collection = [
            'info' => [
                '_postman_id' => 'bds-complete-' . time(),
                'name' => 'BDS API -  (' . date('Y-m-d H:i') . ')',
                'description' => $this->getMainDescription(),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'item' => [],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]
            ],
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            "pm.request.headers.add({key: 'Accept', value: 'application/json'});",
                            "pm.request.headers.add({key: 'X-API-KEY', value: pm.environment.get('apiKey') || pm.variables.get('apiKey')});"
                        ]
                    ]
                ]
            ],
            'variable' => [
                ['key' => 'baseUrl', 'value' => 'http://localhost:8000/api/v1', 'type' => 'string'],
                ['key' => 'apiKey', 'value' => 'your-api-key-here', 'type' => 'string'],
                ['key' => 'token', 'value' => '', 'type' => 'string']
            ]
        ];
    }

    private function getMainDescription()
    {
        return <<<'MD'
# API Hệ Thống Bất Động Sản

## Quick Start
1. **Set Environment**:
   - `baseUrl`: `http://localhost:8000/api/v1` (hoặc IP LAN của máy tính nếu test trên Mobile thật: `http://192.168.1.x:8000/api/v1`)
   - `apiKey`: Key bảo mật (nếu có).
2. **Login**: Gọi API Login để lấy `token`.
3. **Authentication**:
   - Header `Authorization`: `Bearer {{token}}`
   - Header `X-API-KEY`: `{{apiKey}}`

## Mobile Integration Guide

### 1. Flutter (Dart)
Sử dụng thư viện `dio`:
```dart
final dio = Dio();
dio.options.headers['Authorization'] = 'Bearer $token';
dio.options.headers['X-API-KEY'] = '$apiKey';
dio.options.headers['Accept'] = 'application/json';

try {
  final response = await dio.get('http://api.com/api/v1/properties');
  print(response.data);
} catch (e) {
  print(e);
}
```

### 2. iOS (Swift)
Sử dụng `Alamofire`:
```swift
let headers: HTTPHeaders = [
    "Authorization": "Bearer \(token)",
    "X-API-KEY": apiKey,
    "Accept": "application/json"
]

AF.request("http://api.com/api/v1/properties", headers: headers).responseJSON { response in
    debugPrint(response)
}
```
MD;
    }

    public function generate()
    {
        $this->addAuthenticationFolder();
        $this->addDictionariesFolder();
        $this->addPropertiesFolder();
        $this->addPostsFolder();
        $this->addFilesFolder();
        $this->addPhoneRequestsFolder();
        $this->addReportsFolder();
        $this->addAdminFolder(); // Merged Admin properties, requests, reports, users

        return $this->collection;
    }

    public function saveToFile($filename = null)
    {
        $filename = $filename ?? storage_path('app/public/BDS_Complete_API.postman_collection.json');
        $json = json_encode($this->collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filename, $json);
        return $filename;
    }

    // ==========================================
    // MODULES
    // ==========================================

    private function addAuthenticationFolder()
    {
        $folder = [
            'name' => 'Authentication',
            'description' => "Đăng nhập, đăng ký và xác thực.",
            'item' => []
        ];

        // Login
        $folder['item'][] = $this->makeRequest(
            'Login',
            'POST',
            '/auth/login',
            "Đăng nhập lấy Token.\n\n**Note**: Token sẽ tự động được lưu vào environment `token`.",
            ['email' => 'admin@example.com', 'password' => 'password', 'device_name' => 'MobileApp'],
            [
                'response' => [
                    'message' => 'Login successful',
                    'access_token' => '1|sometoken...',
                    'user' => ['id' => 1, 'name' => 'Admin']
                ]
            ],
            "var j=pm.response.json(); if(j.access_token){ pm.environment.set('token', j.access_token); pm.variables.set('token', j.access_token); }"
        );

        // Forgot Password
        $folder['item'][] = $this->makeRequest('Forgot Password', 'POST', '/auth/forgot-password', 'Gửi OTP lấy lại mật khẩu', ['email' => 'user@example.com']);
        $folder['item'][] = $this->makeRequest('Verify OTP', 'POST', '/auth/verify-otp', 'Xác thực OTP', ['email' => 'user@example.com', 'otp' => '123456']);
        $folder['item'][] = $this->makeRequest('Reset Password', 'POST', '/auth/reset-password', 'Đặt lại mật khẩu mới', ['email' => 'user@example.com', 'password' => 'newpass', 'password_confirmation' => 'newpass']);

        // Authenticated Auth Routes
        $folder['item'][] = $this->makeRequest('Logout', 'POST', '/auth/logout', "Đăng xuất.");
        $folder['item'][] = $this->makeRequest('Refresh Token', 'POST', '/auth/refresh', "Làm mới Token.");
        $folder['item'][] = $this->makeRequest('Change Password', 'POST', '/auth/change-password', "Đổi mật khẩu", ['current_password' => 'oldpass', 'new_password' => 'newpass', 'new_password_confirmation' => 'newpass']);
        $folder['item'][] = $this->makeRequest('Get User Info (Me)', 'GET', '/me', "Lấy thông tin User hiện tại.");
        $folder['item'][] = $this->makeRequest(
            'Update Profile',
            'PUT',
            '/me',
            "Cập nhật thông tin cá nhân. Hỗ trợ: Tên, SĐT, Ngày sinh, Địa chỉ, Avatar.\n\n **Quy trình Upload Avatar**:\n 1. Gọi API `Upload File` để lấy URL ảnh.\n 2. Dán URL đó vào trường `avatar_url` của API này.",
            [
                'name' => 'Nguyễn Văn A',
                'phone' => '0912345678',
                'dob' => '1990-01-01',
                'current_address' => 'Hà Nội',
                'permanent_address' => 'Nam Định',
                'avatar_url' => 'http://domain.com/storage/uploads/avatar.jpg'
            ]
        );

        $this->collection['item'][] = $folder;
    }

    private function addPropertiesFolder()
    {
        $this->collection['item'][] = [
            'name' => 'Properties',
            'item' => [
                $this->makeRequest(
                    'List Properties',
                    'GET',
                    '/properties',
                    "Lấy danh sách BĐS với filter đa dạng:\n\n" .
                        "**Search:**\n- `q` - Tìm kiếm từ khóa (title, street_name, address, description)\n\n" .
                        "**Filter theo loại & khu vực:**\n- `category_id` - Lọc theo loại BĐS\n- `district_id` - Lọc theo quận/huyện\n- `ward_id` - Lọc theo phường/xã\n- `area_id` - Lọc theo khu vực (fallback)\n- `project_id` - Lọc theo dự án\n\n" .
                        "**Filter theo giá & diện tích:**\n- `price_min` - Giá tối thiểu\n- `price_max` - Giá tối đa\n- `area_min` - Diện tích tối thiểu (m²)\n- `area_max` - Diện tích tối đa (m²)\n\n" .
                        "**Filter theo chi tiết:**\n- `bedrooms` - Số phòng ngủ\n- `bathrooms` - Số phòng tắm\n- `direction` - Hướng nhà\n- `floor` - Số tầng\n- `approval_status` - Trạng thái duyệt (PENDING, APPROVED, REJECTED)\n\n" .
                        "**Sorting & Pagination:**\n- `sort` - Cột sắp xếp (created_at, price, area, title)\n- `order` - Thứ tự (asc, desc)\n- `limit` - Số item/page (max: 100)\n- `page` - Trang hiện tại",
                    null,
                    ['response' => ['data' => [], 'meta' => []]],
                    null,
                    'q=nhà+phố&category_id=1&district_id=3&price_min=3000000000&price_max=5000000000&area_min=100&bedrooms=3&sort=price&order=asc&limit=20&page=1'
                ),
                $this->makeRequest(
                    'List Properties (Simple)',
                    'GET',
                    '/properties',
                    "Lấy danh sách BĐS đơn giản (không filter)",
                    null,
                    ['response' => ['data' => [], 'meta' => []]],
                    null,
                    'page=1&limit=10'
                ),
                $this->makeRequest('Properties Map', 'GET', '/properties/map', "Lấy danh sách BĐS hiển thị bản đồ.", null, [], null, 'category_id=1&district_id=3'),
                $this->makeRequest('Property Detail', 'GET', '/properties/1', "Chi tiết BĐS (bao gồm images array).", null, ['response' => ['data' => []]]),
                $this->makeRequest('Create Property', 'POST', '/properties', "Đăng tin BĐS mới.", ['title' => 'Nhà bán', 'price' => 1000, 'category_id' => 1, 'area_id' => 1], ['response' => ['data' => ['id' => 1]]]),
                $this->makeRequest('Update Property', 'PUT', '/properties/1', "Cập nhật BĐS.", ['title' => 'Updated Title']),
                $this->makeRequest('Delete Property', 'DELETE', '/properties/1', "Xóa BĐS."),
                $this->makeRequest('My Properties', 'GET', '/me/properties', "BĐS của tôi."),
            ]
        ];
    }

    private function addDictionariesFolder()
    {
        $this->collection['item'][] = [
            'name' => 'Dictionaries',
            'item' => [
                $this->makeRequest('Get Areas', 'GET', '/dicts/areas', 'Danh sách Khu vực/Quận huyện.'),
                $this->makeRequest('Get Categories', 'GET', '/dicts/categories', 'Danh sách Danh mục BĐS.'),
                $this->makeRequest('Get Projects', 'GET', '/dicts/projects', 'Danh sách Dự án BĐS.'),
            ]
        ];
    }

    private function addPostsFolder()
    {
        $this->collection['item'][] = [
            'name' => 'Posts',
            'item' => [
                $this->makeRequest('List Posts', 'GET', '/posts', 'Quản lý bài đăng.', null, [], null, 'status=VISIBLE'),
                $this->makeRequest('Create Post', 'POST', '/posts', 'Tạo bài đăng cho BĐS.', ['property_id' => 1, 'title' => 'Tin VIP', 'visible_until' => '2025-12-31']),
                $this->makeRequest('Update Post', 'PATCH', '/posts/1', 'Cập nhật bài đăng.', ['title' => 'New Title']),
                $this->makeRequest('Renew Post', 'POST', '/posts/1/renew', 'Gia hạn bài đăng.', ['days' => 30]),
                $this->makeRequest('Hide Post', 'POST', '/posts/1/hide', 'Ẩn bài đăng.'),
                $this->makeRequest('Delete Post', 'DELETE', '/posts/1', 'Xóa bài đăng.'),
            ]
        ];
    }

    private function addFilesFolder()
    {
        $item = [
            'name' => 'Files',
            'item' => [
                $this->makeRequest('Upload File', 'POST', '/files', 'Upload ảnh/file (Multipart Form).'),
                $this->makeRequest('Download File', 'GET', '/files/1/download', 'Tải file.'),
            ]
        ];
        // Custom upload body
        $item['item'][0]['request']['body'] = [
            'mode' => 'formdata',
            'formdata' => [
                ['key' => 'file', 'type' => 'file', 'src' => []],
                ['key' => 'purpose', 'value' => 'PROPERTY_IMAGE', 'type' => 'text'],
                ['key' => 'visibility', 'value' => 'PUBLIC', 'type' => 'text']
            ]
        ];
        $this->collection['item'][] = $item;
    }

    private function addPhoneRequestsFolder()
    {
        $this->collection['item'][] = [
            'name' => 'Phone Requests',
            'item' => [
                $this->makeRequest('Create Request', 'POST', '/properties/1/owner-phone-requests', 'Yêu cầu xem SĐT chủ nhà.', ['reason' => 'Liên hệ mua'])
            ]
        ];
    }

    private function addReportsFolder()
    {
        $this->collection['item'][] = [
            'name' => 'Reports',
            'item' => [
                $this->makeRequest('Create Report', 'POST', '/reports', 'Báo cáo vi phạm.', ['post_id' => 1, 'reason' => 'SPAM'])
            ]
        ];
    }

    private function addProfileFolder()
    {
        // Included in Auth folder for simplicity in this version, or can be separate
    }

    private function addAdminFolder()
    {
        $adminItems = [];

        // Admin Properties
        $adminItems[] = [
            'name' => 'Properties',
            'item' => [
                $this->makeRequest('Admin List Properties', 'GET', '/admin/properties', 'Admin xem tất cả BĐS.'),
                $this->makeRequest('Approve Property', 'POST', '/admin/properties/1/approve', 'Admin duyệt BĐS.', ['note' => 'Ok']),
                $this->makeRequest('Reject Property', 'POST', '/admin/properties/1/reject', 'Admin từ chối BĐS.', ['reason' => 'Bad info'])
            ]
        ];

        // Admin Users
        $adminItems[] = [
            'name' => 'Users',
            'item' => [
                $this->makeRequest('List Users', 'GET', '/admin/users', 'Quản lý người dùng.', null, [], null, 'limit=10'),
                $this->makeRequest('Create User', 'POST', '/admin/users', 'Tạo User mới.', ['name' => 'Staff', 'email' => 'staff@test.com', 'password' => '123456', 'role' => 'FIELD_STAFF']),
                $this->makeRequest('User Detail', 'GET', '/admin/users/1', 'Xem chi tiết User.'),
                $this->makeRequest('Update User', 'PUT', '/admin/users/1', 'Cập nhật User.', ['name' => 'New Name']),
                $this->makeRequest('Delete User', 'DELETE', '/admin/users/1', 'Xóa User.'),
                $this->makeRequest('Lock User', 'POST', '/admin/users/1/lock', 'Khóa tài khoản User.', ['reason' => 'Vi phạm']),
                $this->makeRequest('Unlock User', 'POST', '/admin/users/1/unlock', 'Mở khóa tài khoản.'),
            ]
        ];

        // Admin Owner Phone Requests
        $adminItems[] = [
            'name' => 'Phone Requests',
            'item' => [
                $this->makeRequest('List Requests', 'GET', '/admin/owner-phone-requests', 'Duyệt yêu cầu xem SĐT.'),
                $this->makeRequest('Approve Request', 'POST', '/admin/owner-phone-requests/1/approve', 'Đồng ý cho xem SĐT.'),
                $this->makeRequest('Reject Request', 'POST', '/admin/owner-phone-requests/1/reject', 'Từ chối xem SĐT.'),
            ]
        ];

        // Admin Reports
        $adminItems[] = [
            'name' => 'Reports',
            'item' => [
                $this->makeRequest('List Reports', 'GET', '/admin/reports', 'Xem báo cáo vi phạm.'),
                $this->makeRequest('Resolve Report', 'POST', '/admin/reports/1/resolve', 'Xử lý báo cáo.', ['action' => 'HIDE_POST', 'admin_note' => 'Done']),
            ]
        ];

        $this->collection['item'][] = [
            'name' => 'Admin Dashboard',
            'description' => 'Các API dành cho quản trị viên (Admin Panel)',
            'item' => $adminItems
        ];
    }

    // ==========================================
    // HELPER: BUILD REQUEST + CODE SNIPPETS
    // ==========================================
    private function makeRequest($name, $method, $path, $desc = '', $body = null, $meta = [], $testScript = null, $queryParams = null)
    {
        // 1. Generate Code Snippets for Description
        $codeSnippets = $this->generateClientCodeSamples($method, '{{baseUrl}}' . $path, $body);

        $fullDescription = $desc . "\n\n" . $codeSnippets;

        // 2. Build Request Object
        $request = [
            'method' => $method,
            'header' => [
                ['key' => 'X-API-KEY', 'value' => '{{apiKey}}', 'type' => 'text'],
                ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text']
            ],
            'url' => [
                'raw' => '{{baseUrl}}' . $path,
                'host' => ['{{baseUrl}}'],
                'path' => array_filter(explode('/', trim($path, '/')))
            ],
            'description' => $fullDescription
        ];

        if ($queryParams) {
            parse_str($queryParams, $params);
            $request['url']['raw'] .= '?' . $queryParams;
            $request['url']['query'] = [];
            foreach ($params as $key => $value) {
                $request['url']['query'][] = [
                    'key' => $key,
                    'value' => $value
                ];
            }
        }

        if ($body) {
            $request['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'options' => ['raw' => ['language' => 'json']]
            ];
        }

        $item = [
            'name' => $name,
            'request' => $request,
            'response' => []
        ];

        // 3. Add Examples
        if (isset($meta['response'])) {
            $item['response'][] = [
                'name' => 'Example Success',
                'originalRequest' => $request,
                'status' => 'OK',
                'code' => 200,
                '_postman_previewlanguage' => 'json',
                'header' => [['key' => 'Content-Type', 'value' => 'application/json']],
                'body' => json_encode($meta['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ];
        }

        if ($testScript) {
            $item['event'] = [[
                'listen' => 'test',
                'script' => ['exec' => is_array($testScript) ? $testScript : [$testScript], 'type' => 'text/javascript']
            ]];
        }

        return $item;
    }

    private function generateClientCodeSamples($method, $url, $body = null)
    {
        $bodyJson = $body ? json_encode($body, JSON_PRETTY_PRINT) : '{}';

        // Flutter (Dart/Dio)
        $flutterCode = "```dart\n// Flutter (Dio)\n";
        $flutterCode .= "var response = await dio.request('$url',\n";
        $flutterCode .= "  options: Options(method: '$method'),\n";
        if ($body) {
            $flutterCode .= "  data: $bodyJson\n";
        }
        $flutterCode .= ");\n```";

        // iOS (Swift/Alamofire)
        $swiftCode = "```swift\n// iOS (Alamofire)\n";
        $swiftCode .= "AF.request(\"$url\",\n";
        $swiftCode .= "           method: .$method,\n";
        if ($body) {
            $swiftCode .= "           parameters: [:], // Replace with params\n";
            $swiftCode .= "           encoding: JSONEncoding.default,\n";
        }
        $swiftCode .= "           headers: headers)\n";
        $swiftCode .= "  .responseJSON { response in debugPrint(response) }\n```";

        return "### Code Examples\n" . $flutterCode . "\n" . $swiftCode;
    }
}
