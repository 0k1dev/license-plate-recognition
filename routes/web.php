<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::name('login')->get('login', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/property-legal-docs/{file}', [App\Http\Controllers\PropertyLegalDocsController::class, 'show'])
    ->name('property.legal-docs.show')
    ->middleware('auth');

// ===== Email Preview (chỉ trên môi trường development) =====
if (config('app.debug')) {
    Route::get('/email-preview/{template}', function (string $template) {
        $user = App\Models\User::first() ?? new App\Models\User(['name' => 'Nguyễn Văn A', 'email' => 'test@example.com']);
        $property = App\Models\Property::first() ?? new App\Models\Property([
            'title' => 'Căn hộ cao cấp 3PN Vinhomes Central Park',
            'address' => '720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM',
            'price' => 5500000000,
            'contact_name' => 'Trần Thị B',
            'owner_phone' => '0901234567',
        ]);

        $data = [
            'user' => $user,
            'userName' => $user->name,
            'userEmail' => $user->email ?? 'test@example.com',
            'property' => $property,
            'propertyTitle' => $property->title,
            'propertyAddress' => $property->address ?? '',
            'propertyPrice' => number_format(5500000000),
            'otp' => '482916',
            'expiresIn' => 5,
            'name' => $user->name,
            'ttl' => 10,
            'reason' => 'Hình ảnh không rõ ràng, thiếu thông tin pháp lý. Vui lòng bổ sung ảnh thực tế và giấy tờ chứng nhận quyền sở hữu.',
            'ownerName' => 'Trần Thị B',
            'ownerPhone' => '0901234567',
        ];

        $validTemplates = ['otp', 'password-reset-otp', 'property-approved', 'property-rejected', 'phone-request-approved'];

        if (!in_array($template, $validTemplates)) {
            return '<h2>Available templates:</h2><ul>' . collect($validTemplates)->map(fn($t) => "<li><a href=\"/email-preview/{$t}\">{$t}</a></li>")->join('') . '</ul>';
        }

        return view("emails.{$template}", $data);
    })->name('email.preview');
}
