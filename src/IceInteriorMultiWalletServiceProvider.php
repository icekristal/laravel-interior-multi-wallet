<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Icekristal\LaravelInteriorMultiWallet\Services\ImWalletService;
use Illuminate\Support\ServiceProvider;

class IceInteriorMultiWalletServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind('im_wallet.service', ImWalletService::class);
        $this->registerConfig();
        $this->registerTranslations();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfigs();
            $this->publishMigrations();
            $this->publishTranslations();
            $this->publishEnum();
        }
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/im_wallet.php', 'im_wallet');
    }


    protected function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'multi_wallet');
    }

    protected function publishMigrations(): void
    {
        if (!class_exists('CreateMultiWalletsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_multi_wallets_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_multi_wallets_table.php'),
            ], 'migrations');
        }
        sleep(1);
        if (!class_exists('CreateMultiWalletRestrictionsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_multi_wallet_restrictions_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_multi_wallet_restrictions_table.php'),
            ], 'migrations');
        }
    }

    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__ . '/../config/im_wallet.php' => config_path('im_wallet.php'),
        ], 'config');

    }

    protected function publishTranslations(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang'),
        ], 'translations');
    }

    protected function publishEnum(): void
    {
        $this->publishes([
            __DIR__ . '/Enums' => app_path('Enums'),
        ], 'enums');
    }

}
