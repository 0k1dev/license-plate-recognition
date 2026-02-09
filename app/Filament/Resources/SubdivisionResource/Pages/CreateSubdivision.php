<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubdivisionResource\Pages;

use App\Filament\Resources\SubdivisionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubdivision extends CreateRecord
{
    protected static string $resource = SubdivisionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Tự động set level dựa trên division_type nếu người dùng chọn
        // Mặc dù form đã có field level, ta có thể bổ sung logic này để chắc chắn
        $districtTypes = ['quận', 'huyện', 'thị xã', 'thành phố'];
        $wardTypes = ['phường', 'xã', 'thị trấn'];

        if (in_array(mb_strtolower($data['division_type']), $districtTypes)) {
            $data['level'] = 'district';
        } elseif (in_array(mb_strtolower($data['division_type']), $wardTypes)) {
            $data['level'] = 'ward';
        }

        return $data;
    }
}
