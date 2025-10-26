<?php

namespace App\Repositories\Sale;

use App\Models\Sale;
use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Dto\SaleInputDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    /**
     * Create a new sale.
     *
     * @param SaleInputDTO $saleInputDTO
     * @return Sale
     */
    public function create(SaleInputDTO $saleInputDTO): Sale
    {
        return Sale::create($saleInputDTO->toArray());
    }

    /**
     * Get all sales.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage): LengthAwarePaginator
    {
        return Sale::with('seller')->paginate($perPage);
    }

    /**
     * Get sales by seller.
     *
     * @param int $sellerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBySeller(int $sellerId, int $perPage): LengthAwarePaginator
    {
        return Sale::with('seller')
            ->where('seller_id', $sellerId)
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get daily sales report for all sellers with sales on the specified date.
     *
     * @param string $date
     * @return Collection
     */
    public function getDailySalesReportBySeller(string $date): Collection
    {
        return DB::table('sales')
            ->join('sellers', 'sales.seller_id', '=', 'sellers.id')
            ->where('sales.date', $date)
            ->select(
                'sellers.id as seller_id',
                'sellers.name as seller_name',
                'sellers.email as seller_email',
                DB::raw('COUNT(sales.id) as total_sales'),
                DB::raw('SUM(sales.amount) as total_amount'),
                DB::raw('SUM(sales.commission) as total_commission')
            )
            ->groupBy('sellers.id', 'sellers.name', 'sellers.email')
            ->get();
    }

    /**
     * Get daily sales report on the specified date.
     *
     * @param string $date
     * @return object
     */
    public function getDailySalesReport(string $date): object
    {
        return DB::table('sales')
            ->where('date', $date)
            ->select(
                DB::raw('COUNT(id) as total_sales'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(commission) as total_commission')
            )
            ->first();
    }
}
