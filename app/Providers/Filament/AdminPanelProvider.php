<?php

namespace App\Providers\Filament;

use Filament\Support\Enums\Width;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use App\Filament\Resources\Claims\ClaimResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\Invoices\InvoiceResource;
use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\Departments\DepartmentResource;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->maxContentWidth(Width::Full)
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->databaseNotifications()
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->items([
                    NavigationItem::make('Dashboard')
                        ->icon('heroicon-m-home')
                        ->url(fn(): string => Dashboard::getUrl())
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.pages.dashboard')),
                ])
                    ->groups([
                        NavigationGroup::make()
                            ->items([
                                NavigationItem::make('Claims')
                                    ->url(fn(): string => ClaimResource::getUrl()),
                                    // ->visible(fn(): bool => auth()->user()?->hasAnyPermission([
                                    //         'create_claim',
                                    //         'view_claim',
                                    //         'edit_claim'
                                    //     ])),
                                NavigationItem::make('Vendors')
                                    ->url(fn(): string => VendorResource::getUrl('index')),
                                    // ->visible(fn(): bool => auth()->user()?->hasAnyPermission([
                                    //         'create_overtimecoverage',
                                    //         'view_overtimecoverage',
                                    //         'edit_overtimecoverage'
                                    //     ])),
                               
                            ])
                            ->label('Claims & Vendors')
                            ->icon('heroicon-o-clock'),

                        NavigationGroup::make()
                            ->items([
                                NavigationItem::make('Invoices')
                                    ->url(fn(): string => InvoiceResource::getUrl('index')),
                                    
                                    // ->visible(fn(): bool => auth()->user()?->hasAnyPermission([
                                    //         'create_approval',
                                    //         'view_approval',
                                    //         'edit_approval'
                                    //     ])),
                            ])
                            ->label('Vendor Invoices')
                            ->icon('heroicon-o-check-badge'),
                        
                        NavigationGroup::make()
                            ->icon('heroicon-o-cog-8-tooth')
                            ->label('Masters')
                            ->items([
                                NavigationItem::make('Departments')
                                    ->url(fn(): string => DepartmentResource::getUrl('index'))
                                    ->visible(fn(): bool => auth()->user()?->hasRole('super_admin')),
                                    // ->visible(fn(): bool => auth()->user()?->can('view_company')),
                                
                            ]),
                        
                        // NavigationGroup::make('Scheduled Tasks')
                        //     ->items(
                        //         [
                        //             NavigationItem::make('Task Scheduler')
                        //                 ->url(fn(): string => ScheduleResource::getUrl('index'))
                        //                 ->visible(fn(): bool => auth()->user()?->hasRole('super_admin')),
                                        
                        //         ]
                        //     )
                        //     ->icon('heroicon-o-user-group')
                        //     ->collapsed(),

                    

                        NavigationGroup::make('User & Role Management')
                            ->items(
                                [
                                    // NavigationItem::make('Users')
                                    //     ->url(fn(): string => UserResource::getUrl('index'))
                                    //     // ->visible(fn(): bool => auth()->user()?->can('create_user') 
                                    //     //     || auth()->user()?->can('view_user') 
                                    //     //     || auth()->user()?->can('edit_user')),
                                    //     ->visible(fn(): bool => auth()->user()?->hasAnyPermission([
                                    //         'create_user',
                                    //         'view_user',
                                    //         'edit_user',
                                    //     ])),
                                    NavigationItem::make('Roles')
                                        ->url(fn(): string => RoleResource::getUrl('index'))
                                        ->visible(fn(): bool => auth()->user()?->hasRole('super_admin')),
                                ]
                            )
                            ->icon('heroicon-o-user-group')
                            ->collapsed(),

                    ]);
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
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
