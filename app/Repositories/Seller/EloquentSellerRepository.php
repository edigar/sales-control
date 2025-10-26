<?php

namespace App\Repositories\Seller;

use App\Dto\SellerInputDTO;
use App\Models\Seller;
use App\Repositories\Seller\Contracts\SellerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentSellerRepository implements SellerRepositoryInterface
{
    /**
     * Create a new seller.
     *
     * @param SellerInputDTO $sellerInputDTO
     * @return Seller
     */
    public function create(SellerInputDTO $sellerInputDTO): Seller
    {
        return Seller::create($sellerInputDTO->toArray());
    }

    /**
     * Get all sellers.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage): LengthAwarePaginator
    {
        return Seller::paginate($perPage);
    }
}
