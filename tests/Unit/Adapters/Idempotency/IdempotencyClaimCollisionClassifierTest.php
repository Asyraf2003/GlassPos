<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Idempotency;

use App\Adapters\Out\Idempotency\DatabaseIdempotencyRecordAdapter;
use App\Adapters\Out\Idempotency\IdempotencyClaimCollisionClassifier;
use App\Ports\Out\IdempotencyClaimConflictException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

final class IdempotencyClaimCollisionClassifierTest extends TestCase
{
    public static function errors(): array
    {
        return [
            'MariaDB canonical' => ['23000', 1062, "Duplicate entry 'x' for key 'idempotency_records_scope_key_unique'", true],
            'MySQL qualified' => ['23000', 1062, "Duplicate entry 'x' for key 'idempotency_records.idempotency_records_scope_key_unique'", true],
            'Postgres canonical' => ['23505', 7, 'duplicate key value violates unique constraint "idempotency_records_scope_key_unique"', true],
            'primary key' => ['23000', 1062, "Duplicate entry 'idempotency_records_scope_key_unique' for key 'PRIMARY'", false],
            'similar name' => ['23000', 1062, "Duplicate entry 'x' for key 'idempotency_records_scope_key_unique_other'", false],
            'foreign key' => ['23000', 1452, "for key 'idempotency_records_scope_key_unique'", false],
            'unknown state' => ['HY000', 1062, "for key 'idempotency_records_scope_key_unique'", false],
            'Postgres unrelated' => ['23505', 7, 'duplicate key value violates unique constraint "another_constraint"', false],
        ];
    }

    #[DataProvider('errors')]
    public function test_only_exact_canonical_claim_collision_is_translated(string $state, int $code, string $message, bool $expected): void
    {
        $previous = new PDOException($message);
        $previous->errorInfo = [$state, $code, $message];
        $error = new UniqueConstraintViolationException('mysql', 'insert into idempotency_records', [], $previous);
        self::assertSame($expected, (new IdempotencyClaimCollisionClassifier())->matches($error));
        DB::shouldReceive('table')->once()->with('idempotency_records')->andReturnSelf();
        DB::shouldReceive('insert')->once()->andThrow($error);
        $caught = null;
        try {
            (new DatabaseIdempotencyRecordAdapter())->createProcessing('actor', 'create_note_revision', 'key', 'hash');
        } catch (Throwable $e) {
            $caught = $e;
        }
        if ($expected) {
            self::assertInstanceOf(IdempotencyClaimConflictException::class, $caught);
            self::assertSame($error, $caught->getPrevious());
        } else {
            self::assertSame($error, $caught, 'Unrelated unique/database errors must propagate unchanged.');
        }
    }

    public function test_non_unique_database_error_propagates_unchanged(): void
    {
        $error = new QueryException('mysql', 'insert', [], new PDOException('connection failure'));
        DB::shouldReceive('table')->once()->with('idempotency_records')->andReturnSelf();
        DB::shouldReceive('insert')->once()->andThrow($error);
        try {
            (new DatabaseIdempotencyRecordAdapter())->createProcessing('actor', 'create_note_revision', 'key', 'hash');
            self::fail('Database error must escape.');
        } catch (QueryException $caught) {
            self::assertSame($error, $caught);
        }
    }
}
