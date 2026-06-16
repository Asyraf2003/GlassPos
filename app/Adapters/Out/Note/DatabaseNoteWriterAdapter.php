<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Core\Note\Note\Note;
use App\Ports\Out\Note\NoteWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteWriterAdapter implements NoteWriterPort
{
    public function create(Note $note): void
    {
        $now = now()->format('Y-m-d H:i:s');

        DB::table('notes')->insert([
            'id' => $note->id(),
            'customer_name' => $note->customerName(),
            'customer_phone' => $note->customerPhone(),
            'transaction_date' => $note->transactionDate()->format('Y-m-d'),
            'operational_note' => $note->operationalNote(),
            'due_date' => $note->dueDate()->format('Y-m-d'),
            'note_state' => $note->noteState(),
            'closed_at' => $note->closedAt()?->format('Y-m-d H:i:s'),
            'closed_by_actor_id' => $note->closedByActorId(),
            'reopened_at' => $note->reopenedAt()?->format('Y-m-d H:i:s'),
            'reopened_by_actor_id' => $note->reopenedByActorId(),
            'total_rupiah' => $note->totalRupiah()->amount(),
            'subtotal_before_note_tax_rupiah' => $note->subtotalBeforeNoteTaxRupiah()->amount(),
            'note_tax_input' => $note->noteTaxInput(),
            'note_tax_mode' => $note->noteTaxMode(),
            'note_tax_rate_basis_points' => $note->noteTaxRateBasisPoints(),
            'note_tax_amount_rupiah' => $note->noteTaxAmountRupiah()->amount(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateHeader(Note $note): void
    {
        DB::table('notes')->where('id', $note->id())->update([
            'customer_name' => $note->customerName(),
            'customer_phone' => $note->customerPhone(),
            'transaction_date' => $note->transactionDate()->format('Y-m-d'),
            'operational_note' => $note->operationalNote(),
            'due_date' => $note->dueDate()->format('Y-m-d'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateTotal(Note $note): void
    {
        DB::table('notes')->where('id', $note->id())->update([
            'total_rupiah' => $note->totalRupiah()->amount(),
            'subtotal_before_note_tax_rupiah' => $note->subtotalBeforeNoteTaxRupiah()->amount(),
            'note_tax_input' => $note->noteTaxInput(),
            'note_tax_mode' => $note->noteTaxMode(),
            'note_tax_rate_basis_points' => $note->noteTaxRateBasisPoints(),
            'note_tax_amount_rupiah' => $note->noteTaxAmountRupiah()->amount(),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateOperationalState(Note $note): void
    {
        DB::table('notes')->where('id', $note->id())->update([
            'note_state' => $note->noteState(),
            'closed_at' => $note->closedAt()?->format('Y-m-d H:i:s'),
            'closed_by_actor_id' => $note->closedByActorId(),
            'reopened_at' => $note->reopenedAt()?->format('Y-m-d H:i:s'),
            'reopened_by_actor_id' => $note->reopenedByActorId(),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
