<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        static::ensureTestDatabaseExists();
    }

    protected static function ensureTestDatabaseExists(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');

        try {
            DB::connection()->getPdo();
            
            $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);
            
            if (empty($exists)) {
                static::createTestDatabase($database);
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                static::createTestDatabase($database);
            } else {
                echo "\nErro ao conectar ao banco de dados:\n";
                echo "   {$e->getMessage()}\n\n";
                echo "💡 Dicas:\n";
                echo "   - Verifique se o Docker está rodando: docker-compose ps\n";
                echo "   - Suba os containers: docker-compose up -d\n";
                echo "   - Verifique o arquivo .env\n\n";
                
                throw $e;
            }
        }
    }


    protected static function createTestDatabase(string $database): void
    {
        try {
            echo "\nCriando banco de dados de teste: {$database}...\n";
            
            $connection = config('database.connections.mysql');
            $originalDatabase = $connection['database'];
            $connection['database'] = null;
            
            config(['database.connections.mysql_root' => $connection]);
            
            DB::connection('mysql_root')->statement("CREATE DATABASE IF NOT EXISTS `{$database}`");
            
            $username = config('database.connections.mysql.username');
            if ($username && $username !== 'root') {
                echo "Configurando permissões para usuário: {$username}...\n";
                DB::connection('mysql_root')->statement(
                    "GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$username}'@'%'"
                );
                DB::connection('mysql_root')->statement("FLUSH PRIVILEGES");
            }
            
            echo "Banco de dados criado com sucesso!\n\n";
            
            DB::purge('mysql');
            DB::reconnect('mysql');
            
        } catch (\Exception $e) {
            echo "\nErro ao criar banco de dados: {$e->getMessage()}\n\n";
            throw $e;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function isRunningInDocker(): bool
    {
        return file_exists('/.dockerenv') || 
               (getenv('DOCKER_CONTAINER') === 'true');
    }

    protected function debugInfo(string $message): void
    {
        if (getenv('TEST_DEBUG') === 'true') {
            echo "\n[DEBUG] {$message}\n";
        }
    }
}

