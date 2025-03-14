<?php

namespace Icekristal\LaravelInteriorMultiWallet\Casts;

use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletCurrencyEnum;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Foundation\Application;

class CurrencyCustomCast implements CastsAttributes
{

    public string|object $classEnum;

    public function __construct()
    {
        $this->classEnum = config('im_wallet.currency_enum', ImWalletCurrencyEnum::class);
    }

    /**
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $this->classEnum::from($value);
    }

    /**
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed|null
     */
    public function set($model, string $key, $value, array $attributes): mixed
    {
        if ($value instanceof $this->classEnum) {
            return $value->value;
        }
        return $value;
    }

    /**
     * @param $value
     * @return Repository|Application|\Illuminate\Foundation\Application
     */
    public static function setEnum($value): mixed
    {
        $enum = config('im_wallet.currency_enum', ImWalletCurrencyEnum::class);

        if ($value instanceof $enum) {
            return $value;
        }
        return is_string($value) || is_numeric($value) ? $enum::from($value) : $value;
    }
}
