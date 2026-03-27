<?php

declare(strict_types=1);
namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = \Spatie\Permission\Models\Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $modelLabel = 'Vai trò';
    protected static ?string $pluralModelLabel = 'Danh sách Vai trò';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 2;

    public static function getPermissionCheckboxList(string $entity, string $label): Forms\Components\Component
    {
        return Forms\Components\CheckboxList::make('permissions_' . $entity)
            ->label($label)
            ->options(function () use ($entity) {
                return \Spatie\Permission\Models\Permission::where('name', 'like', "%_{$entity}")
                    ->get() // Fetch collection
                    ->mapWithKeys(function ($permission) { // Map to [id => formatted_label]
                        $label = $permission->name;
                        $formatted = match (true) {
                            str_contains($label, 'view_any') => 'Xem danh sách',
                            str_contains($label, 'view_all_properties') => 'Xem tất cả BĐS (Bỏ qua KV)',
                            str_contains($label, 'view_owner_phone') => 'Xem SĐT chính',
                            str_contains($label, 'view_legal_docs') => 'Xem pháp lý',
                            str_contains($label, 'view') => 'Xem chi tiết',
                            str_contains($label, 'create') => 'Thêm mới',
                            str_contains($label, 'update') => 'Cập nhật',
                            str_contains($label, 'delete_any') => 'Xóa nhiều',
                            str_contains($label, 'force_delete') => 'Xóa vĩnh viễn',
                            str_contains($label, 'delete') => 'Xóa',
                            str_contains($label, 'restore') => 'Khôi phục',
                            str_contains($label, 'approve') => 'Duyệt',
                            default => $label,
                        };
                        return [$permission->id => $formatted];
                    });
            })
            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?\Illuminate\Database\Eloquent\Model $record) use ($entity) {
                if (!$record) return;
                // Load existing permissions for this entity group
                $permissions = $record->permissions()
                    ->where('name', 'like', "%_{$entity}")
                    ->pluck('id')
                    ->toArray();
                $component->state($permissions);
            })
            ->columns(2)
            ->gridDirection('row')
            ->bulkToggleable();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Tên vai trò'),
                Forms\Components\Section::make('Phân quyền')
                    ->description('Chọn các quyền hạn gán cho vai trò này')
                    ->schema([
                        Forms\Components\Tabs::make('Permissions')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Core')
                                    ->label('Cơ bản')
                                    ->schema([
                                        static::getPermissionCheckboxList('user', 'Người dùng'),
                                        static::getPermissionCheckboxList('role', 'Vai trò'),
                                        static::getPermissionCheckboxList('permission', 'Quyền hạn'),
                                        static::getPermissionCheckboxList('audit_log', 'Nhật ký'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('BĐS')
                                    ->label('Bất động sản')
                                    ->schema([
                                        static::getPermissionCheckboxList('property', 'Bất động sản'),
                                        static::getPermissionCheckboxList('project', 'Dự án'),
                                        static::getPermissionCheckboxList('area', 'Khu vực (Tỉnh/TP)'),
                                        static::getPermissionCheckboxList('category', 'Danh mục'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Vận hành')
                                    ->label('Vận hành')
                                    ->schema([
                                        static::getPermissionCheckboxList('post', 'Bài đăng'),
                                        static::getPermissionCheckboxList('report', 'Báo cáo'),
                                        static::getPermissionCheckboxList('owner_phone_request', 'Yêu cầu SĐT'),
                                        static::getPermissionCheckboxList('file', 'Tệp tin'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Khác')
                                    ->label('Khác')
                                    ->schema([
                                        static::getPermissionCheckboxList('province', 'Tỉnh/Thành phố'),
                                        static::getPermissionCheckboxList('subdivision', 'Quận/Huyện'),
                                        static::getPermissionCheckboxList('email_template', 'Mẫu Email'),
                                        // Dự phòng cho các quyền không thuộc group nào
                                        Forms\Components\CheckboxList::make('permissions_other')
                                            ->label('Quyền khác (Chưa phân nhóm)')
                                            ->options(function (?\Illuminate\Database\Eloquent\Model $record) {
                                                $standardEntities = [
                                                    'user', 'property', 'project', 'area', 'category', 'post', 'report', 'audit_log', 'role', 'permission',
                                                    'file', 'owner_phone_request', 'province', 'subdivision', 'email_template'
                                                ];
                                                $query = \Spatie\Permission\Models\Permission::query();
                                                foreach ($standardEntities as $entity) {
                                                    $query->where('name', 'not like', "%_{$entity}");
                                                }
                                                return $query->pluck('name', 'id');
                                            })
                                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?\Illuminate\Database\Eloquent\Model $record) {
                                                if (!$record) return;
                                                $standardEntities = [
                                                    'user', 'property', 'project', 'area', 'category', 'post', 'report', 'audit_log', 'role', 'permission',
                                                    'file', 'owner_phone_request', 'province', 'subdivision', 'email_template'
                                                ];
                                                $allPermissions = $record->permissions()->pluck('name', 'id');
                                                $otherIds = $allPermissions->filter(function ($name) use ($standardEntities) {
                                                    foreach ($standardEntities as $entity) {
                                                        if (str_ends_with($name, "_{$entity}")) return false;
                                                    }
                                                    return true;
                                                })->keys()->toArray();
                                                $component->state($otherIds);
                                            })
                                            ->columns(2)
                                            ->bulkToggleable(),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->persistTabInQueryString(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Tên vai trò'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Số quyền hạn')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Ngày tạo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
