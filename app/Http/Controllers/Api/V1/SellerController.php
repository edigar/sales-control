<?php

namespace App\Http\Controllers\Api\V1;

use App\Dto\SellerInputDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerRequest;
use App\Http\Resources\SellerResource;
use App\Services\Seller\Contracts\SellerServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SellerController extends Controller
{
    public function __construct(private readonly SellerServiceInterface $sellerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', config('seller.pagination.per_page', 10));

            $sellers = $this->sellerService->getAllSellers($perPage);

            return response()->json(SellerResource::collection($sellers));
        } catch (\Exception $e) {
            Log::error('Seller index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Seller index failed',
            ]);

            return response()->json(['error' => 'Seller index failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSellerRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $sellerInputDTO = new SellerInputDTO(name: $data['name'], email: $data['email']);

            $this->sellerService->createSeller($sellerInputDTO);

            return response()->json(null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Seller creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Seller creation failed',
            ]);

            return response()->json(['error' => 'Seller creation failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
