<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\File;
use App\Models\Property;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyImagesManager extends Component
{
    use WithFileUploads;

    public Property $property;
    public bool $isViewMode = false;
    public $newImages = [];
    public ?int $editingImageId = null;
    public string $editingImageName = '';

    protected $listeners = [
        'refreshImages' => '$refresh',
    ];

    public function mount(Property $property, bool $isViewMode = false): void
    {
        $this->property = $property;
        $this->isViewMode = $isViewMode;
    }

    public function getImagesProperty()
    {
        return $this->property->files()
            ->where('purpose', 'PROPERTY_IMAGE')
            ->orderBy('is_primary', 'desc')
            ->orderBy('order')
            ->get();
    }

    public function updatedNewImages(): void
    {
        $this->uploadImages();
    }

    /**
     * Upload new images (append mode)
     */
    public function uploadImages(): void
    {
        $this->validate([
            'newImages.*' => 'image|max:5120', // 5MB max
        ]);

        if (empty($this->newImages)) {
            return;
        }

        $maxOrder = $this->property->files()
            ->where('purpose', 'PROPERTY_IMAGE')
            ->max('order') ?? -1;

        $hasPrimary = $this->property->files()
            ->where('purpose', 'PROPERTY_IMAGE')
            ->where('is_primary', true)
            ->exists();

        foreach ($this->newImages as $image) {
            $path = $image->store('uploads/properties/images', 'public');

            File::create([
                'filename' => basename($path),
                'original_name' => $image->getClientOriginalName(),
                'path' => $path,
                'thumbnail_path' => null,
                'mime_type' => $image->getMimeType(),
                'size' => $image->getSize(),
                'purpose' => 'PROPERTY_IMAGE',
                'visibility' => 'PUBLIC',
                'owner_type' => Property::class,
                'owner_id' => $this->property->id,
                'uploaded_by' => Auth::id(),
                'order' => ++$maxOrder,
                'is_primary' => !$hasPrimary,
            ]);

            // Generate thumbnails for gallery
            app(\App\Services\ImageService::class)->makeThumbnail($path, 'thumb');
            app(\App\Services\ImageService::class)->makeThumbnail($path, 'card');

            $hasPrimary = true;
        }

        $this->newImages = [];

        Notification::make()
            ->title('Đã thêm ảnh mới')
            ->success()
            ->send();

        $this->dispatch('refreshImages');
    }

    /**
     * Set image as primary
     */
    public function setPrimary(int $imageId): void
    {
        // Unset all current primaries
        File::where('owner_type', Property::class)
            ->where('owner_id', $this->property->id)
            ->where('purpose', 'PROPERTY_IMAGE')
            ->update(['is_primary' => false]);

        // Set new primary
        File::where('id', $imageId)->update(['is_primary' => true]);

        Notification::make()
            ->title('Đã đặt ảnh chính')
            ->success()
            ->send();
    }

    /**
     * Open edit name modal
     */
    public function openEditModal(int $imageId, string $currentName): void
    {
        $this->editingImageId = $imageId;
        // Show name without extension for editing
        $this->editingImageName = pathinfo($currentName, PATHINFO_FILENAME);
        $this->dispatch('open-modal', id: 'edit-image-name');
    }

    /**
     * Close edit modal
     */
    public function closeEditModal(): void
    {
        $this->editingImageId = null;
        $this->editingImageName = '';
        $this->dispatch('close-modal', id: 'edit-image-name');
    }

    /**
     * Save image name
     */
    public function saveImageName(): void
    {
        $this->validate([
            'editingImageName' => 'required|string|max:255',
        ]);

        if ($this->editingImageId) {
            $file = File::where('id', $this->editingImageId)
                ->where('owner_type', Property::class)
                ->where('owner_id', $this->property->id)
                ->first();

            if ($file) {
                // Get original extension
                $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);

                // Append extension to new name if it's not already there
                $newName = $this->editingImageName;
                if (!empty($extension) && !str_ends_with($newName, '.' . $extension)) {
                    $newName .= '.' . $extension;
                }

                $file->update(['original_name' => $newName]);

                Notification::make()
                    ->title('Đã cập nhật tên ảnh')
                    ->success()
                    ->send();
            }
        }

        $this->closeEditModal();
    }

    /**
     * Delete image
     */
    public function deleteImage(int $imageId): void
    {
        $file = File::where('id', $imageId)
            ->where('owner_type', Property::class)
            ->where('owner_id', $this->property->id)
            ->first();

        if ($file) {
            // Delete from storage
            if (Storage::disk('public')->exists($file->path)) {
                Storage::disk('public')->delete($file->path);
            }
            if ($file->thumbnail_path && Storage::disk('public')->exists($file->thumbnail_path)) {
                Storage::disk('public')->delete($file->thumbnail_path);
            }

            // Delete record
            $file->delete();

            Notification::make()
                ->title('Đã xoá ảnh')
                ->success()
                ->send();
        }
    }

    /**
     * Reorder images (for drag-drop)
     */
    public function reorderImages(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            File::where('id', $id)
                ->where('owner_type', Property::class)
                ->where('owner_id', $this->property->id)
                ->update(['order' => $index]);
        }

        Notification::make()
            ->title('Đã cập nhật thứ tự')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.property-images-manager', [
            'images' => $this->images,
            'isViewMode' => $this->isViewMode,
        ]);
    }
}
