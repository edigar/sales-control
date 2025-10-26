<?php

namespace App\Services\User;

use App\Dto\UserInputDTO;
use App\Models\User;
use App\Repositories\User\Contracts\UserRepositoryInterface;
use App\Services\User\Contracts\UserServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    /**
     * Create a new user service.
     *
     * @param UserRepositoryInterface $repository
     */
    public function __construct(private readonly UserRepositoryInterface $repository)
    {
    }

    /**
     * Create a new user.
     *
     * @param UserInputDTO $userInputDTO
     * @return User
     */
    public function create(UserInputDTO $userInputDTO): User
    {
        return DB::transaction(function () use ($userInputDTO) {
            return $this->repository->create($userInputDTO);
        });
    }

    /**
     * Get all users.
     *
     * @return Collection<User>
     */
    public function getAllUsers(): Collection
    {
        return DB::transaction(function () {
            return $this->repository->getAll();
        });
    }
}
