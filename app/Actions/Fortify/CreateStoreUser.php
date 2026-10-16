<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateStoreUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly CreateNewUser $creator) {}

    public function create(array $input): User
    {
        $address = $input['address'] ?? null;
        if (is_string($address)) {
            $address = json_decode($address, true);
        }

        Validator::make([...$input, 'address' => $address], [
            'password' => $this->passwordRules(),
            'store_name' => ['required', 'string', 'min:3', 'max:255'],
            'user_phone' => ['required', 'string', 'max:20'],
            'sale_type' => ['required', 'in:atacado,varejo,ambos'],
            'store_type' => ['required', 'in:fisica,virtual,ambos'],
            'is_manufacturer' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory' => ['required', 'array', 'min:1'],
            'subcategory.*' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'array'],
            'address.cep' => ['required_if:store_type,fisica,ambos', 'nullable', 'string', 'max:10'],
            'address.street' => ['required_if:store_type,fisica,ambos', 'nullable', 'string', 'max:255'],
            'address.city' => ['required_if:store_type,fisica,ambos', 'nullable', 'string', 'max:255'],
            'address.state' => ['required_if:store_type,fisica,ambos', 'nullable', 'string', 'size:2'],
        ])->validate();

        return $this->creator->create($input);
    }
}
