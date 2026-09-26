<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'glasspos_cancellation_browser'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_PORT') !== '3321') {
    throw new RuntimeException('Requires isolated cancellation browser database on localhost3321.');
}

final class CancellationLiveFixture extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;

    public function generate(): void
    {
        $this->setUp();
        self::assertSame(0, DB::table('notes')->count(), 'Requires fresh disposable schema.');
        $this->preparePrimitiveFixture();
        Carbon::setTestNow();
        $cashier = $this->loginAsKasir();
        $cashier->forceFill(['email' => 'cancellation@example.test'])->save();
        $create = $this->primitiveWorkspace([$this->primitiveItems()[0], $this->primitiveItems()[1]], 'c6-live-create');
        $create['note']['transaction_date'] = (new DateTimeImmutable('now', new DateTimeZone(config('app.timezone'))))->format('Y-m-d');
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $manifest = ['note_id' => $id, 'base' => DB::table('notes')->value('current_revision_id'), 'date' => $create['note']['transaction_date']];
        foreach (['show', 'cancel', 'restore'] as $action) {
            $manifest[$action] = parse_url(route('cashier.notes.'.$action, ['noteId' => $id]), PHP_URL_PATH);
        }
        file_put_contents('/tmp/glasspos-cancellation-live.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'Cancellation browser fixture ready.'.PHP_EOL;
    }
}

(new CancellationLiveFixture('generate'))->generate();
