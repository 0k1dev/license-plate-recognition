<?php

// declare(strict_types=1);

// namespace App\Filament\Pages;

// use App\Console\Support\OpenApiToPostmanConverter;
// use Filament\Pages\Page;
// use Filament\Actions\Action;
// use Filament\Notifications\Notification;
// use Illuminate\Support\Facades\Log;

// class PostmanGenerator extends Page
// {
//     protected static ?string $navigationIcon = 'heroicon-o-bolt';
//     protected static ?string $navigationLabel = 'Postman API Docs';
//     protected static ?string $title = 'Generate Postman Collection';
//     protected static ?string $slug = 'postman-generator';
//     protected static ?int $navigationSort = 100;

//     protected static string $view = 'filament.pages.postman-generator';

//     public array $collectionStats = [];
//     public string $lastGenerated = '';
//     public bool $isServerRunning = true;

//     public static function canAccess(): bool
//     {
//         /** @var \App\Models\User $user */
//         $user = auth()->user();
//         return auth()->check() && $user->isSuperAdmin();
//     }

//     public function mount(): void
//     {
//         // Skip server check to avoid Deadlock/Layout issues on 'php artisan serve'
//         $this->isServerRunning = true;
//     }

//     private function checkServerStatus(): void
//     {
//         // Disabled to prevent layout break
//     }

//     protected function getHeaderActions(): array
//     {
//         return [
//             Action::make('download_json')
//                 ->label('Download Collection JSON')
//                 ->icon('heroicon-o-arrow-down-tray')
//                 ->color('primary')
//                 ->action(function () {
//                     try {
//                         $converter = new OpenApiToPostmanConverter();
//                         // 1. Fetch from Localhost (Fast & Reliable via Internal Request logic in converter)
//                         // 2. Set Postman Variable to LAN IP (For Mobile App)
//                         $converter->generate('http://127.0.0.1:8000', 'http://192.168.1.27:8000');

//                         $stats = $converter->getStats();
//                         $filename = 'BDS_API_' . date('Y-m-d_His') . '.postman_collection.json';

//                         // Save to storage
//                         $filePath = $converter->saveToFile($filename);

//                         Notification::make()
//                             ->title('Collection Generated!')
//                             ->body("Generated {$stats['endpoints']} endpoints in {$stats['folders']} folders")
//                             ->success()
//                             ->send();

//                         // Return download response
//                         return response()->download($filePath, $filename, [
//                             'Content-Type' => 'application/json',
//                             'Content-Disposition' => 'attachment; filename="' . $filename . '"',
//                         ]);
//                     } catch (\Exception $e) {
//                         Log::error("Postman Generation Error: " . $e->getMessage());

//                         Notification::make()
//                             ->title('Generation Failed')
//                             ->body($e->getMessage())
//                             ->danger()
//                             ->send();

//                         return null;
//                     }
//                 }),

//             Action::make('view_api_docs')
//                 ->label('View API Docs')
//                 ->icon('heroicon-o-document-text')
//                 ->color('gray')
//                 ->url('/docs/api')
//                 ->openUrlInNewTab(),
//         ];
//     }

//     public function getCollectionPreview(): array
//     {
//         // Return empty to avoid Deadlock/Layout issues on dev environment
//         // Preview is not critical, Download is the main feature.
//         return [];
//     }
// }
