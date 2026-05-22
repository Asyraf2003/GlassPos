<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

final class SupplierPaymentProofSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = '1';
        $admin = DB::table('users')->where('email', 'admin@gmail.com')->first();
        if ($admin) {
            $adminId = (string) $admin->id;
        }

        // 1. Ambil 12 data pembayaran yang masih pending
        $payments = DB::table('supplier_payments')
            ->where('proof_status', 'pending')
            ->inRandomOrder()
            ->limit(12) // Skala dinaikkan sesuai permintaan (minimal 8-12)
            ->get();

        if ($payments->isEmpty()) {
            $this->command->warn('Tidak ada data supplier_payments dengan status pending.');
            return;
        }

        // 2. Kumpulan file faktual dari storage Anda
        $filePool = [
            'ChgnDB5NnlqZodv2SkAZejen8tIp362scFLhet7a.png',
            'H0TiSrY0Llxjk4DBZc1FBD4xLdHu4cHEquy1UQAp.png',
            'pX517MwTv9f8fPsFkXB97IASYDBTh6gsc0n7RsJS.png',
            'QEiooVQgIJFqMFSBbp2TnDdHIBBi3yDhdrGXEEnF.png',
            '4xIxK15UPG6xiQBtkB96DUpUXCYG0kiesiAciqYT.png',
            'Af2JSmoM9KixSCx13kBqQAWGo9uG9lZ6ByQ5Obno.png',
            'pGdCvBvp1yO91owuveH21eNyxS3uR1aHRqYcsrpY.png',
            'TBuf8sNsjqeWKZ76cjbqOUx7Q8DC17AXSQw2DOFp.png',
        ];

        $inserts = [];

        // 3. Looping 12 pembayaran
        foreach ($payments as $payment) {
            $paymentId = (string) $payment->id;
            
            // Acak jumlah lampiran per pembayaran (1 sampai 3 file)
            $numFiles = rand(1, 3);
            
            // Acak urutan file pool agar tidak sama semua
            shuffle($filePool);
            $selectedFiles = array_slice($filePool, 0, $numFiles);

            foreach ($selectedFiles as $fileIndex => $filename) {
                $inserts[] = [
                    'id' => Str::uuid()->toString(),
                    'supplier_payment_id' => $paymentId,
                    // Tetap simulasikan path ke folder Anda
                    'storage_path' => "supplier-payment-proofs/simulated-folder/{$filename}", 
                    'original_filename' => "Bukti_Transfer_Bagian_" . ($fileIndex + 1) . ".png",
                    'mime_type' => 'image/png',
                    'file_size_bytes' => rand(150000, 500000),
                    'uploaded_at' => Carbon::parse($payment->paid_at)->addHours(rand(1, 12)),
                    'uploaded_by_actor_id' => $adminId,
                ];
            }

            // Update status pembayaran
            DB::table('supplier_payments')
                ->where('id', $paymentId)
                ->update(['proof_status' => 'uploaded']);
        }

        if ($inserts !== []) {
            DB::table('supplier_payment_proof_attachments')->insert($inserts);
            $this->command->info('Berhasil menanamkan ' . count($inserts) . ' lampiran ke ' . $payments->count() . ' pembayaran supplier!');
        }
    }
}
