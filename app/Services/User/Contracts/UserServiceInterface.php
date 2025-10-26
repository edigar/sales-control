<?php

namespace App\Services\User\Contracts;

use App\Dto\UserInputDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    /**
     * Create a new user.
     *
     * @param UserInputDTO $userInputDTO
     * @return User
     */
    public function create(UserInputDTO $userInputDTO): User;

    /**
     * Get all users.
     *
     * @return Collection<User>
     */
    public function getAllUsers(): Collection;
}
