<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    protected function decryptCookie($name, $cookie)
    {
        try {
            return parent::decryptCookie($name, $cookie);
        } catch (DecryptException) {
            return is_array($cookie) ? $this->decryptArrayGracefully($cookie) : null;
        }
    }

    protected function decryptArrayGracefully(array $cookie): array
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            try {
                $decrypted[$key] = $this->encrypter->decrypt($value, static::serialized($key));
            } catch (DecryptException) {
                continue;
            }
        }

        return $decrypted;
    }
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
