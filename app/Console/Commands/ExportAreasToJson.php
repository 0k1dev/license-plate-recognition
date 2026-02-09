<?php

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportAreasToJson extends Command
{
    protected $signature = 'app:export-areas';
    protected $description = 'Export Areas to JSON for fast loading';

    public function handle()
    {
        $this->info('Exporting Subdivisions...');

        // Query chỉ lấy cột cần thiết
        $data = Area::query()
            ->with(['parent:id,name']) // Load parent name
            ->whereIn('level', ['district', 'ward'])
            ->select(['id', 'name', 'parent_id', 'division_type', 'level', 'is_active', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'parent_name' => $item->parent->name ?? '',
                    'division_type' => $item->division_type,
                    'level' => $item->level == 'district' ? 'Quận/Huyện' : 'Phường/Xã',
                    'is_active' => $item->is_active,
                    'updated_at' => $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '',
                ];
            });

        $dir = public_path('data');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path = public_path('data/subdivisions.json');
        File::put($path, $data->toJson(JSON_UNESCAPED_UNICODE));

        $this->info("Exported " . $data->count() . " subdivisions to {$path}");

        // --- Export Provinces ---
        $this->info('Exporting Provinces...');

        $provinces = Area::query()
            ->where('level', 'province')
            ->select(['id', 'name', 'code', 'api_code', 'is_active', 'updated_at', 'order'])
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                    'api_code' => $item->api_code,
                    'is_active' => $item->is_active,
                    'updated_at' => $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '',
                ];
            });

        $pathProvince = public_path('data/provinces.json');
        File::put($pathProvince, $provinces->toJson(JSON_UNESCAPED_UNICODE));

        $this->info("Exported " . $provinces->count() . " provinces to {$pathProvince}");
    }
}
