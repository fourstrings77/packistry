<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Contracts\Tokenable;
use App\Models\Token;
use DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use RuntimeException;

/**
 * @phpstan-require-implements Tokenable
 */
trait HasApiTokens
{
    use \Laravel\Sanctum\HasApiTokens;

    /**
     * @param  string[]  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $plainTextToken = $this->generateTokenString();

        /** @var Token $token */
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, "{$token->type()->prefix()}-$plainTextToken");
    }

    public function currentAccessToken(): Token
    {
        if (! $this->hasCurrentAccessToken()) {
            throw new RuntimeException('No current access token is available');
        }

        /** @var Token $token */
        $token = $this->accessToken;

        return $token;
    }

    public function hasCurrentAccessToken(): bool
    {
        return $this->accessToken instanceof Token;
    }
}
