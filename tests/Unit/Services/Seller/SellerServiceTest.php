<?php

namespace Tests\Unit\Services\Seller;

use App\Dto\SellerInputDTO;
use App\Models\Seller;
use App\Repositories\Seller\Contracts\SellerRepositoryInterface;
use App\Services\Seller\SellerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SellerServiceTest extends TestCase
{
    /** @var SellerRepositoryInterface&\Mockery\MockInterface */
    private SellerRepositoryInterface $repositoryMock;
    private SellerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(SellerRepositoryInterface::class);
        $this->service = new SellerService($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_seller_successfully(): void
    {
        $sellerInputDTO = new SellerInputDTO(
            name: 'John Doe',
            email: 'john@example.com'
        );

        $expectedSeller = new Seller();
        $expectedSeller->id = 1;
        $expectedSeller->name = 'John Doe';
        $expectedSeller->email = 'john@example.com';

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($sellerInputDTO)
            ->andReturn($expectedSeller);

        $result = $this->service->createSeller($sellerInputDTO);

        $this->assertInstanceOf(Seller::class, $result);
        $this->assertEquals($expectedSeller->id, $result->id);
        $this->assertEquals($expectedSeller->name, $result->name);
        $this->assertEquals($expectedSeller->email, $result->email);
    }

    public function test_create_seller_with_transaction_rollback_on_error(): void
    {
        $sellerInputDTO = new SellerInputDTO(
            name: 'Jane Doe',
            email: 'jane@example.com'
        );

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($sellerInputDTO)
            ->andThrow(new \Exception('Database error'));


        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');
        
        $this->service->createSeller($sellerInputDTO);
    }

    public function test_get_all_sellers_successfully(): void
    {
        $perPage = 15;
        
        $sellers = collect([
            (object) ['id' => 1, 'name' => 'Seller 1', 'email' => 'seller1@example.com'],
            (object) ['id' => 2, 'name' => 'Seller 2', 'email' => 'seller2@example.com'],
            (object) ['id' => 3, 'name' => 'Seller 3', 'email' => 'seller3@example.com'],
        ]);

        $expectedPaginator = new LengthAwarePaginator(
            items: $sellers,
            total: 3,
            perPage: $perPage,
            currentPage: 1
        );

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getAllSellers($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $this->assertEquals($perPage, $result->perPage());
        $this->assertCount(3, $result->items());
    }

    public function test_get_all_sellers_returns_empty_paginator_when_no_sellers(): void
    {
        $perPage = 10;
        
        $emptyPaginator = new LengthAwarePaginator(
            items: collect([]),
            total: 0,
            perPage: $perPage,
            currentPage: 1
        );

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($emptyPaginator);

        $result = $this->service->getAllSellers($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    public function test_get_all_sellers_with_custom_per_page(): void
    {
        $perPage = 5;
        
        $sellers = collect([
            (object) ['id' => 1, 'name' => 'Seller 1', 'email' => 'seller1@example.com'],
        ]);

        $expectedPaginator = new LengthAwarePaginator(
            items: $sellers,
            total: 1,
            perPage: $perPage,
            currentPage: 1
        );

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getAllSellers($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($perPage, $result->perPage());
    }
}
