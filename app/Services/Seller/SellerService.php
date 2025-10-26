<?php

namespace App\Services\Seller;

use App\Dto\SellerInputDTO;
use App\Models\Seller;
use App\Repositories\Seller\Contracts\SellerRepositoryInterface;
use App\Services\Seller\Contracts\SellerServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class SellerService implements SellerServiceInterface
{
    /**
     * Create a new seller service.
     *
     * @param SellerRepositoryInterface $repository
     */
    public function __construct(private readonly SellerRepositoryInterface $repository)
    {
    }

    /**
     * Create a new seller.
     *
     * @param SellerInputDTO $sellerInputDTO
     * @return Seller
     */
    public function createSeller(SellerInputDTO $sellerInputDTO): Seller
    {
        return DB::transaction(function () use ($sellerInputDTO) {
            return $this->repository->create($sellerInputDTO);
        });
    }

    /**
     * Get all sellers.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSellers(int $perPage): LengthAwarePaginator
    {
        return DB::transaction(function () use ($perPage) {
            return $this->repository->getAll($perPage);
        });
    }
}
