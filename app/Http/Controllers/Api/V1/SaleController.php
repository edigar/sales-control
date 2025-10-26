<?php

namespace App\Http\Controllers\Api\V1;

use App\Dto\SaleInputDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Services\Sale\Contracts\SaleServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    public function __construct(private SaleServiceInterface $saleService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', config('sale.pagination.per_page', 10));
            $sales = $this->saleService->getAllSales($perPage);

            return response()->json(SaleResource::collection($sales));
        } catch (\Exception $e) {
            Log::error('Sale index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Sale index failed',
            ]);

            return response()->json(['error' => 'Sale index failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showBySeller(Request $request, int $sellerId): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', config('sale.pagination.per_page', 10));
            $sales = $this->saleService->getSalesBySeller($sellerId, $perPage);

            return response()->json(SaleResource::collection($sales));
        } catch (\Exception $e) {
            Log::error('Sale show by seller failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'seller_id' => $sellerId,
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Sale show by seller failed',
            ]);

            return response()->json(['error' => 'Sales show by seller failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $saleInputDTO = new SaleInputDTO(
                seller_id: $data['seller_id'],
                amount: $data['amount'],
                commission: 0,
                date: $data['date'],
            );

            $this->saleService->createSale($saleInputDTO);

            return response()->json(null, 201);
        } catch (\Exception $e) {
            Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Sale creation failed',
            ]);

            return response()->json(['error' => 'Sale creation failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
