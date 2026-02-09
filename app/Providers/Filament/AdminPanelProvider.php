<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\DebugLogin::class) // Sử dụng Debug Login
            ->passwordReset()
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Hồ sơ của tôi')
                    ->url(fn(): string => \App\Filament\Pages\MyProfile::getUrl())
                    ->icon('heroicon-m-user-circle'),
            ])
            // Global Search
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('300ms')
            // Plugins
            ->plugins([
                // \Visualbuilder\EmailTemplates\EmailTemplatesPlugin::make(),
            ])

            // Custom Styles
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn(): string => $this->renderCustomStyles()
            )
            // SPA Mode for faster navigation
            ->spa()
            // Discover Resources, Pages
            ->brandName('Bất Động Sản')
            ->brandLogoHeight('3rem')
            ->font('Outfit') // Modern, clean font
            ->favicon(asset('images/favicon.png'))
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Indigo,
                'gray' => \Filament\Support\Colors\Color::Slate,
                'info' => \Filament\Support\Colors\Color::Blue,
                'success' => \Filament\Support\Colors\Color::Emerald,
                'warning' => \Filament\Support\Colors\Color::Orange,
                'danger' => \Filament\Support\Colors\Color::Rose,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            // Database Notifications
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // Widgets
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\StatsSummary::class,
                \App\Filament\Widgets\PendingActionsWidget::class,
                \App\Filament\Widgets\PropertyStatusChart::class,
                \App\Filament\Widgets\PostActivityChart::class,
                \App\Filament\Widgets\RecentActivitiesWidget::class,
            ])
            // Middleware
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                \App\Http\Middleware\VerifyCsrfToken::class, // Use our custom class with Livewire exceptions
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Get site name from settings
     */
    private function getSiteName(): string
    {
        try {
            return app(\App\Settings\GeneralSettings::class)->site_name ?? 'Bất động sản';
        } catch (\Throwable) {
            return 'Bất động sản';
        }
    }

    /**
     * Get site logo from settings
     */
    private function getSiteLogo(): ?string
    {
        try {
            $logo = app(\App\Settings\GeneralSettings::class)->site_logo;
            return $logo ? \Illuminate\Support\Facades\Storage::url($logo) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get favicon from settings
     */
    private function getFavicon(): ?string
    {
        try {
            $favicon = app(\App\Settings\GeneralSettings::class)->site_favicon ?? null;
            return $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Render custom styles including login background
     */
    private function renderCustomStyles(): string
    {
        return \Illuminate\Support\Facades\Blade::render(<<<'BLADE'
            @php
                try {
                    $settings = app(\App\Settings\GeneralSettings::class);
                    $bg = $settings->auth_bg_image ? \Illuminate\Support\Facades\Storage::url($settings->auth_bg_image) : null;
                } catch (\Throwable $e) {
                    $bg = null;
                }
                $url = $bg ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2070&q=80';
            @endphp

            {{-- External CSS file --}}
            <link rel="stylesheet" href="{{ asset('css/filament-custom.css') }}">

            {{-- Dynamic login background --}}
            <style>
                :root {
                    --login-bg-url: url('{{ $url }}');
                }
            </style>
        BLADE);
    }
}
