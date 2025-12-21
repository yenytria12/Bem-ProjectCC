<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->login()
            ->favicon(asset('images/U.png'))
            ->brandName('BEM TEL-U')
            ->brandLogo(asset('images/TelU.png'))
            ->brandLogoHeight('2.5rem')
            ->darkMode()
            ->colors([
                // 'danger' => '#E63946',  // Merah cerah
                // 'gray' => '#1D3557',    // Biru gelap
                // 'info' => '#457B9D',    // Biru pastel
                // 'primary' => '#2A9D8F', // Hijau biru (turquoise)
                // 'success' => '#2A9D8F', // Hijau biru yang lebih fresh
                // 'warning' => '#F1FAEE', // Putih kekuningan lembut

                // telkom
                'danger' => '#B71C1C',  // Merah tua (dark red) yang intens dan kuat
                'gray' => '#616161',    // Abu-abu netral dengan sedikit sentuhan gelap
                'info' => '#0288D1',    // Biru yang tenang untuk menyeimbangkan merah yang kuat
                'primary' => '#D32F2F', // Merah cerah yang berani dan mencolok
                'success' => '#388E3C', // Hijau gelap yang memberi kesan stabil dan sukses
                'warning' => '#FF8F00', // Kuning-oranye cerah yang enerjik
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ]);
    }
}
