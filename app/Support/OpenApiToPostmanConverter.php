<?php

declare(strict_types=1);

namespace App\Console\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service để convert OpenAPI spec từ Scramble sang Postman Collection format.
 * Đảm bảo Postman collection luôn đồng bộ với /docs/api
 * 
 * Tính năng:
 * - Auto-sync với Scramble OpenAPI spec
 * - Mobile Integration Guide (Flutter/Dart + iOS/Swift)
 * - Code examples cho từng request
 * - Example responses
 * - Auto-save token khi login
 */
class OpenApiToPostmanConverter
{
    private array $collection = [];
    private string $baseUrl = '{{base_url}}';
    private array $openApiSpec = [];

    /**
     * Fetch OpenAPI spec và convert sang Postman collection
     * 
     * @param string $sourceUrl URL để fetch file api.json (nên là localhost để nhanh)
     * @param string|null $targetBaseUrl URL sẽ được dùng trong Postman variable (ví dụ: IP LAN)
     */
    public function generate(string $sourceUrl = 'http://127.0.0.1:8000', ?string $targetBaseUrl = null): self
    {
        // 1. Try Internal Request via Route Dispatch (SAFE for Single-Threaded Server)
        // This avoids the Deadlock/Timeout issue when calling self via HTTP
        try {
            $request = \Illuminate\Http\Request::create('/docs/api.json', 'GET');
            $request->headers->set('Accept', 'application/json');

            // Process request internally through the application kernel
            $response = app()->handle($request);

            if ($response->getStatusCode() === 200) {
                $this->openApiSpec = json_decode($response->getContent(), true);
            }
        } catch (\Exception $e) {
            // Log warning but continue to HTTP fallback
            \Illuminate\Support\Facades\Log::warning("Internal OpenAPI fetch failed: " . $e->getMessage());
        }

        // 2. Fallback to HTTP Request (if internal failed)
        if (empty($this->openApiSpec)) {
            $openApiUrl = rtrim($sourceUrl, '/') . '/docs/api.json';

            try {
                $response = Http::timeout(30)->get($openApiUrl);

                if ($response->successful()) {
                    $this->openApiSpec = $response->json();
                }
            } catch (\Exception $e) {
                throw new \RuntimeException("Connection error to {$openApiUrl}: " . $e->getMessage());
            }
        }

        if (empty($this->openApiSpec)) {
            throw new \RuntimeException("OpenAPI spec is empty or invalid JSON. Could not fetch locally or remotely.");
        }

        $this->collection = $this->convertToPostman($this->openApiSpec, $targetBaseUrl);

        return $this;
    }



    /**
     * Set base URL cho requests trong Postman
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Get collection array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * Get collection as JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Save collection to file
     */
    public function saveToFile(?string $filename = null): string
    {
        $filename = $filename ?: 'BDS_API_Collection.postman_collection.json';
        $storagePath = "postman-collections/{$filename}";

        Storage::disk('local')->put($storagePath, $this->toJson());

        return Storage::disk('local')->path($storagePath);
    }

    /**
     * Get statistics about the collection
     */
    public function getStats(): array
    {
        $endpointCount = 0;
        foreach ($this->collection['item'] ?? [] as $folder) {
            $endpointCount += count($folder['item'] ?? []);
        }

        return [
            'folders' => count($this->collection['item'] ?? []),
            'endpoints' => $endpointCount,
            'base_url' => $this->baseUrl,
        ];
    }

    /**
     * Convert OpenAPI spec to Postman collection format
     */
    private function convertToPostman(array $openApi, ?string $targetBaseUrl = null): array
    {
        $info = $openApi['info'] ?? [];
        $appUrl = config('app.url', 'http://127.0.0.1:8000');

        // Use target base URL if provided, otherwise use app.url
        $postmanBaseUrl = $targetBaseUrl ? rtrim($targetBaseUrl, '/') : $appUrl;

        // Ensure /api/v1 prefix is handled correctly in the baseUrl variable if needed,
        // but typically we want baseUrl to be the root or API root.
        // Let's assume user wants baseUrl to point to API root if they provided it specifically.

        // If we want consistent behavior with previous fix:
        // We set baseUrl to "http://IP:PORT/api/v1"
        if (!str_ends_with($postmanBaseUrl, '/api/v1')) {
            $postmanBaseUrl = rtrim($postmanBaseUrl, '/') . '/api/v1';
        }

        $collection = [
            'info' => [
                '_postman_id' => Str::uuid()->toString(),
                'name' => $info['title'] ?? 'BDS API Collection',
                'description' => $this->getMainDescription($info), // Changed from buildDescription to getMainDescription
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key' => 'baseUrl',
                    'value' => $postmanBaseUrl,
                    'type' => 'string',
                    'description' => 'Base URL của API server (bao gồm /api/v1)',
                ],
                [
                    'key' => 'apiKey',
                    'value' => 'your-api-key-here',
                    'type' => 'string',
                    'description' => 'API Key (nếu có)',
                ],
                [
                    'key' => 'token',
                    'value' => '',
                    'type' => 'string',
                    'description' => 'Bearer token sau khi login',
                ],
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{token}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            "pm.request.headers.add({key: 'Accept', value: 'application/json'});",
                            "pm.request.headers.add({key: 'X-API-KEY', value: pm.collectionVariables.get('apiKey') || ''});",
                        ],
                    ],
                ],
                [
                    'listen' => 'test',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            '// Auto-save token from login response',
                            'if (pm.response.code === 200) {',
                            '    try {',
                            '        var jsonData = pm.response.json();',
                            '        // Handle both access_token and data.token formats',
                            '        var token = jsonData.access_token || (jsonData.data && jsonData.data.token);',
                            '        if (token) {',
                            '            pm.collectionVariables.set("token", token);',
                            '            console.log("✅ Token saved to collection variables");',
                            '        }',
                            '    } catch (e) {}',
                            '}',
                        ],
                    ],
                ],
            ],
            'item' => [],
        ];

        // Group paths by tags
        $groupedPaths = $this->groupPathsByTags($openApi['paths'] ?? []);

        foreach ($groupedPaths as $tag => $paths) {
            $folder = [
                'name' => $this->formatTagName($tag),
                'description' => $this->getTagDescription($tag),
                'item' => [],
            ];

            foreach ($paths as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    $request = $this->createRequest($path, $method, $operation);
                    if ($request) {
                        $folder['item'][] = $request;
                    }
                }
            }

            if (!empty($folder['item'])) {
                $collection['item'][] = $folder;
            }
        }

        return $collection;
    }

    /**
     * Main description với Mobile Integration Guide
     */
    private function getMainDescription(array $info): string
    {
        $version = $info['version'] ?? '1.0.0';

        return <<<MD
# API Hệ Thống Bất Động Sản

**Version:** {$version}
**Generated:** {$this->now()}

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
dio.options.headers['Authorization'] = 'Bearer \$token';
dio.options.headers['X-API-KEY'] = '\$apiKey';
dio.options.headers['Accept'] = 'application/json';

try {
  final response = await dio.get('http://api.com/api/v1/properties');
  print(response.data);
} catch (e) {
  print(e);
}
```

### 2. React Native (Expo)
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://192.168.1.x:8000/api/v1',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer \${token}`,
  }
});

const response = await api.get('/properties');
console.log(response.data);
```

### 3. iOS (Swift)
Sử dụng `Alamofire`:
```swift
let headers: HTTPHeaders = [
    "Authorization": "Bearer \\(token)",
    "X-API-KEY": apiKey,
    "Accept": "application/json"
]

AF.request("http://api.com/api/v1/properties", headers: headers).responseJSON { response in
    debugPrint(response)
}
```

### 4. Android (Kotlin)
Sử dụng `Retrofit`:
```kotlin
interface ApiService {
    @GET("properties")
    suspend fun getProperties(): Response<PropertyList>
}

// Add interceptor for auth
val client = OkHttpClient.Builder()
    .addInterceptor { chain ->
        val request = chain.request().newBuilder()
            .addHeader("Authorization", "Bearer \$token")
            .addHeader("Accept", "application/json")
            .build()
        chain.proceed(request)
    }
    .build()
```
MD;
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function getTagDescription(string $tag): string
    {
        $descriptions = [
            'Authentication' => 'Đăng nhập, đăng ký và xác thực người dùng.',
            'Auth' => 'Đăng nhập, đăng ký và xác thực người dùng.',
            'Properties' => 'Quản lý bất động sản (CRUD, tìm kiếm, filter).',
            'Posts' => 'Quản lý bài đăng tin bất động sản.',
            'Files' => 'Upload và quản lý file/ảnh.',
            'Dictionaries' => 'Danh mục dữ liệu (khu vực, loại BĐS, dự án).',
            'Admin' => 'API dành cho quản trị viên.',
            'Reports' => 'Báo cáo vi phạm.',
            'Phone Requests' => 'Yêu cầu xem số điện thoại chủ nhà.',
            'Profile' => 'Quản lý thông tin cá nhân.',
        ];

        return $descriptions[$tag] ?? "Endpoints for {$tag}";
    }

    private function groupPathsByTags(array $paths): array
    {
        $grouped = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                    continue;
                }

                $tags = $operation['tags'] ?? ['Other'];
                $tag = $tags[0] ?? 'Other';

                if (!isset($grouped[$tag])) {
                    $grouped[$tag] = [];
                }

                if (!isset($grouped[$tag][$path])) {
                    $grouped[$tag][$path] = [];
                }

                $grouped[$tag][$path][$method] = $operation;
            }
        }

        // Sort by tag name
        ksort($grouped);

        return $grouped;
    }

    private function formatTagName(string $tag): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $tag));
    }

    private function createRequest(string $path, string $method, array $operation): ?array
    {
        // Remove /api/v1 prefix since baseUrl already includes it
        // Example: /api/v1/auth/login -> /auth/login
        // Example: /api/v1/admin/properties -> /admin/properties
        $cleanPath = $path;

        // Remove various possible prefixes
        $prefixes = ['/api/v1/', '/api/v1', '/v1/', '/v1', 'api/v1/', 'api/v1', 'v1/', 'v1'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($cleanPath, $prefix)) {
                $cleanPath = '/' . ltrim(substr($cleanPath, strlen($prefix)), '/');
                break;
            }
        }

        // Ensure path starts with /
        if (!str_starts_with($cleanPath, '/')) {
            $cleanPath = '/' . $cleanPath;
        }

        // Build URL - baseUrl = http://192.168.1.27:8000/api/v1
        // So final URL = {{baseUrl}}/auth/login (not {{baseUrl}}/api/v1/auth/login)
        $url = '{{baseUrl}}' . $cleanPath;

        // Build path segments for Postman
        $pathSegments = array_values(array_filter(explode('/', ltrim($cleanPath, '/'))));

        // Replace path params with Postman variable format (:id instead of {id})
        $pathSegments = array_map(function ($segment) {
            if (preg_match('/^\{(.+)\}$/', $segment, $matches)) {
                return ':' . $matches[1];
            }
            return $segment;
        }, $pathSegments);

        // Build URL parts
        $urlParts = [
            'raw' => $url,
            'host' => ['{{baseUrl}}'],
            'path' => $pathSegments,
        ];

        // Handle query parameters
        $queryParams = [];
        $pathParams = [];

        foreach ($operation['parameters'] ?? [] as $param) {
            $paramIn = $param['in'] ?? '';

            if ($paramIn === 'query') {
                $queryParams[] = [
                    'key' => $param['name'],
                    'value' => $this->getParamDefaultValue($param),
                    'description' => $param['description'] ?? '',
                    'disabled' => !($param['required'] ?? false),
                ];
            } elseif ($paramIn === 'path') {
                $pathParams[] = [
                    'key' => $param['name'],
                    'value' => $this->getParamDefaultValue($param) ?: '1',
                    'description' => $param['description'] ?? '',
                ];
            }
        }

        if (!empty($queryParams)) {
            $urlParts['query'] = $queryParams;
        }

        if (!empty($pathParams)) {
            $urlParts['variable'] = $pathParams;
        }

        $requestBody = $this->extractRequestBody($operation);

        // Generate code samples
        $codeSamples = $this->generateClientCodeSamples($method, $url, $this->extractRequestBodyData($operation));

        // Build description with code samples
        $description = ($operation['description'] ?? $operation['summary'] ?? '') . "\n\n" . $codeSamples;

        $headers = [
            [
                'key' => 'X-API-KEY',
                'value' => '{{apiKey}}',
                'type' => 'text',
            ],
            [
                'key' => 'Accept',
                'value' => 'application/json',
                'type' => 'text',
            ],
        ];

        if (($requestBody['mode'] ?? null) === 'raw') {
            $headers[] = [
                'key' => 'Content-Type',
                'value' => 'application/json',
                'type' => 'text',
            ];
        }

        $request = [
            'name' => $operation['summary'] ?? $operation['operationId'] ?? strtoupper($method) . ' ' . $path,
            'request' => [
                'method' => strtoupper($method),
                'header' => $headers,
                'url' => $urlParts,
                'description' => $description,
            ],
            'response' => [],
        ];

        // Check if body is empty (null, empty array, or just "[]")
        $bodyIsEmpty = !$requestBody ||
            (isset($requestBody['raw']) && in_array(trim($requestBody['raw']), ['[]', '{}', '']));

        // Fallback: if body is empty but method requires body, use fallback
        if ($bodyIsEmpty && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $fallbackBody = $this->getFallbackBody($cleanPath, $method);
            if ($fallbackBody) {
                $requestBody = [
                    'mode' => 'raw',
                    'raw' => json_encode($fallbackBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'options' => [
                        'raw' => [
                            'language' => 'json',
                        ],
                    ],
                ];
            }
        }

        if ($requestBody && !$bodyIsEmpty) {
            $request['request']['body'] = $requestBody;
        } elseif (isset($requestBody['raw']) && !in_array(trim($requestBody['raw']), ['[]', '{}', ''])) {
            // Body was set from fallback
            $request['request']['body'] = $requestBody;
        }

        // Add example responses
        $exampleResponses = $this->extractExampleResponses($operation, $request['request']);
        if (!empty($exampleResponses)) {
            $request['response'] = $exampleResponses;
        }

        // Add test scripts
        $testScript = $this->generateTestScript($path, $method, $operation);
        if ($testScript) {
            $request['event'] = [
                [
                    'listen' => 'test',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => $testScript,
                    ],
                ],
            ];
        }

        return $request;
    }

    /**
     * Generate code samples cho Flutter/Swift/React Native
     */
    private function generateClientCodeSamples(string $method, string $url, ?array $body = null): string
    {
        $bodyJson = $body ? json_encode($body, JSON_PRETTY_PRINT) : '{}';
        $methodUpper = strtoupper($method);
        $methodLower = strtolower($method);

        // Flutter (Dart/Dio)
        $flutterCode = "```dart\n// Flutter (Dio)\n";
        $flutterCode .= "var response = await dio.request('$url',\n";
        $flutterCode .= "  options: Options(method: '$methodUpper'),\n";
        if ($body) {
            $flutterCode .= "  data: $bodyJson\n";
        }
        $flutterCode .= ");\n```";

        // React Native (Axios)
        $reactCode = "```javascript\n// React Native (Axios)\n";
        if ($body) {
            $reactCode .= "const response = await api.$methodLower('$url', $bodyJson);\n";
        } else {
            $reactCode .= "const response = await api.$methodLower('$url');\n";
        }
        $reactCode .= "console.log(response.data);\n```";

        // iOS (Swift/Alamofire)
        $swiftCode = "```swift\n// iOS (Alamofire)\n";
        $swiftCode .= "AF.request(\"$url\",\n";
        $swiftCode .= "           method: .$methodLower,\n";
        if ($body) {
            $swiftCode .= "           parameters: [:], // Replace with params\n";
            $swiftCode .= "           encoding: JSONEncoding.default,\n";
        }
        $swiftCode .= "           headers: headers)\n";
        $swiftCode .= "  .responseJSON { response in debugPrint(response) }\n```";

        return "### Code Examples\n" . $flutterCode . "\n\n" . $reactCode . "\n\n" . $swiftCode;
    }

    private function extractRequestBodyData(array $operation): ?array
    {
        $content = $operation['requestBody']['content'] ?? [];

        if (isset($content['application/json'])) {
            return $this->extractExample($content['application/json']['schema'] ?? []);
        }

        if (isset($content['multipart/form-data'])) {
            $schema = $this->resolveRef($content['multipart/form-data']['schema'] ?? []);

            if (($schema['type'] ?? null) !== 'object' && !isset($schema['properties'])) {
                return null;
            }

            $example = [];
            foreach ($schema['properties'] ?? [] as $name => $prop) {
                $prop = $this->resolveRef($prop);
                $isBinaryArray = ($prop['type'] ?? null) === 'array'
                    && (($prop['items']['format'] ?? null) === 'binary' || ($prop['items']['type'] ?? null) === 'file');
                $isBinary = ($prop['format'] ?? null) === 'binary' || ($prop['type'] ?? null) === 'file';

                if ($isBinary || $isBinaryArray) {
                    $example[$name] = $isBinaryArray ? ['<attach-file>'] : '<attach-file>';
                    continue;
                }

                $example[$name] = $this->getExampleValue($prop, $name);
            }

            return $example;
        }

        return null;
    }

    private function getParamDefaultValue(array $param): string
    {
        if (isset($param['example'])) {
            return (string) $param['example'];
        }

        if (isset($param['schema']['example'])) {
            return (string) $param['schema']['example'];
        }

        if (isset($param['schema']['default'])) {
            return (string) $param['schema']['default'];
        }

        return '';
    }

    private function resolveRef(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];
            // Ref format: #/components/schemas/SchemaName
            $parts = explode('/', ltrim($ref, '#/'));

            $current = $this->openApiSpec;
            foreach ($parts as $part) {
                if (isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    return []; // Ref not found
                }
            }
            return $current;
        }

        return $schema;
    }

    private function extractRequestBody(array $operation): ?array
    {
        $content = $operation['requestBody']['content'] ?? null;

        if (!$content) {
            return null;
        }

        // Prefer JSON
        $jsonContent = $content['application/json'] ?? null;
        if ($jsonContent) {
            $schema = $jsonContent['schema'] ?? [];

            // Resolve Ref if present
            $schema = $this->resolveRef($schema);

            $example = $this->extractExample($schema);

            return [
                'mode' => 'raw',
                'raw' => json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'options' => [
                    'raw' => [
                        'language' => 'json',
                    ],
                ],
            ];
        }

        // Handle multipart/form-data
        $formContent = $content['multipart/form-data'] ?? null;
        if ($formContent) {
            $schema = $this->resolveRef($formContent['schema'] ?? []);
            $formData = [];
            $properties = $schema['properties'] ?? [];

            foreach ($properties as $name => $prop) {
                $prop = $this->resolveRef($prop);

                $isFile = ($prop['format'] ?? '') === 'binary'
                    || ($prop['type'] ?? '') === 'file'
                    || (
                        ($prop['type'] ?? '') === 'array'
                        && (($prop['items']['format'] ?? '') === 'binary' || ($prop['items']['type'] ?? '') === 'file')
                    );

                $formData[] = [
                    'key' => $name,
                    'value' => $isFile ? '' : ($prop['example'] ?? ''),
                    'type' => $isFile ? 'file' : 'text',
                    'description' => $prop['description'] ?? '',
                ];
            }

            return [
                'mode' => 'formdata',
                'formdata' => $formData,
            ];
        }

        return null;
    }

    /**
     * Extract example responses từ OpenAPI spec
     */
    private function extractExampleResponses(array $operation, array $originalRequest): array
    {
        $responses = [];

        foreach ($operation['responses'] ?? [] as $statusCode => $response) {
            $content = $response['content']['application/json'] ?? null;
            if (!$content) {
                continue;
            }

            $example = $content['example'] ?? $this->extractExample($content['schema'] ?? []);

            $statusName = match ((int)$statusCode) {
                200 => 'OK',
                201 => 'Created',
                204 => 'No Content',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                422 => 'Validation Error',
                500 => 'Server Error',
                default => 'Response',
            };

            $responses[] = [
                'name' => "Example {$statusName}",
                'originalRequest' => $originalRequest,
                'status' => $statusName,
                'code' => (int) $statusCode,
                '_postman_previewlanguage' => 'json',
                'header' => [
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ],
                'body' => json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ];
        }

        return $responses;
    }

    private function extractExample(array $schema): array
    {
        // Resolve ref first
        $schema = $this->resolveRef($schema);

        if (isset($schema['example'])) {
            return (array) $schema['example'];
        }

        // Handle allOf (merge properties)
        if (isset($schema['allOf'])) {
            $example = [];
            foreach ($schema['allOf'] as $subSchema) {
                $example = array_merge($example, $this->extractExample($subSchema));
            }
            return $example;
        }

        if (($schema['type'] ?? '') !== 'object' && !isset($schema['properties'])) {
            return [];
        }

        $example = [];
        $required = $schema['required'] ?? [];

        foreach ($schema['properties'] ?? [] as $name => $prop) {
            // Resolve nested refs
            $prop = $this->resolveRef($prop);

            // Prioritize required fields and fields with examples
            if (in_array($name, $required) || isset($prop['example'])) {
                $example[$name] = $this->getExampleValue($prop, $name);
            } else {
                $example[$name] = $this->getExampleValue($prop, $name);
            }
        }

        return $example;
    }

    private function getExampleValue(array $prop, string $fieldName = ''): mixed
    {
        if (isset($prop['example'])) {
            return $prop['example'];
        }

        $type = $prop['type'] ?? 'string';
        $format = $prop['format'] ?? '';

        // Smart defaults based on field name
        $fieldLower = strtolower($fieldName);

        if (str_contains($fieldLower, 'email')) {
            return 'user@example.com';
        }
        if (str_contains($fieldLower, 'password')) {
            return 'password123';
        }
        if (str_contains($fieldLower, 'phone')) {
            return '0901234567';
        }
        if (str_contains($fieldLower, 'name')) {
            return 'Test Name';
        }
        if (str_contains($fieldLower, 'title')) {
            return 'Test Title';
        }
        if (str_contains($fieldLower, 'description') || str_contains($fieldLower, 'content')) {
            return 'Test description content';
        }
        if (str_contains($fieldLower, 'price') || str_contains($fieldLower, 'amount')) {
            return 1000000;
        }
        if (str_contains($fieldLower, 'area') || str_contains($fieldLower, 'size')) {
            return 100;
        }
        if (str_contains($fieldLower, 'reason')) {
            return 'Lý do mẫu';
        }
        if (str_contains($fieldLower, 'note')) {
            return 'Ghi chú mẫu';
        }

        return match ($type) {
            'string' => match ($format) {
                'email' => 'user@example.com',
                'date' => date('Y-m-d'),
                'date-time' => date('Y-m-d H:i:s'),
                'uri', 'url' => 'https://example.com',
                default => '',
            },
            'integer', 'number' => $prop['minimum'] ?? 1,
            'boolean' => false,
            'array' => [],
            'object' => new \stdClass(),
            default => '',
        };
    }

    private function generateTestScript(string $path, string $method, array $operation): ?array
    {
        $scripts = [
            'pm.test("Status code is successful", function () {',
            '    pm.expect(pm.response.code).to.be.oneOf([200, 201, 204]);',
            '});',
            '',
        ];

        // Special handling for login endpoint
        if (str_contains($path, 'login') && strtoupper($method) === 'POST') {
            $scripts = array_merge($scripts, [
                'pm.test("Response has token", function () {',
                '    var jsonData = pm.response.json();',
                '    var token = jsonData.access_token || (jsonData.data && jsonData.data.token);',
                '    pm.expect(token).to.not.be.undefined;',
                '});',
                '',
                '// Auto-save token',
                'if (pm.response.code === 200) {',
                '    var jsonData = pm.response.json();',
                '    var token = jsonData.access_token || (jsonData.data && jsonData.data.token);',
                '    if (token) {',
                '        pm.collectionVariables.set("token", token);',
                '        pm.environment.set("token", token);',
                '        console.log("✅ Token saved successfully!");',
                '    }',
                '}',
            ]);
        }

        // For list endpoints
        if (strtoupper($method) === 'GET' && !preg_match('/\{.+\}/', $path)) {
            $scripts = array_merge($scripts, [
                'pm.test("Response has data array", function () {',
                '    var jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("data");',
                '});',
            ]);
        }

        // For create endpoints
        if (strtoupper($method) === 'POST' && !str_contains($path, 'login') && !str_contains($path, 'logout')) {
            $scripts = array_merge($scripts, [
                'pm.test("Response has created resource", function () {',
                '    if (pm.response.code === 201) {',
                '        var jsonData = pm.response.json();',
                '        pm.expect(jsonData.data).to.have.property("id");',
                '    }',
                '});',
            ]);
        }

        return $scripts;
    }

    /**
     * Get fallback body for common endpoints when OpenAPI doesn't have requestBody
     */
    private function getFallbackBody(string $path, string $method): ?array
    {
        $pathLower = strtolower($path);

        // Authentication
        if (str_contains($pathLower, '/auth/login') || str_contains($pathLower, '/login')) {
            return [
                'email' => 'admin@example.com',
                'password' => 'password',
                'device_name' => 'MobileApp',
            ];
        }

        if (str_contains($pathLower, '/auth/register') || str_contains($pathLower, '/register')) {
            return [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'phone' => '0901234567',
            ];
        }

        if (str_contains($pathLower, '/forgot-password')) {
            return ['email' => 'user@example.com'];
        }

        if (str_contains($pathLower, '/verify-otp')) {
            return [
                'email' => 'user@example.com',
                'otp' => '123456',
            ];
        }

        if (str_contains($pathLower, '/reset-password')) {
            return [
                'email' => 'user@example.com',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ];
        }

        if (str_contains($pathLower, '/change-password')) {
            return [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword',
                'new_password_confirmation' => 'newpassword',
            ];
        }

        // Properties
        if (str_contains($pathLower, '/properties') && strtoupper($method) === 'POST' && !str_contains($pathLower, 'approve') && !str_contains($pathLower, 'reject')) {
            return [
                'title' => 'Nhà bán quận 1',
                'description' => 'Nhà mặt tiền đường lớn, 4 tầng, 5 phòng ngủ',
                'price' => 5000000000,
                'price_unit' => 'VND',
                'area_size' => 120,
                'category_id' => 1,
                'area_id' => 1,
                'address' => '123 Nguyễn Huệ, Quận 1',
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'owner_name' => 'Nguyễn Văn A',
                'owner_phone' => '0901234567',
            ];
        }

        if (str_contains($pathLower, '/properties') && (strtoupper($method) === 'PUT' || strtoupper($method) === 'PATCH')) {
            return [
                'title' => 'Nhà bán quận 1 - Cập nhật',
                'price' => 5500000000,
            ];
        }

        if (str_contains($pathLower, '/approve')) {
            return [
                'note' => 'Đã kiểm tra thông tin, duyệt tin.',
            ];
        }

        if (str_contains($pathLower, '/reject')) {
            return [
                'reason' => 'Thông tin không chính xác, vui lòng cập nhật lại.',
            ];
        }

        // Posts
        if (str_contains($pathLower, '/posts') && strtoupper($method) === 'POST' && !str_contains($pathLower, 'renew') && !str_contains($pathLower, 'hide')) {
            return [
                'property_id' => 1,
                'title' => 'Tin VIP - Nhà bán Quận 1',
                'visible_until' => date('Y-m-d', strtotime('+30 days')),
            ];
        }

        if (str_contains($pathLower, '/renew')) {
            return ['days' => 30];
        }

        // Phone Requests
        if (str_contains($pathLower, 'owner-phone-request') && strtoupper($method) === 'POST') {
            return ['reason' => 'Muốn liên hệ mua nhà'];
        }

        // Reports
        if (str_contains($pathLower, '/reports') && strtoupper($method) === 'POST' && !str_contains($pathLower, 'resolve')) {
            return [
                'post_id' => 1,
                'reason' => 'SPAM',
                'description' => 'Tin đăng trùng lặp nhiều lần',
            ];
        }

        if (str_contains($pathLower, '/resolve')) {
            return [
                'action' => 'HIDE_POST',
                'admin_note' => 'Đã xử lý, ẩn bài đăng vi phạm.',
            ];
        }

        // Users
        if (str_contains($pathLower, '/admin/users') && strtoupper($method) === 'POST') {
            return [
                'name' => 'Nhân viên mới',
                'email' => 'staff@example.com',
                'password' => 'password123',
                'role' => 'FIELD_STAFF',
            ];
        }

        if (str_contains($pathLower, '/lock')) {
            return [
                'reason' => 'Vi phạm chính sách',
                'duration' => 'permanent',
            ];
        }

        // Profile
        if (str_contains($pathLower, '/me') && (strtoupper($method) === 'PUT' || strtoupper($method) === 'PATCH')) {
            return [
                'name' => 'Tên mới',
                'phone' => '0909123456',
            ];
        }

        // Files
        if (str_contains($pathLower, '/files') && strtoupper($method) === 'POST') {
            // File upload uses formdata, return null here
            return null;
        }

        return null;
    }
}
