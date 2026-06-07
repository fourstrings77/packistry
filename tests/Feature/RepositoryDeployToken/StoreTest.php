<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Models\DeployToken;
use App\Models\Repository;

use function Pest\Laravel\postJson;

it('denies guests', function (): void {
    $repository = Repository::factory()->create();

    postJson("/api/repositories/$repository->id/deploy-tokens", [
        'name' => 'Customer Composer token',
    ])
        ->assertStatus(401);
});

it('denies users without deploy token create permission', function (): void {
    $repository = Repository::factory()->create();

    user();

    postJson("/api/repositories/$repository->id/deploy-tokens", [
        'name' => 'Customer Composer token',
    ])
        ->assertStatus(403);
});

it('denies tokens without deploy token create ability', function (): void {
    Repository::factory()->create();
    $repository = Repository::factory()->create();

    personalToken(
        abilities: TokenAbility::REPOSITORY_READ,
        withAccess: true,
        permissions: Permission::DEPLOY_TOKEN_CREATE
    );

    postJson("/api/repositories/$repository->id/deploy-tokens", [
        'name' => 'Customer Composer token',
    ])
        ->assertStatus(403);
});

it('creates a repository-scoped read deploy token', function (): void {
    $repository = Repository::factory()->create();
    $otherRepository = Repository::factory()->create();

    personalToken(
        abilities: TokenAbility::DEPLOY_TOKEN_CREATE,
        withAccess: true,
        permissions: Permission::DEPLOY_TOKEN_CREATE
    );

    $response = postJson("/api/repositories/$repository->id/deploy-tokens", [
        'name' => 'Customer Composer token',
        'expires_at' => $expiresAt = now()->addYear()->format(DATE_RFC3339_EXTENDED),
        'abilities' => [TokenAbility::REPOSITORY_WRITE->value],
        'repositories' => [$otherRepository->id],
        'packages' => [1],
    ])
        ->assertCreated()
        ->assertJsonStructure([
            'token',
            'plain_text',
        ])
        ->assertJsonPath('token.name', 'Customer Composer token')
        ->assertJsonPath('token.abilities', [TokenAbility::REPOSITORY_READ->value])
        ->assertJsonPath('token.repositories.0.id', $repository->id)
        ->assertJsonPath('token.repositories.0.name', $repository->name)
        ->assertJsonPath('token.packages', []);

    expect($response->json('plain_text'))->toBeString()->not->toBe('');

    /** @var DeployToken $deployToken */
    $deployToken = DeployToken::query()->firstOrFail();

    expect($deployToken)
        ->name->toBe('Customer Composer token')
        ->and($deployToken->token->abilities)->toBe([TokenAbility::REPOSITORY_READ->value])
        ->and($deployToken->token->expires_at->format(DATE_RFC3339_EXTENDED))->toBe($expiresAt)
        ->and($deployToken->repositories()->pluck('repositories.id')->all())->toBe([$repository->id])
        ->and($deployToken->packages()->count())->toBe(0);
});

it('denies repository access outside the authenticated user scope', function (): void {
    Repository::factory()->create();
    $repository = Repository::factory()->create();

    personalToken(
        abilities: TokenAbility::DEPLOY_TOKEN_CREATE,
        withAccess: true,
        permissions: Permission::DEPLOY_TOKEN_CREATE
    );

    postJson("/api/repositories/$repository->id/deploy-tokens", [
        'name' => 'Customer Composer token',
    ])
        ->assertStatus(404);
});
