<?php

declare(strict_types=1);

namespace App\Actions\DeployTokens\Inputs;

use App\Actions\Input;
use Illuminate\Support\Carbon;

class StoreRepositoryDeployTokenInput extends Input
{
    public function __construct(
        public string $name,
        public ?Carbon $expiresAt = null,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
