<div id="sidebar">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo">
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ asset('assets/compiled/svg/logo.svg') }}" alt="Logo">
                    </a>
                </div>

                <div class="theme-toggle d-flex gap-2 align-items-center mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                        role="img" class="iconify iconify--system-uicons" width="20" height="20"
                        preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                        <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                opacity=".3"></path>
                            <g transform="translate(-210 -1)">
                                <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                <circle cx="220.5" cy="11.5" r="4"></circle>
                                <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2"></path>
                            </g>
                        </g>
                    </svg>

                    <div class="form-check form-switch fs-6">
                        <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                        <label class="form-check-label"></label>
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                        role="img" class="iconify iconify--mdi" width="20" height="20"
                        preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                        </path>
                    </svg>
                </div>

                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block">
                        <i class="bi bi-x bi-middle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Admin</li>

                <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-title">Master</li>

                <li class="sidebar-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.products.index') }}" class="sidebar-link">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Produk</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.services.index') }}" class="sidebar-link">
                        <i class="bi bi-wrench-adjustable"></i>
                        <span>Master Jasa</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.service-product-templates.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.service-product-templates.index') }}" class="sidebar-link">
                        <i class="bi bi-tools"></i>
                        <span>Template Jasa + Produk</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.suppliers.index') }}" class="sidebar-link">
                        <i class="bi bi-truck"></i>
                        <span>Pemasok</span>
                    </a>
                </li>

                <li class="sidebar-title">Operasional</li>

                <li class="sidebar-item {{ request()->routeIs('admin.procurement.supplier-invoices.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.procurement.supplier-invoices.index') }}" class="sidebar-link">
                        <i class="bi bi-receipt"></i>
                        <span>Pengadaan</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.notes.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.notes.index') }}" class="sidebar-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Nota Pelanggan</span>
                    </a>
                </li>


                <li class="sidebar-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.employees.index') }}" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Data Karyawan</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.payrolls.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.payrolls.index') }}" class="sidebar-link">
                        <i class="bi bi-cash-stack"></i>
                        <span>Gaji Karyawan</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.employee-debts.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.employee-debts.index') }}" class="sidebar-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Hutang Karyawan</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.expenses.index') }}" class="sidebar-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Biaya Operasional</span>
                    </a>
                </li>

                <li class="sidebar-title">Laporan</li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.transaction_cash_ledger.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.transaction_cash_ledger.index') }}" class="sidebar-link">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Buku Kas Transaksi</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.payroll.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.payroll.index') }}" class="sidebar-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Laporan Gaji</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.employee_debt.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.employee_debt.index') }}" class="sidebar-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Laporan Hutang</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.operational_profit.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.operational_profit.index') }}" class="sidebar-link">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Laba Kas Operasional</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.operational_expense.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.operational_expense.index') }}" class="sidebar-link">
                        <i class="bi bi-cash-coin"></i>
                        <span>Laporan Operasional</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.supplier_payable.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.supplier_payable.index') }}" class="sidebar-link">
                        <i class="bi bi-receipt"></i>
                        <span>Hutang Pemasok</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.inventory_stock_value.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.inventory_stock_value.index') }}" class="sidebar-link">
                        <i class="bi bi-box-seam"></i>
                        <span>Stok dan Persediaan</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.reports.transaction_summary.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reports.transaction_summary.index') }}" class="sidebar-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Laporan Transaksi per Nota</span>
                    </a>
                </li>

                <li class="sidebar-title">Sistem</li>

                <li class="sidebar-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link">
                        <i class="bi bi-shield-check"></i>
                        <span>Audit Log</span>
                    </a>
                </li>

                <li class="sidebar-title">Ganti Role</li>

                @if (($appShell['can_access_cashier_area'] ?? false) === true)
                    <li class="sidebar-item {{ request()->routeIs('cashier.*') ? 'active' : '' }}">
                        <a href="{{ route('cashier.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Masuk ke Area Kasir</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
