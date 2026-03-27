<?php

declare(strict_types=1);

namespace Tests\Feature\Property;

use App\Models\Area;
use App\Models\Category;
use App\Models\File;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyDirectUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        Role::firstOrCreate(['name' => 'FIELD_STAFF']);
        Permission::firstOrCreate(['name' => 'create_property']);
    }

    #[Test]
    public function user_can_create_property_with_direct_image_and_legal_doc_uploads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        $user->givePermissionTo('create_property');

        $area = Area::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAsApi($user)->post('/api/v1/properties', [
            'title' => 'Nha pho trung tam',
            'description' => 'Mo ta',
            'price' => 1800000000,
            'area' => 95,
            'address' => '123 Nguyen Hue',
            'owner_name' => 'Nguyen Van A',
            'owner_phone' => '0909123456',
            'area_id' => $area->id,
            'category_id' => $category->id,
            'legal_status' => 'SO_DO',
            'property_images' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('living-room.png'),
            ],
            'legal_doc_files' => [
                UploadedFile::fake()->create('so-do.pdf', 120, 'application/pdf'),
            ],
        ]);

        $response->assertCreated();

        $propertyId = (int) $response->json('property_id');

        $this->assertDatabaseHas('properties', [
            'id' => $propertyId,
            'title' => 'Nha pho trung tam',
            'approval_status' => 'PENDING',
        ]);

        $imageFiles = File::query()
            ->where('owner_type', Property::class)
            ->where('owner_id', $propertyId)
            ->where('purpose', 'PROPERTY_IMAGE')
            ->orderBy('order')
            ->get();

        $this->assertCount(2, $imageFiles);
        $this->assertTrue((bool) $imageFiles->first()?->is_primary);

        foreach ($imageFiles as $file) {
            $this->assertSame('PUBLIC', $file->visibility);
            Storage::disk('public')->assertExists($file->path);
        }

        $legalDoc = File::query()
            ->where('owner_type', Property::class)
            ->where('owner_id', $propertyId)
            ->where('purpose', 'SO_DO')
            ->first();

        $this->assertNotNull($legalDoc);
        $this->assertSame('PRIVATE', $legalDoc->visibility);
        Storage::disk('local')->assertExists($legalDoc->path);
    }
}
