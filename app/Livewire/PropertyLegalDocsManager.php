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

class PropertyLegalDocsManager extends Component
{
    use WithFileUploads;

    public Property $property;
    public $newFiles = [];
    public ?int $editingFileId = null;
    public string $editingFileName = '';

    protected $listeners = [
        'refreshFiles' => '$refresh',
    ];

    public function mount(Property $property): void
    {
        $this->property = $property;
    }

    public function getFilesProperty()
    {
        return $this->property->files()
            ->where('purpose', 'LEGAL_DOC')
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
            'newFiles.*' => 'file|max:10240', // 10MB max
        ]);

        if (empty($this->newFiles)) {
            return;
        }

        $maxOrder = $this->property->files()
            ->where('purpose', 'LEGAL_DOC')
            ->max('order') ?? -1;

        foreach ($this->newFiles as $file) {
            $path = $file->store('uploads/properties/legal_docs', 'public');

            File::create([
                'filename' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'thumbnail_path' => null,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'purpose' => 'LEGAL_DOC',
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

    public function render()
    {
        return view('livewire.property-legal-docs-manager', [
            'files' => $this->files,
        ]);
    }
}
