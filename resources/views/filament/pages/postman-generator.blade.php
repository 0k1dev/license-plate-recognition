<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Hướng dẫn sử dụng
            </x-slot>

            <div class="prose max-w-none dark:prose-invert text-sm">
                <p>
                    Công cụ này giúp bạn tạo ra file Collection chuẩn cho <strong>Postman</strong> để test toàn bộ API của hệ thống.
                </p>
                <p class="text-xs text-green-600 dark:text-green-400">
                    ✅ <strong>Đồng bộ tự động</strong> với API Documentation (/docs/api)
                </p>
                <ol>
                    <li>Nhấn nút <strong>"Download Collection JSON"</strong> ở góc trên bên phải.</li>
                    <li>Mở ứng dụng <strong>Postman</strong>.</li>
                    <li>Chọn <strong>Import</strong> -> Upload file JSON vừa tải về.</li>
                    <li>Set biến <code>baseUrl</code> = URL server của bạn (Ví dụ: <code>http://192.168.1.27:8000/api/v1</code>).</li>
                    <li>Login trước - Token sẽ được <strong>tự động lưu</strong>.</li>
                </ol>
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-xs font-semibold uppercase text-gray-500 mb-2">Lưu ý khi Test Mobile App:</p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 list-disc list-inside">
                        <li>Collection đã được config sẵn biến <code>baseUrl</code> trỏ tới IP LAN (192.168.1.27).</li>
                        <li>Đảm bảo điện thoại và máy tính (server) cùng chung mạng Wifi.</li>
                        <li>Nếu IP máy tính thay đổi, bạn cần update lại biến <code>baseUrl</code> trong Postman.</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>