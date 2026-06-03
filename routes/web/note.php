<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\Note\NoteDetailPageController as AdminNoteDetailPageController;
use App\Adapters\In\Http\Controllers\Admin\Note\NoteHistoryPageController as AdminNoteHistoryPageController;
use App\Adapters\In\Http\Controllers\Admin\Note\NoteHistoryTableDataController as AdminNoteHistoryTableDataController;
use App\Adapters\In\Http\Controllers\Admin\Note\CreateNoteRevisionSurplusRefundDueController as AdminCreateNoteRevisionSurplusRefundDueController;
use App\Adapters\In\Http\Controllers\Admin\Note\RecordNoteRevisionSurplusRefundPaymentController as AdminRecordNoteRevisionSurplusRefundPaymentController;
use App\Adapters\In\Http\Controllers\Admin\Note\ReopenClosedNoteController as AdminReopenClosedNoteController;
use App\Adapters\In\Http\Controllers\Cashier\Note\CreateTransactionWorkspaceMobileUiLabPageController;
use App\Adapters\In\Http\Controllers\Cashier\Note\CreateTransactionWorkspacePageController;
use App\Adapters\In\Http\Controllers\Cashier\Note\EditTransactionWorkspacePageController;
use App\Adapters\In\Http\Controllers\Cashier\Note\GetTransactionWorkspaceDraftController;
use App\Adapters\In\Http\Controllers\Cashier\Note\NoteDetailPageController;
use App\Adapters\In\Http\Controllers\Cashier\Note\NoteHistoryPageController as CashierNoteHistoryPageController;
use App\Adapters\In\Http\Controllers\Cashier\Note\NoteHistoryTableDataController as CashierNoteHistoryTableDataController;
use App\Adapters\In\Http\Controllers\Cashier\Note\ProductLookupController;
use App\Adapters\In\Http\Controllers\Cashier\Note\SaveTransactionWorkspaceDraftController;
use App\Adapters\In\Http\Controllers\Note\AddNoteRowsController;
use App\Adapters\In\Http\Controllers\Note\CorrectPaidServiceOnlyWorkItemController;
use App\Adapters\In\Http\Controllers\Note\CorrectPaidWorkItemStatusController;
use App\Adapters\In\Http\Controllers\Note\CreateNoteController;
use App\Adapters\In\Http\Controllers\Note\RecordClosedNoteRefundController;
use App\Adapters\In\Http\Controllers\Note\RecordNotePaymentController;
use App\Adapters\In\Http\Controllers\Note\StoreNoteRevisionController;
use App\Adapters\In\Http\Controllers\Note\StoreTransactionWorkspaceController;
use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureAdminPageAccess;
use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureCashierAreaAccess;
use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureTransactionEntryAllowed;
use App\Adapters\In\Http\Middleware\Note\EnsureCashierNoteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'transaction.entry'])->group(function (): void {
    Route::post('/notes/create', CreateNoteController::class)->name('notes.create');
    Route::post('/notes/workspace/store', StoreTransactionWorkspaceController::class)->name('notes.workspace.store');
});


Route::middleware(['auth', EnsureAdminPageAccess::class, 'app.shell'])
    ->prefix('admin/notes')
    ->name('admin.notes.')
    ->group(function (): void {
        Route::get('/', AdminNoteHistoryPageController::class)->name('index');
        Route::get('/table', AdminNoteHistoryTableDataController::class)->name('table');
        Route::get('/products/lookup', ProductLookupController::class)->name('products.lookup');
        Route::get('/workspace/draft', GetTransactionWorkspaceDraftController::class)->name('workspace.draft.show');
        Route::post('/workspace/draft', SaveTransactionWorkspaceDraftController::class)->name('workspace.draft.save');
        Route::post(
            '/revision-settlements/{settlementId}/refund-due',
            AdminCreateNoteRevisionSurplusRefundDueController::class
        )->name('revision-settlements.refund-due.store');
        Route::post(
            '/revision-surplus-dispositions/{dispositionId}/refund-paid',
            AdminRecordNoteRevisionSurplusRefundPaymentController::class
        )->name('revision-surplus-dispositions.refund-paid.store');
        Route::get('/{noteId}/workspace/edit', EditTransactionWorkspacePageController::class)->name('workspace.edit');
        Route::get('/{noteId}', AdminNoteDetailPageController::class)->name('show');

        Route::middleware(EnsureTransactionEntryAllowed::class)->group(function (): void {
            Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');
            Route::post('/{noteId}/payments', RecordNotePaymentController::class)->name('payments.store');
            Route::post('/{noteId}/rows', AddNoteRowsController::class)->name('rows.store');
            Route::patch('/{noteId}/workspace', StoreNoteRevisionController::class)->name('workspace.update');
        });

        Route::post('/{noteId}/reopen', AdminReopenClosedNoteController::class)->name('reopen');
    });

Route::middleware(['auth', EnsureCashierAreaAccess::class, EnsureTransactionEntryAllowed::class, 'app.shell'])
    ->prefix('cashier/notes')
    ->name('cashier.notes.')
    ->group(function (): void {
        Route::get('/', CashierNoteHistoryPageController::class)->name('index');
        Route::get('/table', CashierNoteHistoryTableDataController::class)->name('table');

        Route::get('/products/lookup', ProductLookupController::class)->name('products.lookup');
        Route::get('/workspace/draft', GetTransactionWorkspaceDraftController::class)->name('workspace.draft.show');
        Route::post('/workspace/draft', SaveTransactionWorkspaceDraftController::class)->name('workspace.draft.save');
        Route::get('/workspace/create', CreateTransactionWorkspacePageController::class)->name('workspace.create');
        Route::get('/workspace/mobile-ui-lab/{variant?}', CreateTransactionWorkspaceMobileUiLabPageController::class)->where('variant', '0?[1-3]')->name('workspace.mobile-ui-lab');

        Route::middleware(EnsureCashierNoteAccess::class)->group(function (): void {
            Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');
            Route::get('/{noteId}/workspace/edit', EditTransactionWorkspacePageController::class)->name('workspace.edit');
            Route::get(
                '/prototype/{noteId}',
                fn (string $noteId): RedirectResponse => redirect()->route('cashier.notes.show', ['noteId' => $noteId])
            )->name('prototype.show');

            Route::get('/{noteId}', NoteDetailPageController::class)->name('show');
            Route::patch('/{noteId}/workspace', StoreNoteRevisionController::class)->name('workspace.update');
            Route::post('/{noteId}/rows', AddNoteRowsController::class)->name('rows.store');
            Route::post('/{noteId}/payments', RecordNotePaymentController::class)->name('payments.store');
            Route::post('/{noteId}/corrections/status', CorrectPaidWorkItemStatusController::class)->name('corrections.status.store');
            Route::post('/{noteId}/corrections/service-only', CorrectPaidServiceOnlyWorkItemController::class)->name('corrections.service-only.store');
        });
    });
