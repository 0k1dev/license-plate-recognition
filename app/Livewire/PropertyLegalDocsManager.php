<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\File;
use App\Models\Property;
use App\Support\PropertyOptionResolver;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyLegalDocsManager extends Component
{
    use WithFileUploads;

    public Property $property;
    public bool $isViewMode = false;
    public $newFiles = [];
    public ?int $editingFileId = null;
    public string $editingFileName = '';

    protected $listeners = [
        'refreshFiles' => '$refresh',
    ];

    public function mount(Property $property, bool $isViewMode = false): void
    {
        $this->property = $property;
        $this->isViewMode = $isViewMode;
    }

    public function getFilesProperty()
    {
        return $this->property->files()
            ->whereIn('purpose', PropertyOptionResolver::legalStatusCodes())
            ->orderBy('order')
            ->get();
    }

    public function updatedNewFiles(): void
    {
        $this->uploadFiles();
    }

    /**
     * Upload new files (append mode)
     */
    public function uploadFiles(): void
    {
        $this->validate([
            'newFiles.*' => 'file|max:51200', // 50MB max
        ]);

        if (empty($this->newFiles)) {
            return;
        }

        $maxOrder = $this->property->files()
            ->whereIn('purpose', PropertyOptionResolver::legalStatusCodes())
            ->max('order') ?? -1;

        $legalPurpose = PropertyOptionResolver::isLegalDocumentPurpose($this->property->legal_status)
            ? PropertyOptionResolver::normalizePurpose($this->property->legal_status)
            : (PropertyOptionResolver::defaultLegalPurpose() ?? 'KHAC');

        foreach ($this->newFiles as $file) {
            $path = $file->store('uploads/properties/documents', 'local');

            File::create([
                'filename' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'thumbnail_path' => null,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'purpose' => $legalPurpose,
                'visibility' => 'PRIVATE',
                'owner_type' => Property::class,
                'owner_id' => $this->property->id,
                'uploaded_by' => Auth::id(),
                'order' => ++$maxOrder,
                'is_primary' => false,
            ]);
        }

        $this->newFiles = [];

        Notification::make()
            ->title('Đã thêm tài liệu mới')
            ->success()
            ->send();

        $this->dispatch('refreshFiles');
    }

    /**
     * Open edit name modal
     */
    public function openEditModal(int $fileId, string $currentName): void
    {
        $this->editingFileId = $fileId;
        // Show name without extension for editing
        $this->editingFileName = pathinfo($currentName, PATHINFO_FILENAME);
        $this->dispatch('open-modal', id: 'edit-file-name');
    }

    /**
     * Close edit modal
     */
    public function closeEditModal(): void
    {
        $this->editingFileId = null;
        $this->editingFileName = '';
        $this->dispatch('close-modal', id: 'edit-file-name');
    }

    /**
     * Save file name
     */
    public function saveFileName(): void
    {
        $this->validate([
            'editingFileName' => 'required|string|max:255',
        ]);

        if ($this->editingFileId) {
            $file = File::where('id', $this->editingFileId)
                ->where('owner_type', Property::class)
                ->where('owner_id', $this->property->id)
                ->first();

            if ($file) {
                // Get original extension
                $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);

                // Append extension to new name if it's not already there
                $newName = $this->editingFileName;
                if (!empty($extension) && !str_ends_with($newName, '.' . $extension)) {
                    $newName .= '.' . $extension;
                }

                $file->update(['original_name' => $newName]);

                Notification::make()
                    ->title('Đã cập nhật tên tài liệu')
                    ->success()
                    ->send();
            }
        }

        $this->closeEditModal();
    }

    /**
     * Delete file
     */
    public function deleteFile(int $fileId): void
    {
        $file = File::where('id', $fileId)
            ->where('owner_type', Property::class)
            ->where('owner_id', $this->property->id)
            ->first();

        if ($file) {
            // Delete from storage
            $disk = $file->visibility === 'PRIVATE' ? 'local' : 'public';
            if (Storage::disk($disk)->exists($file->path)) {
                Storage::disk($disk)->delete($file->path);
            }

            // Delete record
            $file->delete();

            Notification::make()
                ->title('Đã xoá tài liệu')
                ->success()
                ->send();
        }
    }

    /**
     * Reorder files (for drag-drop)
     */
    public function reorderFiles(array $orderedIds): void
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

    /**
     * Toggle visibility (PRIVATE <-> PUBLIC)
     */
    public function toggleVisibility(int $fileId): void
    {
        $file = File::where('id', $fileId)
            ->where('owner_type', Property::class)
            ->where('owner_id', $this->property->id)
            ->first();

        if (!$file) return;

        $currentDisk = $file->visibility === 'PRIVATE' ? 'local' : 'public';
        $newVisibility = $file->visibility === 'PRIVATE' ? 'PUBLIC' : 'PRIVATE';
        $newDisk = $newVisibility === 'PRIVATE' ? 'local' : 'public';

        if (Storage::disk($currentDisk)->exists($file->path)) {
            // Move file between disks
            try {
                $stream = Storage::disk($currentDisk)->readStream($file->path);
                $saved = Storage::disk($newDisk)->put($file->path, $stream);

                if ($saved) {
                    Storage::disk($currentDisk)->delete($file->path);
                    $file->update(['visibility' => $newVisibility]);

                    Notification::make()
                        ->title('Đã chuyển thành ' . ($newVisibility === 'PRIVATE' ? 'Riêng tư' : 'Công khai'))
                        ->success()
                        ->send();
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Lỗi khi chuyển file: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            // File not found in expected disk, just update DB to fix state if needed or warn
            Notification::make()
                ->title('File gốc không tìm thấy')
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.property-legal-docs-manager', [
            'files' => $this->files,
            'isViewMode' => $this->isViewMode,
        ]);
    }
}
