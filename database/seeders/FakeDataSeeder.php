<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Project;
use App\Models\Category;
use App\Models\Property;
use App\Models\Post;
use App\Models\User;
use App\Models\OwnerPhoneRequest;
use App\Models\Report;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // 1. Get Existing Imported Areas (Provinces)
        // Lấy 5 tỉnh ngẫu nhiên có subdivisions để test
        $provinces = Area::where('level', 'province')
            ->has('children') // Chỉ lấy tỉnh có quận/huyện
            ->inRandomOrder()
            ->limit(5)
            ->get();

        if ($provinces->isEmpty()) {
            $this->command->error('Vui lòng chạy php artisan areas:import-vietnam trước khi seed!');
            return;
        }

        // 2. Create Categories
        $categories = [];
        $catNames = ['Đất nền', 'Nhà phố', 'Biệt thự', 'Căn hộ chung cư', 'Kho bãi', 'Văn phòng'];
        foreach ($catNames as $name) {
            $categories[] = Category::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true
                ]
            );
        }

        // 3. Create Projects
        $projects = [];
        foreach ($provinces as $province) {
            for ($i = 1; $i <= 3; $i++) {
                $name = 'Dự án ' . $faker->company() . ' ' . $province->name;
                $projects[] = Project::create([
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . uniqid(),
                    'area_id' => $province->id, // Project gắn với Tỉnh
                    'description' => $faker->paragraph(),
                ]);
            }
        }

        // 4. Create Users (Staff & Admin)
        $fieldStaffRole = Role::findByName('FIELD_STAFF');
        // $officeAdminRole = Role::findByName('OFFICE_ADMIN');
        // $superAdminRole = Role::findByName('SUPER_ADMIN');

        // Create some Field Staff
        $staffs = [];
        for ($i = 1; $i <= 5; $i++) {
            $staff = User::firstOrCreate(
                ['email' => "staff$i@example.com"],
                [
                    'name' => "Nhân viên $i",
                    'password' => Hash::make('password'),
                    'area_ids' => $faker->randomElements($provinces->pluck('id')->toArray(), rand(1, 2)),
                    'is_locked' => false,
                ]
            );
            if (!$staff->hasRole('FIELD_STAFF')) {
                $staff->assignRole($fieldStaffRole);
            }
            $staffs[] = $staff;
        }

        // 5. Create Properties
        $properties = [];
        $directions = ['Đông', 'Tây', 'Nam', 'Bắc', 'Đông Nam', 'Tây Nam', 'Đông Bắc', 'Tây Bắc'];

        foreach ($staffs as $staff) {
            for ($j = 1; $j <= 10; $j++) {
                $status = $faker->randomElement(['PENDING', 'APPROVED', 'REJECTED']);

                // Chọn ngẫu nhiên 1 Province
                $province = $provinces->random();

                // Chọn ngẫu nhiên 1 Subdivision thuộc Province đó
                $subdivision = $province->children()->inRandomOrder()->first();

                // Chọn ngẫu nhiên Project thuộc Province (nếu có)
                $provinceProjects = collect($projects)->where('area_id', $province->id);
                $project = $provinceProjects->isNotEmpty() && $faker->boolean(50)
                    ? $provinceProjects->random()
                    : null;

                $prop = Property::create([
                    'title' => 'Bán ' . $faker->randomElement($catNames) . ' tại ' . ($subdivision->name ?? $province->name),
                    'description' => $faker->paragraph(3),

                    // Gán đúng cấu trúc 2 cấp
                    'area_id' => $province->id,
                    'subdivision_id' => $subdivision?->id,

                    'project_id' => $project?->id,
                    'category_id' => $faker->randomElement($categories)->id,
                    'address' => $faker->streetAddress() . ', ' . ($subdivision->name ?? ''),
                    'owner_name' => $faker->name(),
                    'owner_phone' => $faker->phoneNumber(),
                    'price' => $faker->numberBetween(1000, 50000) * 1000000,
                    'area' => $faker->numberBetween(40, 500),
                    'bedrooms' => $faker->numberBetween(1, 5),
                    'bathrooms' => $faker->numberBetween(1, 4),
                    'direction' => $faker->randomElement($directions),
                    'approval_status' => $status,
                    'approval_note' => $status === 'REJECTED' ? $faker->sentence() : null,
                    'created_by' => $staff->id,
                    'approved_by' => $status === 'APPROVED' ? User::role('SUPER_ADMIN')->first()?->id : null,
                    'approved_at' => $status === 'APPROVED' ? now() : null,
                ]);
                $properties[] = $prop;

                // Log activity
                AuditLog::create([
                    'actor_id' => $staff->id,
                    'action' => 'CREATE',
                    'target_type' => Property::class,
                    'target_id' => $prop->id,
                    'description' => "Tạo mới BĐS: {$prop->title}",
                ]);
            }
        }

        // 6. Create Posts for Approved Properties
        foreach (collect($properties)->where('approval_status', 'APPROVED') as $prop) {
            if ($faker->boolean(70)) {
                $post = Post::create([
                    'property_id' => $prop->id,
                    'status' => $faker->randomElement(['VISIBLE', 'VISIBLE', 'HIDDEN', 'PENDING']),
                    'visible_until' => now()->addDays(30),
                    'created_by' => $prop->created_by,
                ]);

                AuditLog::create([
                    'actor_id' => $prop->created_by,
                    'action' => 'CREATE',
                    'target_type' => Post::class,
                    'target_id' => $post->id,
                    'description' => "Đăng tin cho BĐS: {$prop->title}",
                ]);
            }
        }

        // 7. Create Owner Phone Requests
        foreach ($staffs as $staff) {
            $reqProps = $faker->randomElements($properties, 5);
            foreach ($reqProps as $p) {
                // Kiểm tra xem đã có request chưa để tránh trùng lặp gây lỗi unique
                $exists = OwnerPhoneRequest::where('property_id', $p->id)
                    ->where('requester_id', $staff->id)
                    ->exists();

                if (!$exists) {
                    $s = $faker->randomElement(['PENDING', 'APPROVED', 'REJECTED']);
                    OwnerPhoneRequest::create([
                        'property_id' => $p->id,
                        'requester_id' => $staff->id,
                        'status' => $s,
                        'reason' => "Cần liên hệ chủ nhà để dẫn khách xem",
                        'reviewed_by' => $s !== 'PENDING' ? User::role('SUPER_ADMIN')->first()?->id : null,
                        'reviewed_at' => $s !== 'PENDING' ? now() : null,
                        'admin_note' => $s === 'REJECTED' ? "Yêu cầu không hợp lệ" : null,
                    ]);
                }
            }
        }

        // 8. Create Reports
        $activePosts = Post::all();
        if ($activePosts->isNotEmpty()) {
            foreach ($staffs as $staff) {
                if ($faker->boolean(40)) {
                    $post = $activePosts->random();
                    Report::create([
                        'reportable_id' => $post->id,
                        'reportable_type' => Post::class,
                        'reporter_id' => $staff->id,
                        'type' => 'SPAM',
                        'content' => $faker->randomElement(['Tin giả', 'Sai giá', 'Đã bán', 'Thông tin không chính xác']),
                        'status' => 'NEW',
                    ]);
                }
            }
        }

        $this->command->info('Đã tạo dữ liệu ảo thành công theo cấu trúc 2 cấp (Province -> Subdivision)!');
    }
}
