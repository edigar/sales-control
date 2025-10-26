<?php

namespace App\Repositories\User;

use App\Dto\UserInputDTO;
use App\Models\User;
use App\Repositories\User\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * Create a new user.
     *
     * @param UserInputDTO $userInputDTO
     * @return User
     */
    public function create(UserInputDTO $userInputDTO): User
    {
        return User::create($userInputDTO->toArray());
    }

    /**
     * Get all users.
     *
     * @return Collection<User>
     */
    public function getAll(): Collection
    {
        return User::all();
    }

    /**
     * Find a user by id.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return User::where('id', $id)->first();
    }
}
