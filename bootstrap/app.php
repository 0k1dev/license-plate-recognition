<?php

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ValidateApiKey::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau.',
            ], 429);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($status >= 500) {
                 \Illuminate\Support\Facades\Log::error('CRITICAL WEB ERROR: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => auth()->id(),
                ]);
            }

            // If not API, and we want to show error on screen
            if (! $request->is('api/*')) {
                // For development or if specifically requested, show a simplified error on screen
                // instead of generic 500.
                if (config('app.debug')) {
                    return null; // Let Laravel show the full debug page
                }

                return response("
                    <div style='padding: 30px; background: #fff5f5; color: #c53030; border: 2px solid #feb2b2; border-radius: 12px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 800px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);'>
                        <h1 style='font-size: 1.5rem; margin-top: 0; color: #9b2c2c; border-bottom: 1px solid #feb2b2; padding-bottom: 10px;'>🚨 HỆ THỐNG GẶP LỖI (ERROR 500)</h1>
                        <p style='font-size: 1rem; line-height: 1.5; background: #fff; padding: 15px; border-radius: 6px; border: 1px dashed #feb2b2;'>
                            <b>Nội dung lỗi:</b> <code style='color: #e53e3e;'>{$e->getMessage()}</code>
                        </p>
                        <div style='margin-top: 20px; font-size: 0.9rem;'>
                            <p><b>Vị trí:</b> <code>{$e->getFile()}</code></p>
                            <p><b>Dòng:</b> <code>{$e->getLine()}</code></p>
                        </div>
                        
                        <div style='background: #f7fafc; padding: 15px; border-radius: 6px; margin-top: 20px; border: 1px solid #edf2f7;'>
                            <p style='margin-top: 0; font-weight: bold; color: #4a5568;'>Gợi ý xử lý:</p>
                            <ul style='color: #4a5568; padding-left: 20px;'>
                                <li>Kiểm tra lại file code vừa sửa theo đường dẫn trên.</li>
                                <li>Đảm bảo các extension (intl, gd, imagick) đã bật trong <b>Logs Hệ thống</b>.</li>
                                <li>Chạy lệnh <code>php artisan config:clear</code> hoặc <code>cache:clear</code> nếu vừa đổi .env.</li>
                            </ul>
                        </div>

                        <div style='margin-top: 25px; display: flex; gap: 10px;'>
                            <button onclick='window.location.reload()' style='padding: 10px 20px; background: #c53030; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;'>🔄 Tải lại trang</button>
                            <a href='/admin' style='padding: 10px 20px; background: #718096; color: white; border: none; border-radius: 6px; text-decoration: none; font-weight: bold;'>🏠 Về trang chủ Admin</a>
                        </div>
                    </div>
                ", 200); // FORCE 200 TO BYPASS HOSTING OVERRIDES
            }

            return response()->json([
                'error' => 'Lỗi máy chủ nội bộ.',
                'message' => $e->getMessage()
            ], $status);
        });
    })->create();
