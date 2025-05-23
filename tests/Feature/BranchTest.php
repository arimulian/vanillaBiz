<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

afterEach(function () {
    // Clean up the database after each test
    \Illuminate\Support\Facades\DB::table('branches')->truncate();
    \Illuminate\Support\Facades\DB::table('users')->truncate();
});

// POST a new branch

test('create branch success', function () {
    $response = postJson('api/branches/create', [
        'name' => 'Branch 1',
        'address' => '123 Main St',
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Branch created successfully',
            'data' => [
                'name' => 'Branch 1',
                'address' => '123 Main St',
            ],
        ]);
});

test('create branch failed', function () {
    $response = postJson('api/branches/create', [
        'name' => '',
        'address' => '',
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Validation failed',
            'errors' => [
                'name' => ['The name field is required.'],
                'address' => ['The address field is required.'],
            ],
        ]);
});

test('create branch duplicate name', function () {
    if (Branch::query()->count() === 0) {
        Branch::query()->create([
            'name' => 'Branch 1',
            'address' => '123 Main St',
        ]);
    }
    $response = postJson('api/branches/create', [
        'name' => 'Branch 1',
        'address' => '123 Main St',
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Validation failed',
            'errors' => [
                'name' => ['The name has already been taken.'],
            ],
        ]);
});

// GET Branch

test('get branches success', function () {
    $branch = Branch::query()->create([
        'name' => 'Branch 1',
        'address' => '123 Main St',
    ]);
    $response = getJson('api/branches/get', [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);

    $data = Branch::query()->get();
    $response->assertStatus(200)
        ->assertJson([
            'data' => $data->toArray(),
        ]);
});

test('get branch by id success', function () {
    $branch = Branch::query()->create([
        'name' => 'Branch 1',
        'address' => '123 Main St',
    ]);
    $response = getJson('api/branches/get/' . $branch->id, [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $branch->id,
                'name' => 'Branch 1',
                'address' => '123 Main St',
                'is_active' => 1,
            ],
        ]);
});

test('get branch by id failed', function () {
    $response = getJson('api/branches/get/999', [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Branch not found',
        ]);
});

// DELETE Branch
test('delete branch success', function () {
    $branch = new Branch();
    if ($branch->query()->count() === 0) {
        $branch->query()->create([
            'name' => 'Branch 1',
            'address' => '123 Main St',
        ]);
    }

    $response = deleteJson('/api/branches/delete/1', [], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Branch deleted successfully',
            'data' => true
        ]);
});

// PUT Branch
test('update branch success', function () {
    $branch = new Branch();
    if ($branch->query()->count() === 0) {
        $branch->query()->create([
            'name' => 'Branch 1',
            'address' => '123 Main St',
        ]);
    }

    $response = putJson('/api/branches/update/1', [
        'name' => 'Branch 2',
        'address' => '456 Main St',
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Branch updated successfully',
            'data' => [
                'id' => 1,
                'name' => 'Branch 2',
                'address' => '456 Main St',
                'is_active' => 1,
            ]
        ]);
});

test('update branch is_active', function () {
    $branch = new Branch();
    if ($branch->query()->count() === 0) {
        $branch->query()->create([
            'name' => 'Branch 1',
            'address' => '123 Main St',
        ]);
    }

    $response = putJson('/api/branches/update/1', [
        'name' => 'Branch 2',
        'address' => '123 Main St',
        'is_active' => 0,
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Branch updated successfully',
            'data' => [
                'id' => 1,
                'name' => 'Branch 2',
                'address' => '123 Main St',
                'is_active' => 0,
            ]
        ]);
});

test('update branch not found', function () {
    $response = putJson('/api/branches/update/1', [
        'name' => 'test',
        'address' => 'test',
    ], [
        'Authorization' => Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        ),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Branch not found',
        ]);
});
