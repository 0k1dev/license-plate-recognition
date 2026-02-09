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
                    ->schema([
                        static::getPermissionCheckboxList('user', 'Người dùng'),
                        static::getPermissionCheckboxList('property', 'Bất động sản'),
                        static::getPermissionCheckboxList('project', 'Dự án'),
                        static::getPermissionCheckboxList('area', 'Khu vực'),
                        static::getPermissionCheckboxList('category', 'Danh mục'),
                        static::getPermissionCheckboxList('post', 'Bài đăng'),
                        static::getPermissionCheckboxList('report', 'Báo cáo'),
                        static::getPermissionCheckboxList('audit_log', 'Nhật ký'),
                        static::getPermissionCheckboxList('role', 'Vai trò'),
                        static::getPermissionCheckboxList('permission', 'Quyền hạn'),
                    ])
                    ->columns(2),
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
