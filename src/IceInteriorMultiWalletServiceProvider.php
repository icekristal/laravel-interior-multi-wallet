<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Illuminate\Support\ServiceProvider;

class IceInteriorMultiWalletServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->registerConfig();
        $this->registerTranslations();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfigs();
            $this->publishMigrations();
            $this->publishTranslations();
        }
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/im_wallet.php', 'im_wallet');
    }


    protected function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'multi_wallet');
    }

    protected function publishMigrations(): void
    {
        if (!class_exists('CreateMultiWalletsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_multi_wallets_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_multi_wallets_table.php'),
            ], 'migrations');
        }
    }

    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__ . '/../config/im_wallet.php' => config_path('im_wallet.php'),
        ], 'config');

    }

    protected function publishTranslations()
    {
        $this->publishes([
            __DIR__.'../resources/lang' => resource_path('lang'),
        ]);
    }

}
