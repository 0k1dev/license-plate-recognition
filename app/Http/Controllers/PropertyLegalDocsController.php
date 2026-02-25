<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PropertyLegalDocsController extends Controller
{
    public function show(File $file)
    {
        if ($file->purpose !== 'LEGAL_DOC' || $file->visibility !== 'PRIVATE') {
            abort(404);
        }

        // Check ownership/permissions
        // Only Admin, Office Admin, or Creator can view
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$file->owner || !($file->owner instanceof \App\Models\Property)) {
            abort(404);
        }

        $property = $file->owner;

        if (!$user) abort(403);

        // Authorization Logic
        // Can be moved to Policy
        if (
            $user->id === $property->created_by ||
            $user->isSuperAdmin() ||
            $user->isOfficeAdmin()
        ) {

            $path = $file->path;

            // Check if file exists on 'local' disk
            if (!Storage::disk('local')->exists($path)) {
                abort(404);
            }

            $absolutePath = Storage::disk('local')->path($path);
            return response()->file($absolutePath);
        }

        abort(403);
    }
}
