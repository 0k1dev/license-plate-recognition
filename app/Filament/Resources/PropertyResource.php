<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use App\Models\Area;
use App\Services\PropertyService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PropertyResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $activeNavigationIcon = 'heroicon-s-home-modern';

    protected static ?string $modelLabel = 'Bất động sản';
    protected static ?string $pluralModelLabel = 'Danh sách BĐS';
    protected static ?string $navigationLabel = 'Bất động sản';
    protected static ?string $navigationGroup = 'Quản lý BĐS';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('approval_status', ApprovalStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['areaLocation', 'project', 'category', 'creator', 'approver', 'myApprovedPhoneRequest']);

        /** @var User $user */
        $user = Auth::user();
        return $query->withinUserAreas($user);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('PropertyTabs')
                    ->tabs([
                        // TAB 1: Thông tin cơ bản
                        Forms\Components\Tabs\Tab::make('Thông tin cơ bản')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Forms\Components\Section::make('Tiêu đề & Mô tả')
                                    ->icon('heroicon-m-pencil-square')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Tiêu đề')
                                            ->placeholder('VD: Căn hộ 2PN view sông Sài Gòn')
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('description')
                                            ->required()
                                            ->rows(4)
                                            ->label('Mô tả chi tiết')
                                            ->placeholder('Mô tả chi tiết về bất động sản...')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Phân loại')
                                    ->icon('heroicon-m-tag')
                                    ->collapsible()
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('area_id')
                                            ->options(\App\Models\Area::getCachedProvincesOptions())
                                            ->required()
                                            ->searchable()
                                            ->label('Khu vực')
                                            ->prefixIcon('heroicon-m-map-pin'),
                                        Forms\Components\Select::make('project_id')
                                            ->options(\App\Models\Project::getCachedOptions())
                                            ->searchable()
                                            ->label('Dự án')
                                            ->prefixIcon('heroicon-m-building-office-2'),
                                        Forms\Components\Select::make('category_id')
                                            ->options(\App\Models\Category::getCachedOptions())
                                            ->searchable()
                                            ->preload()
                                            ->label('Danh mục')
                                            ->prefixIcon('heroicon-m-squares-2x2'),
                                    ]),
                            ]),

                        // TAB 2: Vị trí
                        Forms\Components\Tabs\Tab::make('Vị trí')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Địa chỉ')
                                    ->icon('heroicon-m-home')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('address')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Địa chỉ đầy đủ')
                                            ->placeholder('Số nhà, đường...')
                                            ->prefixIcon('heroicon-m-map-pin')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('area_id')
                                            ->label('Tỉnh/Thành phố')
                                            ->options(\App\Models\Area::getCachedProvincesOptions())
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                // Clear subdivision when province changes
                                                $set('subdivision_id', null);
                                            })
                                            ->placeholder('Chọn tỉnh/thành phố')
                                            ->helperText('Chọn tỉnh/thành phố trước khi chọn quận/huyện/phường/xã')
                                            ->prefixIcon('heroicon-m-map'),

                                        Forms\Components\Select::make('subdivision_id')
                                            ->label('Quận/Huyện/Phường/Xã')
                                            ->options(function (Forms\Get $get) {
                                                $areaId = $get('area_id');
                                                if (!$areaId) {
                                                    return [];
                                                }
                                                // Sử dụng cache để lấy danh sách quận huyện
                                                return \App\Models\Area::getCachedSubdivisionsOptions((int)$areaId);
                                            })
                                            ->searchable()
                                            ->disabled(fn(Forms\Get $get) => !$get('area_id'))
                                            ->placeholder(
                                                fn(Forms\Get $get) =>
                                                $get('area_id') ? 'Chọn quận/huyện/phường/xã' : 'Chọn tỉnh/TP trước'
                                            )
                                            ->helperText(
                                                fn(Forms\Get $get) =>
                                                !$get('area_id') ? 'Vui lòng chọn tỉnh/thành phố trước' : null
                                            )
                                            ->prefixIcon('heroicon-m-building-office-2'),
                                        Forms\Components\TextInput::make('road_width')
                                            ->label('Độ rộng đường vào')
                                            ->numeric()
                                            ->suffix('m')
                                            ->placeholder('VD: 4')
                                            ->prefixIcon('heroicon-m-arrows-right-left'),
                                        Forms\Components\Select::make('location_type')
                                            ->label('Vị trí')
                                            ->options([
                                                'Mặt tiền' => 'Mặt tiền',
                                                'Ngõ hẻm' => 'Ngõ hẻm',
                                                'Trong ngõ' => 'Trong ngõ',
                                                'Trong khu dân cư' => 'Trong khu dân cư',
                                            ])
                                            ->placeholder('Chọn vị trí')
                                            ->prefixIcon('heroicon-m-map'),
                                        Forms\Components\TextInput::make('google_map_url')
                                            ->label('Google Maps Link')
                                            ->placeholder('VD: https://www.google.com/maps/place/...')
                                            ->prefixIcon('heroicon-m-link')
                                            ->columnSpanFull()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (?string $state, Forms\Set $set) {

                                                if (!$state) return;

                                                // Handle Iframe input: extract src
                                                if (str_contains($state, '<iframe') && preg_match('/src="([^"]+)"/', $state, $matches)) {
                                                    $state = $matches[1];
                                                    $set('google_map_url', $state); // Update field with clean URL
                                                }

                                                // Pattern 1: /@lat,lng,
                                                if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $state, $matches)) {
                                                    $set('lat', $matches[1]);
                                                    $set('lng', $matches[2]);
                                                    $set('location_map', ['lat' => (float)$matches[1], 'lng' => (float)$matches[2]]);
                                                    return;
                                                }

                                                // Pattern 2: ?q=lat,lng
                                                if (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $state, $matches)) {
                                                    $set('lat', $matches[1]);
                                                    $set('lng', $matches[2]);
                                                    $set('location_map', ['lat' => (float)$matches[1], 'lng' => (float)$matches[2]]);
                                                    return;
                                                }

                                                // Pattern 3: Embed format pb=...!2d{lng}!3d{lat}...
                                                // Note: Google uses !2d for Longitude and !3d for Latitude in embed strings
                                                if (preg_match('/!2d(-?\d+\.\d+)!3d(-?\d+\.\d+)/', $state, $matches)) {
                                                    $set('lng', $matches[1]);
                                                    $set('lat', $matches[2]);
                                                    $set('location_map', ['lat' => (float)$matches[2], 'lng' => (float)$matches[1]]);
                                                    return;
                                                }

                                                // Pattern 4: 3dlat!4dlng (old pattern or long URL)
                                                if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $state, $matches)) {
                                                    $set('lat', $matches[1]);
                                                    $set('lng', $matches[2]);
                                                    $set('location_map', ['lat' => (float)$matches[1], 'lng' => (float)$matches[2]]);
                                                    return;
                                                }
                                            }),
                                    ]),

                                Forms\Components\Section::make('Tọa độ & Bản đồ')
                                    ->icon('heroicon-m-globe-alt')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('lat')
                                                    ->numeric()
                                                    ->label('Vĩ độ (Latitude)')
                                                    ->live(onBlur: true)
                                                    ->prefixIcon('heroicon-m-arrow-up'),
                                                Forms\Components\TextInput::make('lng')
                                                    ->numeric()
                                                    ->label('Kinh độ (Longitude)')
                                                    ->live(onBlur: true)
                                                    ->prefixIcon('heroicon-m-arrow-right'),
                                            ]),
                                        Forms\Components\Placeholder::make('location_map_preview')
                                            ->label('Bản đồ')
                                            ->content(fn(Forms\Get $get) => view('filament.forms.components.google-map-embed', [
                                                'lat' => $get('lat'),
                                                'lng' => $get('lng'),
                                                'url' => $get('google_map_url'),
                                            ]))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 3: Thông tin chủ nhà
                        Forms\Components\Tabs\Tab::make('Chủ nhà')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Forms\Components\Section::make('Thông tin liên hệ')
                                    ->icon('heroicon-m-phone')
                                    ->description('Thông tin chủ sở hữu bất động sản')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('owner_name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Tên chủ nhà')
                                            ->placeholder('Nguyễn Văn A')
                                            ->prefixIcon('heroicon-m-user'),
                                        Forms\Components\TextInput::make('owner_phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Số điện thoại')
                                            ->placeholder('0912345678')
                                            ->prefixIcon('heroicon-m-phone')
                                            ->helperText('Chỉ Admin và người đăng mới thấy SĐT')
                                            ->formatStateUsing(function ($state, ?Model $record) {
                                                if (!$state || !$record) return $state;
                                                /** @var User $user */
                                                $user = Auth::user();
                                                if ($user->hasRole('FIELD_STAFF')) {
                                                    // Allow if Creator
                                                    if ($record->created_by === $user->id) return $state;

                                                    // Allow if Approved Request
                                                    if ($record->myApprovedPhoneRequest) return $state;

                                                    return substr($state, 0, 3) . '****' . substr($state, -3);
                                                }
                                                return $state;
                                            }),
                                    ]),
                            ]),

                        // TAB 4: Giá & Chi tiết
                        Forms\Components\Tabs\Tab::make('Giá & Chi tiết')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Giá cả')
                                    ->icon('heroicon-m-banknotes')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('price')
                                            ->required()
                                            ->numeric()
                                            ->suffix('VNĐ')
                                            ->label('Giá')
                                            ->placeholder('VD: 1,500,000,000')
                                            ->live(onBlur: true)
                                            ->helperText(function ($state) {
                                                if (!$state) return null;
                                                $n = (float) preg_replace('/[^0-9.]/', '', (string)$state);

                                                if ($n >= 1_000_000_000) {
                                                    return number_format($n / 1_000_000_000, 2, ',', '.') . ' tỷ';
                                                } elseif ($n >= 1_000_000) {
                                                    return number_format($n / 1_000_000, 0, ',', '.') . ' triệu';
                                                }
                                                return number_format($n, 0, ',', '.') . ' đồng';
                                            }),
                                        Forms\Components\TextInput::make('area')
                                            ->required()
                                            ->numeric()
                                            ->suffix('m²')
                                            ->label('Diện tích'),
                                        Forms\Components\TextInput::make('width')
                                            ->numeric()
                                            ->suffix('m')
                                            ->label('Chiều ngang')
                                            ->placeholder('VD: 5'),
                                        Forms\Components\TextInput::make('length')
                                            ->numeric()
                                            ->suffix('m')
                                            ->label('Chiều dài')
                                            ->placeholder('VD: 20'),
                                        Forms\Components\Select::make('shape')
                                            ->label('Hình dạng đất')
                                            ->options([
                                                'Vuông vức' => 'Vuông vức',
                                                'Chữ nhật' => 'Chữ nhật',
                                                'Tóp hậu' => 'Tóp hậu',
                                                'Nở hậu' => 'Nở hậu',
                                                'Không thường xuyên' => 'Không thường xuyên',
                                            ])
                                            ->placeholder('Chọn hình dạng')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Chi tiết căn hộ')
                                    ->icon('heroicon-m-home-modern')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('bedrooms')
                                            ->numeric()
                                            ->label('Số phòng ngủ')
                                            ->prefixIcon('heroicon-m-moon')
                                            ->placeholder('2'),
                                        Forms\Components\TextInput::make('bathrooms')
                                            ->numeric()
                                            ->label('Số WC')
                                            ->prefixIcon('heroicon-m-beaker')
                                            ->placeholder('2'),
                                        Forms\Components\Select::make('direction')
                                            ->options([
                                                'Đông' => 'Đông',
                                                'Tây' => 'Tây',
                                                'Nam' => 'Nam',
                                                'Bắc' => 'Bắc',
                                                'Đông Nam' => 'Đông Nam',
                                                'Đông Bắc' => 'Đông Bắc',
                                                'Tây Nam' => 'Tây Nam',
                                                'Tây Bắc' => 'Tây Bắc',
                                            ])
                                            ->label('Hướng nhà')
                                            ->prefixIcon('heroicon-m-arrow-trending-up'),
                                        Forms\Components\TextInput::make('floor')
                                            ->label('Tầng')
                                            ->prefixIcon('heroicon-m-building-office')
                                            ->placeholder('5'),
                                        Forms\Components\TextInput::make('year_built')
                                            ->numeric()
                                            ->label('Năm xây dựng')
                                            ->minValue(1900)
                                            ->maxValue(date('Y'))
                                            ->placeholder('VD: 2020')
                                            ->prefixIcon('heroicon-m-calendar')
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        // TAB 5: Pháp lý
                        Forms\Components\Tabs\Tab::make('Pháp lý')
                            ->icon('heroicon-m-document-check')
                            ->schema([
                                Forms\Components\Section::make('Tình trạng pháp lý')
                                    ->icon('heroicon-m-clipboard-document-check')
                                    ->schema([
                                        Forms\Components\Select::make('legal_status')
                                            ->options([
                                                'SO_DO' => 'Sổ đỏ/Sổ hồng',
                                                'HOP_DONG_MB' => 'Hợp đồng mua bán',
                                                'VI_BANG' => 'Vi bằng',
                                                'CHO_SO' => 'Chờ sổ',
                                                'KHAC' => 'Khác',
                                            ])
                                            ->label('Loại giấy tờ')
                                            ->prefixIcon('heroicon-m-document'),
                                        Forms\Components\Textarea::make('legal_docs')
                                            ->columnSpanFull()
                                            ->rows(3)
                                            ->label('Ghi chú pháp lý')
                                            ->placeholder('Thông tin chi tiết về giấy tờ pháp lý...')
                                            ->visible(
                                                function ($record) {
                                                    /** @var User $user */
                                                    $user = Auth::user();
                                                    if (!$user) return false;
                                                    return !$record ||
                                                        $user->isSuperAdmin() ||
                                                        $user->isOfficeAdmin() ||
                                                        $user->id === $record->created_by;
                                                }
                                            ),
                                    ]),
                            ]),

                        // TAB 5.5: Tiện ích xung quanh
                        Forms\Components\Tabs\Tab::make('Tiện ích')
                            ->icon('heroicon-m-star')
                            ->schema([
                                Forms\Components\Section::make('Tiện ích xung quanh')
                                    ->icon('heroicon-m-map')
                                    ->description('Chọn các tiện ích gần bất động sản')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('amenities')
                                            ->label('Tiện ích')
                                            ->options([
                                                'Siêu thị' => 'Siêu thị',
                                                'Chợ' => 'Chợ',
                                                'Trường học' => 'Trường học',
                                                'Bệnh viện' => 'Bệnh viện',
                                                'Công viên' => 'Công viên',
                                                'Sân bay' => 'Sân bay',
                                                'Ga tàu' => 'Ga tàu',
                                                'Bến xe' => 'Bến xe',
                                                'Ngân hàng' => 'Ngân hàng',
                                                'ATM' => 'ATM',
                                                'Nhà hàng' => 'Nhà hàng',
                                                'Quán cafe' => 'Quán cafe',
                                                'Trung tâm thương mại' => 'Trung tâm thương mại',
                                                'Thư viện' => 'Thư viện',
                                                'Rạp chiếu phim' => 'Rạp chiếu phim',
                                                'Sân thể thao' => 'Sân thể thao',
                                                'Bể bơi' => 'Bể bơi',
                                                'Trạm xăng' => 'Trạm xăng',
                                            ])
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->searchable()
                                            ->bulkToggleable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 6: Phê duyệt (chỉ Admin thấy)
                        Forms\Components\Tabs\Tab::make('Phê duyệt')
                            ->icon('heroicon-m-check-badge')
                            ->visible(function () {
                                /** @var User $user */
                                $user = Auth::user();
                                return $user && ($user->isSuperAdmin() || $user->isOfficeAdmin());
                            })
                            ->schema([
                                Forms\Components\Section::make('Trạng thái duyệt')
                                    ->icon('heroicon-m-shield-check')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('approval_status')
                                            ->options(ApprovalStatus::options())
                                            ->required()
                                            ->default(ApprovalStatus::PENDING->value)
                                            ->label('Trạng thái')
                                            ->prefixIcon('heroicon-m-flag'),
                                        Forms\Components\Select::make('created_by')
                                            ->relationship('creator', 'name')
                                            ->disabled()
                                            ->label('Người tạo')
                                            ->prefixIcon('heroicon-m-user'),
                                        Forms\Components\Textarea::make('approval_note')
                                            ->columnSpanFull()
                                            ->rows(2)
                                            ->label('Ghi chú duyệt')
                                            ->placeholder('Lý do duyệt hoặc từ chối...'),
                                        Forms\Components\Select::make('approved_by')
                                            ->relationship('approver', 'name')
                                            ->disabled()
                                            ->label('Người duyệt')
                                            ->prefixIcon('heroicon-m-user-circle'),
                                        Forms\Components\DateTimePicker::make('approved_at')
                                            ->disabled()
                                            ->label('Ngày duyệt')
                                            ->displayFormat('d/m/Y H:i'),
                                    ]),
                            ]),

                        // TAB 7: Hình ảnh
                        Forms\Components\Tabs\Tab::make('Hình ảnh & Media')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                // Section: Video
                                Forms\Components\Section::make('Video')
                                    ->icon('heroicon-m-video-camera')
                                    ->description('Link video Youtube hoặc TikTok giới thiệu bất động sản')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\TextInput::make('video_url')
                                            ->label('Link Video')
                                            ->url()
                                            ->placeholder('https://www.youtube.com/watch?v=... hoặc https://www.tiktok.com/@...')
                                            ->prefixIcon('heroicon-m-video-camera')
                                            ->helperText('Hỗ trợ Youtube và TikTok')
                                            ->columnSpanFull(),
                                    ]),

                                // Section: Quản lý hình ảnh
                                Forms\Components\Section::make('Quản lý hình ảnh')
                                    ->icon('heroicon-m-photo')
                                    ->description('Upload và quản lý ảnh BĐS. Kéo thả để sắp xếp, chọn ảnh chính.')
                                    ->schema([
                                        Forms\Components\Placeholder::make('filepond_css')
                                            ->label('')
                                            ->content(new HtmlString('
                                                <style>
                                                    .filepond--item {
                                                        width: calc(25% - 0.5em) !important;
                                                    }
                                                    @media (max-width: 1024px) { .filepond--item { width: calc(33.33% - 0.5em) !important; } }
                                                    @media (max-width: 768px) { .filepond--item { width: calc(50% - 0.5em) !important; } }
                                                </style>
                                            ')),
                                        // CREATE MODE: File Upload
                                        Forms\Components\FileUpload::make('new_property_images')
                                            ->label('Upload ảnh')
                                            ->disk('public')
                                            ->directory('uploads/properties/images')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->maxFiles(10)
                                            ->maxSize(5120)
                                            ->panelLayout('grid')
                                            ->imagePreviewHeight('160')
                                            ->helperText('Chọn tối đa 10 ảnh/lần, mỗi ảnh ≤ 5MB.')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'])
                                            ->visible(fn($record) => $record === null)
                                            ->saveRelationshipsUsing(function (Property $record, $state) {
                                                if (empty($state)) return;
                                                $order = 0;
                                                foreach ($state as $path) {
                                                    $record->files()->create([
                                                        'filename' => basename($path),
                                                        'original_name' => basename($path),
                                                        'path' => $path,
                                                        'purpose' => 'PROPERTY_IMAGE',
                                                        'visibility' => 'PUBLIC',
                                                        'uploaded_by' => Auth::id(),
                                                        'order' => $order++,
                                                        'is_primary' => $order === 1,
                                                        'mime_type' => Storage::disk('public')->exists($path) ? (method_exists(Storage::disk('public'), 'mimeType') ? Storage::disk('public')->mimeType($path) : 'image/jpeg') : 'image/jpeg',
                                                        'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : 0,
                                                    ]);

                                                    // Generate thumbnails
                                                    app(\App\Services\ImageService::class)->makeThumbnail($path, 'thumb');
                                                    app(\App\Services\ImageService::class)->makeThumbnail($path, 'card');
                                                }
                                            }),

                                        // EDIT/VIEW MODE: Livewire Manager
                                        Forms\Components\Livewire::make('property-images-manager')
                                            ->lazy()
                                            ->key(fn($record) => 'images-manager-' . ($record?->id ?? 'new'))
                                            ->data(fn($record, string $operation) => [
                                                'property' => $record,
                                                'isViewMode' => $operation === 'view',
                                            ])
                                            ->visible(fn($record) => $record !== null),
                                    ]),

                                // Section: Tài liệu pháp lý
                                Forms\Components\Section::make('Tài liệu pháp lý')
                                    ->icon('heroicon-m-document-text')
                                    ->description('Upload giấy tờ pháp lý (Chỉ Admin và người đăng thấy).')
                                    ->schema([
                                        // CREATE MODE: File Upload
                                        Forms\Components\FileUpload::make('new_legal_documents')
                                            ->label('Upload tài liệu')
                                            ->disk('local')
                                            ->visibility('private')
                                            ->directory('uploads/properties/legal_docs')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->maxSize(10240)
                                            ->panelLayout('grid')
                                            ->imagePreviewHeight('160')
                                            ->helperText('PDF, JPG, PNG. Tối đa 5 file, ≤ 10MB.')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                            ->visible(fn($record) => $record === null)
                                            ->saveRelationshipsUsing(function (Property $record, $state) {
                                                if (empty($state)) return;
                                                $order = 0;
                                                foreach ($state as $path) {
                                                    $record->files()->create([
                                                        'filename' => basename($path),
                                                        'original_name' => basename($path),
                                                        'path' => $path,
                                                        'purpose' => 'LEGAL_DOC',
                                                        'visibility' => 'PRIVATE',
                                                        'uploaded_by' => Auth::id(),
                                                        'order' => $order++,
                                                        'is_primary' => false,
                                                        'mime_type' => Storage::disk('local')->exists($path) ? (method_exists(Storage::disk('local'), 'mimeType') ? Storage::disk('local')->mimeType($path) : 'application/pdf') : 'application/pdf',
                                                        'size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : 0,
                                                    ]);
                                                }
                                            }),

                                        // EDIT/VIEW MODE: Livewire Manager
                                        Forms\Components\Livewire::make('property-legal-docs-manager')
                                            ->lazy()
                                            ->key(fn($record) => 'legal-docs-manager-' . ($record?->id ?? 'new'))
                                            ->data(fn($record, string $operation) => [
                                                'property' => $record,
                                                'isViewMode' => $operation === 'view',
                                            ])
                                            ->visible(fn($record) => $record !== null),
                                    ])
                                    ->visible(function ($record) {
                                        /** @var User $user */
                                        $user = Auth::user();
                                        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) return true;
                                        if (!$record) return true; // Create mode: always show (will be hidden by backend logic if needed, but usually create is allowed)
                                        return $record->created_by === $user->id;
                                    }),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ImageColumn::make('primaryImage.path')
                    ->label('Ảnh')
                    ->state(fn($record) => $record->primaryImage?->path ? app(\App\Services\ImageService::class)->thumbnailUrl($record->primaryImage->path, 'thumb') : null)
                    ->circular()
                    ->defaultImageUrl(asset('images/no-image.svg')),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn(Property $record): string => "{$record->title}\n📍 " . ($record->address ?? 'N/A'))
                    ->label('Tiêu đề')
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('areaLocation.name')
                    ->sortable()
                    ->label('Khu vực')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Danh mục')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('owner_phone')
                    ->label('SĐT Chủ')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Đã copy SĐT'),

                Tables\Columns\TextColumn::make('price')
                    ->money('VND', locale: 'vi')
                    ->sortable()
                    ->label('Giá')
                    ->color('success')
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('area')
                    ->suffix(' m²')
                    ->sortable()
                    ->label('DT')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location_type')
                    ->label('Vị trí')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Mặt tiền' => 'success',
                        'Ngõ hẻm' => 'warning',
                        'Trong ngõ' => 'info',
                        'Trong khu dân cư' => 'primary',
                        default => 'gray'
                    })
                    ->icon(fn(?string $state): string => match ($state) {
                        'Mặt tiền' => 'heroicon-m-star',
                        default => 'heroicon-m-map-pin'
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shape')
                    ->label('Hình dạng')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('subdivision.name')
                    ->label('Quận/Huyện/Phường/Xã')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => $record->subdivision?->division_type)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->label('Trạng thái')
                    ->formatStateUsing(fn(string $state): string => ApprovalStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => ApprovalStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn(string $state): string => ApprovalStatus::tryFrom($state)?->getIcon() ?? 'heroicon-m-question-mark-circle'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->sortable()
                    ->label('Người tạo')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('areaLocation')
                    ->relationship('areaLocation', 'name')
                    ->label('Khu vực')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->label('Dự án')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Danh mục'),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->options(ApprovalStatus::options())
                    ->label('Trạng thái'),

                Tables\Filters\SelectFilter::make('location_type')
                    ->options([
                        'Mặt tiền' => 'Mặt tiền',
                        'Ngõ hẻm' => 'Ngõ hẻm',
                        'Trong ngõ' => 'Trong ngõ',
                        'Trong khu dân cư' => 'Trong khu dân cư',
                    ])
                    ->label('Vị trí'),

                Tables\Filters\SelectFilter::make('shape')
                    ->options([
                        'Vuông vức' => 'Vuông vức',
                        'Chữ nhật' => 'Chữ nhật',
                        'Tóp hậu' => 'Tóp hậu',
                        'Nở hậu' => 'Nở hậu',
                        'Không thường xuyên' => 'Không thường xuyên',
                    ])
                    ->label('Hình dạng'),


                Tables\Filters\SelectFilter::make('subdivision')
                    ->relationship('subdivision', 'name')
                    ->searchable()
                    ->label('Quận/Huyện/Phường/Xã'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                        Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    })
                    ->columns(2),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->icon('heroicon-m-eye'),

                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('approve')
                        ->label('Duyệt')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt bất động sản')
                        ->modalDescription('Xác nhận duyệt bất động sản này?')
                        ->modalSubmitActionLabel('Duyệt')
                        ->action(function (Property $record) {
                            /** @var User $user */
                            $user = Auth::user();
                            app(PropertyService::class)->approve($record, $user);

                            Notification::make()
                                ->title('Đã duyệt thành công')
                                ->success()
                                ->send();

                            if ($record->creator && $record->creator->id !== $user->id) {
                                Notification::make()
                                    ->title('Bất động sản đã được duyệt')
                                    ->body("BĐS \"{$record->title}\" của bạn đã được duyệt và đang chờ hiển thị.")
                                    ->success()
                                    ->sendToDatabase($record->creator);
                            }
                        })
                        ->visible(function (Property $record) {
                            /** @var User $user */
                            $user = Auth::user();
                            return $record->approval_status === ApprovalStatus::PENDING->value && $user && $user->can('approve', $record);
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Từ chối')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->label('Lý do từ chối')
                                ->placeholder('Nhập lý do từ chối...')
                        ])
                        ->action(function (Property $record, array $data) {
                            /** @var User $user */
                            $user = Auth::user();
                            app(PropertyService::class)->reject($record, $user, $data['reason']);

                            Notification::make()
                                ->title('Đã từ chối')
                                ->warning()
                                ->send();

                            if ($record->creator && $record->creator->id !== $user->id) {
                                Notification::make()
                                    ->title('Bất động sản bị từ chối')
                                    ->body("BĐS \"{$record->title}\" bị từ chối. Lý do: {$data['reason']}")
                                    ->danger()
                                    ->sendToDatabase($record->creator);
                            }
                        })
                        ->visible(function (Property $record) {
                            /** @var User $user */
                            $user = Auth::user();
                            return $record->approval_status === ApprovalStatus::PENDING->value && $user && $user->can('approve', $record);
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Thao tác'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Duyệt hàng loạt')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            // Check permission for current user
                            /** @var User $user */
                            $user = Auth::user();
                            if (!$user->isOfficeAdmin() && !$user->isSuperAdmin()) {
                                Notification::make()->title('Không có quyền thực hiện')->danger()->send();
                                return;
                            }

                            $approvedCount = 0;
                            $records->each(function ($record) use ($user, &$approvedCount) {
                                if ($record->approval_status === ApprovalStatus::PENDING->value) {
                                    app(PropertyService::class)->approve($record, $user);
                                    $approvedCount++;

                                    // Notify creator if it's not the approver
                                    if ($record->creator && $record->creator->id !== $user->id) {
                                        Notification::make()
                                            ->title('Bất động sản đã được duyệt')
                                            ->body("BĐS \"{$record->title}\" của bạn đã được duyệt và đang chờ hiển thị.")
                                            ->success()
                                            ->sendToDatabase($record->creator);
                                    }
                                }
                            });
                            Notification::make()
                                ->title('Đã duyệt ' . $approvedCount . ' BĐS')
                                ->success()
                                ->send();
                        })
                        ->visible(function () {
                            /** @var User $user */
                            $user = Auth::user();
                            return $user && $user->can('approve', Property::class);
                        }),
                ]),
            ])
            ->emptyStateHeading('Chưa có bất động sản nào')
            ->emptyStateDescription('Bắt đầu bằng việc tạo bất động sản mới.')
            ->emptyStateIcon('heroicon-o-home-modern')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tạo BĐS mới')
                    ->icon('heroicon-m-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'view' => Pages\ViewProperty::route('/{record}'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'address', 'owner_name'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['areaLocation'])
            ->withinUserAreas(Auth::user());
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Khu vực' => $record->areaLocation?->name ?? '-',
            'Giá' => number_format((float) ($record->price ?? 0)) . ' VNĐ',
        ];
    }
}
