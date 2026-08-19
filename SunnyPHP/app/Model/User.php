<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
namespace App\Model;

use SunnyPHP\Auth\Authenticatable;
use SunnyPHP\Database\Model;

final class User extends Model implements Authenticatable
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email', 'password'];

    #[\Override]
    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    #[\Override]
    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }
}
