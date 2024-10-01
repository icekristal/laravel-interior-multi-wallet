<?php

namespace Icekristal\LaravelInteriorMultiWallet\Trait;


use App\Enums\ImWalletTypeEnum;

trait BalanceNameTrait
{

    /**
     * @param string $name
     * @return BalanceNameTrait|ImWalletTypeEnum
     */
    public static function fromName(string $name): self
    {
        foreach (self::cases() as $operation) {
            if ($name === $operation->name) {
                return $operation;
            }
        }
        return self::unknown;
    }


    /**
     * @return string
     */
    public function translate(): string
    {
        return 'Unknown';
    }

}
