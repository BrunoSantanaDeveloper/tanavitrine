<?php

declare(strict_types=1);

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Http::preventStrayRequests();
});

function completeStoreRegistrationPayload(): array
{
    $category = Category::query()->create(['name' => 'Moda', 'slug' => 'moda', 'is_active' => true]);
    $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aOioAAAAASUVORK5CYII=');

    return [
        'name' => 'Responsável pela Loja',
        'email' => 'loja@example.com',
        'user_phone' => '(62) 99999-9999',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => true,
        'store_name' => 'Loja Completa',
        'sale_type' => 'varejo',
        'store_type' => 'virtual',
        'is_manufacturer' => false,
        'category_id' => $category->id,
        'subcategory' => ['Camisetas'],
        'description' => str_repeat('Descrição dos produtos da loja. ', 3),
        'whatsapp' => '(62) 99999-9999',
        'address' => json_encode(['cep' => '', 'street' => '', 'city' => '', 'state' => '']),
        'logo' => UploadedFile::fake()->createWithContent('logo.png', $image),
        'photos' => array_map(fn ($index) => UploadedFile::fake()->createWithContent("foto{$index}.png", $image), range(1, 3)),
    ];
}

test('simple registration page redirects to pricing even with a selected plan', function (): void {
    $this->get('/register')->assertRedirect(route('prices'));
    $this->get('/register?plan=123')->assertRedirect(route('prices'));
});

test('simple registration cannot create users or teams', function (): void {
    $this->postJson('/register', [
        'name' => 'Cadastro incompleto',
        'email' => 'incompleto@example.com',
        'terms' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['store_name', 'password']);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
});

test('the former simple registration payload is rejected even with a valid password', function (): void {
    $this->postJson('/register', [
        'name' => 'Cadastro antigo',
        'email' => 'antigo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('store_name');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
});

test('store registration rejects missing required fields before saving anything', function (string $field): void {
    $payload = completeStoreRegistrationPayload();
    unset($payload[$field]);

    $this->post('/register', $payload)->assertSessionHasErrors($field === 'password_confirmation' ? 'password' : $field);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
})->with(['store_name', 'password', 'password_confirmation', 'user_phone', 'sale_type', 'store_type', 'is_manufacturer', 'category_id', 'subcategory', 'description', 'whatsapp', 'logo', 'photos']);

test('store registration rejects empty or insufficient required values', function (string $field, mixed $value): void {
    $payload = completeStoreRegistrationPayload();
    $payload[$field] = $value;

    $this->post('/register', $payload)->assertSessionHasErrors($field);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
})->with([
    ['store_name', ''],
    ['password', ''],
    ['description', 'Muito curta'],
    ['subcategory', []],
    ['photos', []],
]);

test('physical store requires its address on the server', function (): void {
    $payload = completeStoreRegistrationPayload();
    $payload['store_type'] = 'ambos';

    $this->post('/register', $payload)->assertSessionHasErrors(['address.cep', 'address.street', 'address.city', 'address.state']);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
});

test('complete virtual store registration still succeeds', function (): void {
    $response = $this->post('/register', completeStoreRegistrationPayload());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'loja@example.com', 'user_phone' => '(62) 99999-9999']);
    $this->assertDatabaseHas('teams', ['name' => 'Loja Completa', 'personal_team' => false, 'status' => 'pendente']);
    $this->assertDatabaseCount('media', 3);
});
