<?php

declare(strict_types=1);

namespace Database\Seeders\Product;

final class ProductSeedCatalog
{
    /**
     * @return array{
     *   active_basic:list<array{
     *     code:string,
     *     name:string,
     *     brand:string,
     *     size:?int,
     *     price:int
     *   }>,
     *   active_edited:list<array{
     *     create:array{code:string,name:string,brand:string,size:?int,price:int},
     *     update:array{code:string,name:string,brand:string,size:?int,price:int}
     *   }>,
     *   soft_deleted:list<array{
     *     create:array{code:string,name:string,brand:string,size:?int,price:int}
     *   }>,
     *   recreated_after_delete:list<array{
     *     original:array{code:string,name:string,brand:string,size:?int,price:int},
     *     replacement:array{code:string,name:string,brand:string,size:?int,price:int}
     *   }>,
     *   legacy_incomplete_history:list<array{
     *     create:array{code:string,name:string,brand:string,size:?int,price:int},
     *     update:array{code:string,name:string,brand:string,size:?int,price:int}
     *   }>
     * }
     */
    public static function all(): array
    {
        return [
            'active_basic' => [
                ['code' => 'PRD-ACT-001', 'name' => 'Filter Oli Supra',       'brand' => 'Federal', 'size' => null, 'price' => 35000],
                ['code' => 'PRD-ACT-002', 'name' => 'Filter Udara Beat',      'brand' => 'Astra',   'size' => null, 'price' => 42000],
                ['code' => 'PRD-ACT-003', 'name' => 'Kampas Rem Vario',       'brand' => 'Nissin',  'size' => null, 'price' => 68000],
                ['code' => 'PRD-ACT-004', 'name' => 'Busi Iridium NMAX',      'brand' => 'NGK',     'size' => null, 'price' => 95000],
                ['code' => 'PRD-ACT-005', 'name' => 'Lampu Depan Scoopy',     'brand' => 'Stanley', 'size' => null, 'price' => 85000],
                ['code' => 'PRD-ACT-006', 'name' => 'V Belt PCX',             'brand' => 'Bando',   'size' => 150,  'price' => 185000],
                ['code' => 'PRD-ACT-007', 'name' => 'Roller Mio',             'brand' => 'Yamaha',  'size' => 100,  'price' => 56000],
                ['code' => 'PRD-ACT-008', 'name' => 'Kabel Gas Jupiter Z',    'brand' => 'YGP',     'size' => null, 'price' => 47000],
                ['code' => 'PRD-ACT-009', 'name' => 'Rantai Satria FU',       'brand' => 'DID',     'size' => 428,  'price' => 210000],
                ['code' => 'PRD-ACT-010', 'name' => 'Saringan Bensin Vixion', 'brand' => 'Mikuni',  'size' => null, 'price' => 39000],
                ['code' => 'PRD-ACT-011', 'name' => 'Piston Kit Tiger',       'brand' => 'FIM',     'size' => 200,  'price' => 275000],
                ['code' => 'PRD-ACT-012', 'name' => 'Shockbreaker Aerox',     'brand' => 'KYB',     'size' => 300,  'price' => 425000],
                ['code' => 'PRD-ACT-013', 'name' => 'Ban Luar Beat',          'brand' => 'FDR',     'size' => 80,   'price' => 240000],
                ['code' => 'PRD-ACT-014', 'name' => 'Ban Dalam Vario',        'brand' => 'FDR',     'size' => 80,   'price' => 55000],
                ['code' => 'PRD-ACT-015', 'name' => 'Oli Gardan Mio',         'brand' => 'Yamalube','size' => null, 'price' => 28000],
                ['code' => 'PRD-ACT-016', 'name' => 'Seal Shock NMAX',        'brand' => 'Showa',   'size' => 33,   'price' => 74000],
                ['code' => 'PRD-ACT-017', 'name' => 'Klep In Supra X',        'brand' => 'NPP',     'size' => 100,  'price' => 66000],
                ['code' => 'PRD-ACT-018', 'name' => 'Spion Scoopy',           'brand' => 'Astra',   'size' => null, 'price' => 88000],
                ['code' => 'PRD-ACT-019', 'name' => 'CDI FizR',               'brand' => 'BRT',     'size' => null, 'price' => 315000],
                ['code' => 'PRD-ACT-020', 'name' => 'Kanvas Kopling Megapro', 'brand' => 'FCC',     'size' => 125,  'price' => 132000],
            ],
            'active_edited' => [
                [
                    'create' => ['code' => 'PRD-EDT-001', 'name' => 'Filter Oli Beat Lama',      'brand' => 'Federal', 'size' => null, 'price' => 32000],
                    'update' => ['code' => 'PRD-EDT-001', 'name' => 'Filter Oli Beat',           'brand' => 'Federal', 'size' => null, 'price' => 35000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-002', 'name' => 'Kampas Rem Vario CBS',      'brand' => 'Nissn',   'size' => null, 'price' => 62000],
                    'update' => ['code' => 'PRD-EDT-002', 'name' => 'Kampas Rem Vario CBS',      'brand' => 'Nissin',  'size' => null, 'price' => 68000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-003', 'name' => 'Busi Nmax',                 'brand' => 'NGK',     'size' => null, 'price' => 70000],
                    'update' => ['code' => 'PRD-EDT-003', 'name' => 'Busi Iridium NMAX Turbo',  'brand' => 'NGK',     'size' => null, 'price' => 95000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-004', 'name' => 'V Belt Pcx',                'brand' => 'Bando',   'size' => 125,  'price' => 165000],
                    'update' => ['code' => 'PRD-EDT-004', 'name' => 'V Belt PCX ABS',            'brand' => 'Bando',   'size' => 150,  'price' => 185000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-005', 'name' => 'Roller Mio Sporty',         'brand' => 'Yamaha',  'size' => 90,   'price' => 49000],
                    'update' => ['code' => 'PRD-EDT-005', 'name' => 'Roller Mio Sporty',         'brand' => 'Yamaha',  'size' => 100,  'price' => 56000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-006', 'name' => 'Rantai Satria',             'brand' => 'DID',     'size' => 420,  'price' => 185000],
                    'update' => ['code' => 'PRD-EDT-006', 'name' => 'Rantai Satria FU Racing',   'brand' => 'DID',     'size' => 428,  'price' => 210000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-007', 'name' => 'Shockbreaker Aerox Old',    'brand' => 'KYB',     'size' => 280,  'price' => 390000],
                    'update' => ['code' => 'PRD-EDT-007', 'name' => 'Shockbreaker Aerox Pro',    'brand' => 'KYB',     'size' => 300,  'price' => 425000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-008', 'name' => 'Seal Shock Nmax',           'brand' => 'Showa',   'size' => 31,   'price' => 68000],
                    'update' => ['code' => 'PRD-EDT-008', 'name' => 'Seal Shock NMAX Depan',     'brand' => 'Showa',   'size' => 33,   'price' => 74000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-009', 'name' => 'Spion Scoopy Merah',        'brand' => 'Astra',   'size' => null, 'price' => 82000],
                    'update' => ['code' => 'PRD-EDT-009', 'name' => 'Spion Scoopy Kiri',         'brand' => 'Astra',   'size' => null, 'price' => 88000],
                ],
                [
                    'create' => ['code' => 'PRD-EDT-010', 'name' => 'CDI F1ZR',                  'brand' => 'BRT',     'size' => null, 'price' => 290000],
                    'update' => ['code' => 'PRD-EDT-010', 'name' => 'CDI FizR Racing',           'brand' => 'BRT',     'size' => null, 'price' => 315000],
                ],
            ],
            'soft_deleted' => [
                ['create' => ['code' => 'PRD-DEL-001', 'name' => 'Karet Footstep Beat',     'brand' => 'Astra',  'size' => null, 'price' => 26000]],
                ['create' => ['code' => 'PRD-DEL-002', 'name' => 'Seal Knalpot Vario',      'brand' => 'TDR',    'size' => null, 'price' => 18000]],
                ['create' => ['code' => 'PRD-DEL-003', 'name' => 'Bohlam Sen Scoopy',       'brand' => 'Philips','size' => null, 'price' => 22000]],
                ['create' => ['code' => 'PRD-DEL-004', 'name' => 'Karet CVT Mio',           'brand' => 'Yamaha', 'size' => null, 'price' => 31000]],
                ['create' => ['code' => 'PRD-DEL-005', 'name' => 'Kabel Kopling Tiger',     'brand' => 'YGP',    'size' => null, 'price' => 47000]],
                ['create' => ['code' => 'PRD-DEL-006', 'name' => 'Tutup Pentil NMAX',       'brand' => 'FDR',    'size' => null, 'price' => 12000]],
                ['create' => ['code' => 'PRD-DEL-007', 'name' => 'Soket Lampu Aerox',       'brand' => 'Osram',  'size' => null, 'price' => 34000]],
                ['create' => ['code' => 'PRD-DEL-008', 'name' => 'O Ring Busi Supra X',     'brand' => 'NPP',    'size' => null, 'price' => 9000]],
            ],
            'recreated_after_delete' => [
                [
                    'original' => ['code' => 'PRD-RCR-001', 'name' => 'Piston Kit Beat',     'brand' => 'FIM',     'size' => 100, 'price' => 180000],
                    'replacement' => ['code' => 'PRD-RCR-001', 'name' => 'Piston Kit Beat',  'brand' => 'FIM',     'size' => 100, 'price' => 185000],
                ],
                [
                    'original' => ['code' => 'PRD-RCR-002', 'name' => 'Shockbreaker Vario',  'brand' => 'KYB',     'size' => 300, 'price' => 410000],
                    'replacement' => ['code' => 'PRD-RCR-002', 'name' => 'Shockbreaker Vario','brand' => 'KYB',    'size' => 300, 'price' => 425000],
                ],
                [
                    'original' => ['code' => 'PRD-RCR-003', 'name' => 'Ban Luar Scoopy',     'brand' => 'FDR',     'size' => 90,  'price' => 225000],
                    'replacement' => ['code' => 'PRD-RCR-003', 'name' => 'Ban Luar Scoopy',  'brand' => 'FDR',     'size' => 90,  'price' => 235000],
                ],
                [
                    'original' => ['code' => 'PRD-RCR-004', 'name' => 'Seal Shock PCX',      'brand' => 'Showa',   'size' => 33,  'price' => 72000],
                    'replacement' => ['code' => 'PRD-RCR-004', 'name' => 'Seal Shock PCX',   'brand' => 'Showa',   'size' => 33,  'price' => 76000],
                ],
            ],
            'legacy_incomplete_history' => [
                [
                    'create' => ['code' => 'PRD-LEG-001', 'name' => 'Filter Oli Legacy Beat',   'brand' => 'Federal', 'size' => null, 'price' => 30000],
                    'update' => ['code' => 'PRD-LEG-001', 'name' => 'Filter Oli Legacy Beat',   'brand' => 'Federal', 'size' => null, 'price' => 34000],
                ],
                [
                    'create' => ['code' => 'PRD-LEG-002', 'name' => 'Busi Legacy Vario',        'brand' => 'NGK',     'size' => null, 'price' => 60000],
                    'update' => ['code' => 'PRD-LEG-002', 'name' => 'Busi Legacy Vario Iridium','brand' => 'NGK',     'size' => null, 'price' => 92000],
                ],
                [
                    'create' => ['code' => 'PRD-LEG-003', 'name' => 'Rantai Legacy Satria',     'brand' => 'DID',     'size' => 420,  'price' => 175000],
                    'update' => ['code' => 'PRD-LEG-003', 'name' => 'Rantai Legacy Satria FU',  'brand' => 'DID',     'size' => 428,  'price' => 205000],
                ],
                [
                    'create' => ['code' => 'PRD-LEG-004', 'name' => 'Spion Legacy Scoopy',      'brand' => 'Astra',   'size' => null, 'price' => 78000],
                    'update' => ['code' => 'PRD-LEG-004', 'name' => 'Spion Legacy Scoopy',      'brand' => 'Astra',   'size' => null, 'price' => 86000],
                ],
            ],
        ];
    }
}
