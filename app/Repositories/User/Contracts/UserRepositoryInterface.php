<?php

namespace App\Repositories\User\Contracts;

use App\Dto\UserInputDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
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
    public function getAll(): Collection;

    /**
     * Find a user by id.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;
}
