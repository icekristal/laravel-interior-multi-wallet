<?php

namespace Icekristal\LaravelInteriorMultiWallet\Casts;

use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletTypeEnum;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Foundation\Application;

class TypeCustomCast implements CastsAttributes
{

    public string|object $classEnum;

    public function __construct()
    {
        $this->classEnum = config('im_wallet.types_enum', ImWalletTypeEnum::class);
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
        $enum = config('im_wallet.types_enum', ImWalletTypeEnum::class);

        if ($value instanceof $enum) {
            return $value;
        }
        return is_int($value) ? $enum::from($value) : $value;
    }
}
