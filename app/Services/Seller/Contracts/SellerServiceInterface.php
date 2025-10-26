<?php

namespace App\Services\Seller\Contracts;

use App\Dto\SellerInputDTO;
use App\Models\Seller;
use Illuminate\Pagination\LengthAwarePaginator;

interface SellerServiceInterface
{
    /**
     * Create a new seller.
     *
     * @param SellerInputDTO $sellerInputDTO
     * @return Seller
     */
    public function createSeller(SellerInputDTO $sellerInputDTO): Seller;

    /**
     * Get all sellers.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSellers(int $perPage): LengthAwarePaginator;
}
