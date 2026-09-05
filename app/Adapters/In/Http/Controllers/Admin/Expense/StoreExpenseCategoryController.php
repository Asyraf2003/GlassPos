<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Expense;

use App\Adapters\In\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Application\Expense\UseCases\CreateAuditedExpenseCategoryHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class StoreExpenseCategoryController extends Controller
{
    public function __invoke(
        StoreExpenseCategoryRequest $request,
        CreateAuditedExpenseCategoryHandler $useCase,
    ): RedirectResponse {
        $data = $request->validated();
        $actorId = $request->user()?->getAuthIdentifier();

        $result = $useCase->handle(
            (string) $data['code'],
            (string) $data['name'],
            isset($data['description']) && $data['description'] !== ''
                ? (string) $data['description']
                : null,
            $actorId !== null ? (string) $actorId : null,
            'web_admin',
        );

        if ($result->isFailure()) {
            return back()
                ->withErrors(['expense_category' => $result->message() ?? 'Kategori pengeluaran gagal dibuat.'])
                ->withInput();
        }

        $source = trim((string) $request->input('source', ''));
        $payload = $result->data();
        $categoryData = is_array($payload['expense_category'] ?? null) ? $payload['expense_category'] : [];
        $categoryId = trim((string) ($categoryData['id'] ?? ''));

        if ($source === 'expense_create' && $categoryId !== '') {
            return redirect()
                ->route('admin.expenses.create', ['category_id' => $categoryId])
                ->with('success', 'Kategori pengeluaran berhasil dibuat.');
        }

        return redirect()
            ->route('admin.expenses.categories.index')
            ->with('success', 'Kategori pengeluaran berhasil dibuat.');
    }
}
