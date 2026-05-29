<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Application\Payment\UseCases\RecordCustomerRefundHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;
use Throwable;

final class PaymentRefundConcurrencyCharacterizationFeatureTest extends TestCase
{
    use SeedsMinimalNotePaymentFixture;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);

        parent::tearDown();
    }

    public function test_concurrent_payment_and_refund_on_same_note_remain_serialized_and_exact(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::fail('Payment/refund concurrency characterization requires pcntl_fork(). Current PHP runtime cannot prove true concurrent request behavior.');
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            self::fail('Payment/refund concurrency characterization requires a row-locking database driver such as mysql, mariadb, or pgsql. SQLite does not prove lockForUpdate() same-note serialization.');
        }

        $this->seedPaymentRefundConcurrencyNote();

        $workDir = storage_path('framework/testing/payment-refund-concurrency-' . (string) Str::uuid());
        $startFile = $workDir . '/start';

        if (!is_dir($workDir) && !mkdir($workDir, 0775, true) && !is_dir($workDir)) {
            self::fail('Failed to create payment/refund concurrency work directory.');
        }

        DB::disconnect();

        $children = [];

        foreach (['payment', 'refund'] as $workerType) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                self::fail('Failed to fork payment/refund concurrency worker.');
            }

            if ($pid === 0) {
                self::runWorker($workerType, $workDir, $startFile);
                exit(0);
            }

            $children[] = $pid;
        }

        touch($startFile);

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                self::fail('Payment/refund concurrency worker exited abnormally.');
            }
        }

        DB::reconnect();

        $results = [];

        foreach (['payment', 'refund'] as $workerType) {
            $resultFile = $workDir . '/' . $workerType . '.json';

            self::assertFileExists($resultFile, 'Payment/refund concurrency worker did not write a result file.');

            $decoded = json_decode((string) file_get_contents($resultFile), true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($decoded);
            $results[$workerType] = $decoded;
        }

        self::assertTrue(
            (bool) ($results['payment']['success'] ?? false),
            'Concurrent payment worker should succeed because the note has exactly 50000 outstanding before the new payment.'
        );

        self::assertTrue(
            (bool) ($results['refund']['success'] ?? false),
            'Concurrent refund worker should succeed because the existing payment has exactly 50000 refundable allocation.'
        );

        $allocatedTotal = (int) DB::table('payment_component_allocations')
            ->where('note_id', 'note-payment-refund-concurrency-1')
            ->sum('allocated_amount_rupiah');

        self::assertSame(
            100000,
            $allocatedTotal,
            'Payment allocation total should remain exact after concurrent payment/refund on the same note.'
        );

        $refundedTotal = (int) DB::table('refund_component_allocations')
            ->where('note_id', 'note-payment-refund-concurrency-1')
            ->sum('refunded_amount_rupiah');

        self::assertSame(
            50000,
            $refundedTotal,
            'Refund allocation total should remain exact after concurrent payment/refund on the same note.'
        );

        self::assertSame(2, DB::table('customer_payments')->count());
        self::assertSame(1, DB::table('customer_refunds')->count());

        $paymentIds = DB::table('customer_payments')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $allocatedPaymentIds = DB::table('payment_component_allocations')
            ->select('customer_payment_id')
            ->distinct()
            ->pluck('customer_payment_id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        foreach ($paymentIds as $paymentId) {
            self::assertContains(
                $paymentId,
                $allocatedPaymentIds,
                'A committed customer payment must not be left without component allocation after concurrent payment/refund.'
            );
        }

        self::assertDatabaseHas('refund_component_allocations', [
            'customer_payment_id' => 'payment-existing-concurrency-1',
            'note_id' => 'note-payment-refund-concurrency-1',
            'work_item_id' => 'wi-payment-refund-concurrency-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-payment-refund-concurrency-1',
            'refunded_amount_rupiah' => 50000,
        ]);
    }

    private static function runWorker(string $workerType, string $workDir, string $startFile): void
    {
        try {
            DB::purge();
            DB::reconnect();

            $deadline = microtime(true) + 10.0;

            while (!file_exists($startFile)) {
                if (microtime(true) > $deadline) {
                    throw new \RuntimeException('Timed out waiting for payment/refund concurrency start signal.');
                }

                usleep(1000);
            }

            if ($workerType === 'payment') {
                $result = app(RecordAndAllocateNotePaymentHandler::class)->handle(
                    'note-payment-refund-concurrency-1',
                    50000,
                    '2026-05-29',
                );
            } elseif ($workerType === 'refund') {
                $result = app(RecordCustomerRefundHandler::class)->handle(
                    'payment-existing-concurrency-1',
                    'note-payment-refund-concurrency-1',
                    50000,
                    '2026-05-29',
                    'Concurrent refund characterization',
                    'actor-payment-refund-concurrency-1',
                );
            } else {
                throw new \InvalidArgumentException('Unknown payment/refund concurrency worker type.');
            }

            file_put_contents(
                $workDir . '/' . $workerType . '.json',
                json_encode([
                    'worker' => $workerType,
                    'success' => $result->isSuccess(),
                    'message' => $result->message(),
                ], JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $e) {
            file_put_contents(
                $workDir . '/' . $workerType . '.json',
                json_encode([
                    'worker' => $workerType,
                    'success' => false,
                    'exception_class' => $e::class,
                    'exception_message' => $e->getMessage(),
                ], JSON_THROW_ON_ERROR)
            );
        } finally {
            DB::disconnect();
        }
    }

    private function seedPaymentRefundConcurrencyNote(): void
    {
        $this->seedNoteBase(
            'note-payment-refund-concurrency-1',
            'Budi Payment Refund Concurrent',
            '2026-05-29',
            100000,
            'open',
        );

        $this->seedWorkItemBase(
            'wi-payment-refund-concurrency-1',
            'note-payment-refund-concurrency-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-payment-refund-concurrency-1',
            'Servis Payment Refund Concurrent',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedCustomerPaymentBase(
            'payment-existing-concurrency-1',
            50000,
            '2026-05-29',
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-payment-refund-concurrency-1',
            'customer_payment_id' => 'payment-existing-concurrency-1',
            'note_id' => 'note-payment-refund-concurrency-1',
            'work_item_id' => 'wi-payment-refund-concurrency-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-payment-refund-concurrency-1',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);
    }
}
