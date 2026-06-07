<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeployTokens\Inputs\StoreDeployTokenInput;
use App\Actions\DeployTokens\Inputs\StoreRepositoryDeployTokenInput;
use App\Actions\DeployTokens\StoreDeployToken;
use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Http\Resources\DeployTokenResource;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class RepositoryDeployTokenController extends Controller
{
    public function __construct(
        private StoreDeployToken $storeDeployToken,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function store(StoreRepositoryDeployTokenInput $input, Repository $repository): JsonResponse
    {
        $this->authorize(Permission::DEPLOY_TOKEN_CREATE);

        $user = $this->user();

        $this->authorizeApiTokenAbility($user);
        $this->authorizeRepositoryAccess($user, $repository);

        [$token, $accessToken] = $this->storeDeployToken->handle(new StoreDeployTokenInput(
            name: $input->name,
            abilities: [TokenAbility::REPOSITORY_READ->value],
            expiresAt: $input->expiresAt,
            repositories: [$repository->id],
            packages: null,
        ));

        $token->load(['token', 'repositories', 'packages']);

        return response()->json([
            'token' => new DeployTokenResource($token),
            'plain_text' => $accessToken->plainTextToken,
        ], 201);
    }

    private function authorizeApiTokenAbility(User $user): void
    {
        if (! $user->hasCurrentAccessToken()) {
            abort(403);
        }

        $accessToken = $user->currentAccessToken();

        if ($accessToken->isExpired() || ! $accessToken->can(TokenAbility::DEPLOY_TOKEN_CREATE->value)) {
            abort(403);
        }
    }

    private function authorizeRepositoryAccess(User $user, Repository $repository): void
    {
        if ($user->isUnscoped()) {
            return;
        }

        $hasAccess = DB::query()
            ->fromSub($user->accessibleRepositoryIdsQuery(), 'accessible_repositories')
            ->where('id', $repository->id)
            ->exists();

        abort_unless($hasAccess, 404);
    }
}
