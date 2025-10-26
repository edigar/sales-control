<?php

namespace App\Repositories\Seller\Contracts;

use App\Dto\SellerInputDTO;
use App\Models\Seller;
use Illuminate\Pagination\LengthAwarePaginator;

interface SellerRepositoryInterface
{
    /**
     * Create a new seller.
     *
     * @param SellerInputDTO $sellerInputDTO
     * @return Seller
     */
    public function create(SellerInputDTO $sellerInputDTO): Seller;

    /**
     * Get all sellers.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage): LengthAwarePaginator;
}
