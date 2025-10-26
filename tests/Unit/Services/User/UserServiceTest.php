<?php

namespace Tests\Unit\Services\User;

use App\Dto\UserInputDTO;
use App\Models\User;
use App\Repositories\User\Contracts\UserRepositoryInterface;
use App\Services\User\UserService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    /** @var UserRepositoryInterface&\Mockery\MockInterface */
    private UserRepositoryInterface $repositoryMock;
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $this->service = new UserService($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockDbTransaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());
    }

    private function createUserInputDTO(
        string $name = 'John Doe',
        string $email = 'john@example.com',
        string $password = 'password123'
    ): UserInputDTO {
        return new UserInputDTO(
            name: $name,
            email: $email,
            password: $password
        );
    }

    private function createUserModel(
        int $id,
        string $name,
        string $email
    ): User {
        $user = new User();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        return $user;
    }

    public function test_create_user_successfully(): void
    {
        $userInputDTO = $this->createUserInputDTO();
        $expectedUser = $this->createUserModel(1, 'John Doe', 'john@example.com');

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof UserInputDTO
                    && $arg->name === 'John Doe'
                    && $arg->email === 'john@example.com'
                    && $arg->password === 'password123';
            }))
            ->andReturn($expectedUser);

        $result = $this->service->create($userInputDTO);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($expectedUser->id, $result->id);
        $this->assertEquals($expectedUser->name, $result->name);
        $this->assertEquals($expectedUser->email, $result->email);
    }

    public function test_create_user_with_different_data(): void
    {
        $userInputDTO = $this->createUserInputDTO(
            name: 'Jane Doe',
            email: 'jane.doe@example.com',
            password: 'password123'
        );
        $expectedUser = $this->createUserModel(2, 'Jane Doe', 'jane.doe@example.com');

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof UserInputDTO
                    && $arg->name === 'Jane Doe'
                    && $arg->email === 'jane.doe@example.com'
                    && $arg->password === 'password123';
            }))
            ->andReturn($expectedUser);

        $result = $this->service->create($userInputDTO);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals('jane.doe@example.com', $result->email);
    }

    public function test_create_user_with_transaction_rollback_on_error(): void
    {
        $userInputDTO = $this->createUserInputDTO();

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($userInputDTO)
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->create($userInputDTO);
    }

    public function test_create_user_handles_duplicate_email_error(): void
    {
        $userInputDTO = $this->createUserInputDTO(
            email: 'duplicate@example.com'
        );

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof UserInputDTO
                    && $arg->email === 'duplicate@example.com';
            }))
            ->andThrow(new \Exception('Duplicate entry for email'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Duplicate entry for email');

        $this->service->create($userInputDTO);
    }

    public function test_create_user_calls_repository_within_transaction(): void
    {
        $userInputDTO = $this->createUserInputDTO();
        $expectedUser = $this->createUserModel(1, 'John Doe', 'john@example.com');

        $transactionCalled = false;
        $repositoryCalled = false;

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use (&$transactionCalled, &$repositoryCalled) {
                $transactionCalled = true;
                $this->assertFalse($repositoryCalled, 'Repository should not be called before transaction');
                return $callback();
            });

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($userInputDTO)
            ->andReturnUsing(function () use ($expectedUser, &$repositoryCalled, &$transactionCalled) {
                $repositoryCalled = true;
                $this->assertTrue($transactionCalled, 'Transaction should be started before repository call');
                return $expectedUser;
            });

        $result = $this->service->create($userInputDTO);

        $this->assertTrue($transactionCalled, 'Transaction should have been called');
        $this->assertTrue($repositoryCalled, 'Repository should have been called');
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_create_user_with_special_characters_in_name(): void
    {
        $userInputDTO = $this->createUserInputDTO(
            name: "José Antônio O'Brien",
            email: 'obrien@example.com',
            password: 'pass123'
        );
        $expectedUser = $this->createUserModel(3, "José Antônio O'Brien", 'obrien@example.com');

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof UserInputDTO
                    && $arg->name === "José Antônio O'Brien";
            }))
            ->andReturn($expectedUser);

        $result = $this->service->create($userInputDTO);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals("José Antônio O'Brien", $result->name);
    }

    public function test_create_user_with_long_password(): void
    {
        $longPassword = str_repeat('a', 100);
        $userInputDTO = $this->createUserInputDTO(
            password: $longPassword
        );
        $expectedUser = $this->createUserModel(4, 'John Doe', 'john@example.com');

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($longPassword) {
                return $arg instanceof UserInputDTO
                    && $arg->password === $longPassword;
            }))
            ->andReturn($expectedUser);

        $result = $this->service->create($userInputDTO);

        $this->assertInstanceOf(User::class, $result);
    }

    public function test_create_user_preserves_dto_data(): void
    {
        $userInputDTO = $this->createUserInputDTO(
            name: 'Test User',
            email: 'test@example.com',
            password: 'testpass'
        );
        $expectedUser = $this->createUserModel(5, 'Test User', 'test@example.com');

        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($userInputDTO) {
                $this->assertSame($userInputDTO, $arg, 'The same DTO instance should be passed to repository');
                $this->assertEquals('Test User', $arg->name);
                $this->assertEquals('test@example.com', $arg->email);
                $this->assertEquals('testpass', $arg->password);
                return true;
            }))
            ->andReturn($expectedUser);

        $result = $this->service->create($userInputDTO);

        $this->assertInstanceOf(User::class, $result);
    }
}

