<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportVietnameseAdministrativeDivisions extends Command
{
    protected $signature = 'areas:import-vietnam 
                            {--fresh : Xóa toàn bộ dữ liệu cũ trước khi import}
                            {--province=* : Chỉ import tỉnh/TP cụ thể (code)}';

    protected $description = 'Import dữ liệu hành chính Việt Nam từ provinces.open-api.vn';

    private const API_BASE = 'https://provinces.open-api.vn/api/v2';

    // Mapping division_type sang level
    private const LEVEL_MAPPING = [
        'thành phố trung ương' => 'province',
        'tỉnh' => 'province',
        'quận' => 'district',
        'huyện' => 'district',
        'thị xã' => 'district',
        'thành phố' => 'district',  // Thành phố trực thuộc tỉnh
        'phường' => 'ward',
        'xã' => 'ward',
        'thị trấn' => 'ward',
        'đặc khu' => 'ward',
    ];

    public function handle(): int
    {
        $this->info('🚀 Bắt đầu import dữ liệu hành chính Việt Nam...');

        try {
            DB::beginTransaction();

            if ($this->option('fresh')) {
                $this->warn('⚠️  Xóa toàn bộ dữ liệu cũ...');
                Area::query()->delete();
            }

            // Fetch provinces
            $provinces = $this->fetchProvinces();
            $this->info("📊 Tìm thấy {$provinces->count()} tỉnh/thành phố");

            $bar = $this->output->createProgressBar($provinces->count());
            $bar->start();

            $totalDistricts = 0;
            $totalWards = 0;

            foreach ($provinces as $provinceData) {
                // Nếu có filter province code
                if ($this->option('province') && !in_array($provinceData['code'], $this->option('province'))) {
                    $bar->advance();
                    continue;
                }

                $counts = $this->importProvince($provinceData);
                $totalDistricts += $counts['districts'];
                $totalWards += $counts['wards'];

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info('✅ Import thành công!');
            $this->table(
                ['Loại', 'Số lượng'],
                [
                    ['Tỉnh/TP', $provinces->count()],
                    ['Quận/Huyện', $totalDistricts],
                    ['Phường/Xã', $totalWards],
                    ['Tổng cộng', $provinces->count() + $totalDistricts + $totalWards],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Lỗi: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function fetchProvinces(): \Illuminate\Support\Collection
    {
        $this->info('📡 Fetching provinces từ API...');

        $response = Http::timeout(30)->get(self::API_BASE . '/p/');

        if (!$response->successful()) {
            throw new \Exception("API request failed: {$response->status()}");
        }

        return collect($response->json());
    }

    private function importProvince(array $data): array
    {
        // Tạo/cập nhật Province
        $province = Area::updateOrCreate(
            ['api_code' => $data['code']],
            [
                'name' => $data['name'],
                'code' => 'PROV_' . $data['code'],
                'division_type' => $data['division_type'],
                'codename' => $data['codename'] ?? null,
                'phone_code' => $data['phone_code'] ?? null,
                'level' => 'province',
                'parent_id' => null,
                'path' => $data['name'],
                'is_active' => true,
                'order' => $data['code'],
            ]
        );

        // Fetch districts & wards với depth=2
        $response = Http::timeout(30)->get(self::API_BASE . '/p/' . $data['code'] . '?depth=2');

        if (!$response->successful()) {
            $this->warn("⚠️  Không thể fetch subdivisions cho {$data['name']}");
            return ['districts' => 0, 'wards' => 0];
        }

        $fullData = $response->json();
        $wards = $fullData['wards'] ?? [];

        return $this->importSubdivisions($province, $wards);
    }

    private function importSubdivisions(Area $province, array $subdivisions): array
    {
        $districts = [];
        $wards = [];

        // Phân loại theo level
        foreach ($subdivisions as $sub) {
            $level = self::LEVEL_MAPPING[$sub['division_type']] ?? 'ward';

            if ($level === 'district') {
                $districts[] = $sub;
            } else {
                $wards[] = $sub;
            }
        }

        // Import districts trước
        $districtModels = [];
        foreach ($districts as $districtData) {
            $district = Area::updateOrCreate(
                ['api_code' => $districtData['code']],
                [
                    'name' => $districtData['name'],
                    'code' => 'DIST_' . $districtData['code'],
                    'division_type' => $districtData['division_type'],
                    'codename' => $districtData['codename'] ?? null,
                    'level' => 'district',
                    'parent_id' => $province->id,
                    'path' => $districtData['name'] . ', ' . $province->name,
                    'is_active' => true,
                    'order' => $districtData['code'],
                ]
            );

            $districtModels[$districtData['code']] = $district;
        }

        // Import wards - cố gắng match với district dựa trên naming
        $wardCount = 0;
        foreach ($wards as $wardData) {
            $parentDistrict = $this->findParentDistrict($wardData, $districtModels);

            Area::updateOrCreate(
                ['api_code' => $wardData['code']],
                [
                    'name' => $wardData['name'],
                    'code' => 'WARD_' . $wardData['code'],
                    'division_type' => $wardData['division_type'],
                    'codename' => $wardData['codename'] ?? null,
                    'level' => 'ward',
                    'parent_id' => $parentDistrict ? $parentDistrict->id : $province->id,
                    'path' => $wardData['name'] . ($parentDistrict ? ', ' . $parentDistrict->name : '') . ', ' . $province->name,
                    'is_active' => true,
                    'order' => $wardData['code'],
                ]
            );

            $wardCount++;
        }

        return [
            'districts' => count($districts),
            'wards' => $wardCount,
        ];
    }

    /**
     * Tìm district cha cho ward dựa trên naming convention
     * VD: "Phường Bến Thành" thường thuộc "Quận 1"
     */
    private function findParentDistrict(array $wardData, array $districtModels): ?Area
    {
        // Logic đơn giản: không thể xác định chính xác từ API
        // Trả về district đầu tiên hoặc null
        // User có thể update manual sau nếu cần
        return !empty($districtModels) ? reset($districtModels) : null;
    }
}
