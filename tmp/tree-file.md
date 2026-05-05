[asyraf@arch app]$ tree -L9 app database docs lang mk resources routes scripts tests tmp
app
├── Adapters
│   ├── In
│   │   └── Http
│   │       ├── Controllers
│   │       │   ├── Admin
│   │       │   │   ├── AdminDashboardAnalyticsPayloadController.php
│   │       │   │   ├── AdminDashboardPageController.php
│   │       │   │   ├── AuditLog
│   │       │   │   │   └── AuditLogIndexPageController.php
│   │       │   │   ├── Employee
│   │       │   │   │   ├── CreateEmployeePageController.php
│   │       │   │   │   ├── EditEmployeePageController.php
│   │       │   │   │   ├── EmployeeDetailPageController.php
│   │       │   │   │   ├── EmployeeIndexPageController.php
│   │       │   │   │   ├── EmployeePayrollDetailPageController.php
│   │       │   │   │   ├── EmployeePayrollTableDataController.php
│   │       │   │   │   ├── EmployeeTableDataController.php
│   │       │   │   │   ├── StoreEmployeeController.php
│   │       │   │   │   └── UpdateEmployeeController.php
│   │       │   │   ├── EmployeeDebt
│   │       │   │   │   ├── CreateEmployeeDebtPageController.php
│   │       │   │   │   ├── EmployeeDebtDetailPageController.php
│   │       │   │   │   ├── EmployeeDebtIndexPageController.php
│   │       │   │   │   ├── EmployeeDebtPrincipalPageController.php
│   │       │   │   │   ├── EmployeeDebtTableDataController.php
│   │       │   │   │   ├── StoreEmployeeDebtAdjustmentController.php
│   │       │   │   │   ├── StoreEmployeeDebtController.php
│   │       │   │   │   ├── StoreEmployeeDebtPaymentController.php
│   │       │   │   │   └── StoreEmployeeDebtPaymentReversalController.php
│   │       │   │   ├── Expense
│   │       │   │   │   ├── ActivateExpenseCategoryController.php
│   │       │   │   │   ├── CreateExpenseCategoryPageController.php
│   │       │   │   │   ├── CreateExpensePageController.php
│   │       │   │   │   ├── DeactivateExpenseCategoryController.php
│   │       │   │   │   ├── EditExpenseCategoryPageController.php
│   │       │   │   │   ├── ExpenseCategoryIndexPageController.php
│   │       │   │   │   ├── ExpenseCategoryTableDataController.php
│   │       │   │   │   ├── ExpenseIndexPageController.php
│   │       │   │   │   ├── ExpenseTableDataController.php
│   │       │   │   │   ├── SoftDeleteOperationalExpenseController.php
│   │       │   │   │   ├── StoreExpenseCategoryController.php
│   │       │   │   │   ├── StoreExpenseController.php
│   │       │   │   │   └── UpdateExpenseCategoryController.php
│   │       │   │   ├── Note
│   │       │   │   │   ├── NoteDetailPageController.php
│   │       │   │   │   ├── NoteHistoryPageController.php
│   │       │   │   │   ├── NoteHistoryTableDataController.php
│   │       │   │   │   └── ReopenClosedNoteController.php
│   │       │   │   ├── Payroll
│   │       │   │   │   ├── CreatePayrollPageController.php
│   │       │   │   │   ├── PayrollIndexPageController.php
│   │       │   │   │   ├── PayrollTableDataController.php
│   │       │   │   │   ├── StorePayrollController.php
│   │       │   │   │   └── StorePayrollReversalController.php
│   │       │   │   ├── Procurement
│   │       │   │   │   ├── AttachSupplierPaymentProofController.php
│   │       │   │   │   ├── Concerns
│   │       │   │   │   │   ├── BuildsProcurementInvoiceDetailLinesView.php
│   │       │   │   │   │   ├── BuildsProcurementInvoiceDetailPaymentsView.php
│   │       │   │   │   │   ├── BuildsProcurementInvoiceDetailPolicyView.php
│   │       │   │   │   │   ├── BuildsProcurementInvoiceDetailSummaryView.php
│   │       │   │   │   │   ├── BuildsProcurementInvoiceDetailViewData.php
│   │       │   │   │   │   └── FormatsProcurementInvoiceDetailViewValue.php
│   │       │   │   │   ├── CreateSupplierInvoicePageController.php
│   │       │   │   │   ├── EditSupplierInvoicePageController.php
│   │       │   │   │   ├── ProcurementInvoiceDetailPageController.php
│   │       │   │   │   ├── ProcurementInvoiceIndexPageController.php
│   │       │   │   │   ├── ProcurementInvoicePaymentProofPageController.php
│   │       │   │   │   ├── ProcurementInvoiceTableDataController.php
│   │       │   │   │   ├── ProductLookupController.php
│   │       │   │   │   ├── ReceiveSupplierInvoiceController.php
│   │       │   │   │   ├── RecordSupplierPaymentController.php
│   │       │   │   │   ├── ReverseSupplierPaymentController.php
│   │       │   │   │   ├── ReverseSupplierReceiptController.php
│   │       │   │   │   ├── ReviseSupplierInvoicePageController.php
│   │       │   │   │   ├── ServeSupplierPaymentProofAttachmentController.php
│   │       │   │   │   ├── StoreSupplierInvoiceController.php
│   │       │   │   │   ├── SupplierLookupController.php
│   │       │   │   │   ├── Support
│   │       │   │   │   │   └── EditSupplierInvoiceLineItemsViewBuilder.php
│   │       │   │   │   ├── UpdateSupplierInvoiceController.php
│   │       │   │   │   └── VoidSupplierInvoiceController.php
│   │       │   │   ├── Product
│   │       │   │   │   ├── CreateProductPageController.php
│   │       │   │   │   ├── DeleteProductController.php
│   │       │   │   │   ├── EditProductPageController.php
│   │       │   │   │   ├── EditProductStockPageController.php
│   │       │   │   │   ├── ProductIndexPageController.php
│   │       │   │   │   ├── ProductTableDataController.php
│   │       │   │   │   ├── RecordProductStockAdjustmentController.php
│   │       │   │   │   ├── RestoreProductController.php
│   │       │   │   │   ├── ReverseProductStockAdjustmentController.php
│   │       │   │   │   ├── ShowProductPageController.php
│   │       │   │   │   ├── StoreProductController.php
│   │       │   │   │   └── UpdateProductController.php
│   │       │   │   ├── Reporting
│   │       │   │   │   ├── EmployeeDebtReportExcelExportController.php
│   │       │   │   │   ├── EmployeeDebtReportPageController.php
│   │       │   │   │   ├── EmployeeDebtReportPdfExportController.php
│   │       │   │   │   ├── InventoryStockValueReportExcelExportController.php
│   │       │   │   │   ├── InventoryStockValueReportPageController.php
│   │       │   │   │   ├── InventoryStockValueReportPdfExportController.php
│   │       │   │   │   ├── OperationalExpenseReportExcelExportController.php
│   │       │   │   │   ├── OperationalExpenseReportPageController.php
│   │       │   │   │   ├── OperationalExpenseReportPdfExportController.php
│   │       │   │   │   ├── OperationalProfitReportExcelExportController.php
│   │       │   │   │   ├── OperationalProfitReportPageController.php
│   │       │   │   │   ├── OperationalProfitReportPdfExportController.php
│   │       │   │   │   ├── PayrollReportExcelExportController.php
│   │       │   │   │   ├── PayrollReportPageController.php
│   │       │   │   │   ├── PayrollReportPdfExportController.php
│   │       │   │   │   ├── SupplierPayableReportExcelExportController.php
│   │       │   │   │   ├── SupplierPayableReportPageController.php
│   │       │   │   │   ├── SupplierPayableReportPdfExportController.php
│   │       │   │   │   ├── TransactionCashLedgerExcelExportController.php
│   │       │   │   │   ├── TransactionCashLedgerPageController.php
│   │       │   │   │   ├── TransactionCashLedgerPdfExportController.php
│   │       │   │   │   ├── TransactionReportExcelExportController.php
│   │       │   │   │   ├── TransactionReportPageController.php
│   │       │   │   │   └── TransactionReportPdfExportController.php
│   │       │   │   └── Supplier
│   │       │   │       ├── EditSupplierPageController.php
│   │       │   │       ├── SupplierIndexPageController.php
│   │       │   │       ├── SupplierTableDataController.php
│   │       │   │       └── UpdateSupplierController.php
│   │       │   ├── Auth
│   │       │   │   ├── AuthenticateController.php
│   │       │   │   ├── LoginPageController.php
│   │       │   │   └── LogoutController.php
│   │       │   ├── Cashier
│   │       │   │   ├── CashierDashboardPageController.php
│   │       │   │   └── Note
│   │       │   │       ├── CreateTransactionWorkspacePageController.php
│   │       │   │       ├── EditTransactionWorkspacePageController.php
│   │       │   │       ├── GetTransactionWorkspaceDraftController.php
│   │       │   │       ├── NoteDetailPageController.php
│   │       │   │       ├── NoteHistoryPageController.php
│   │       │   │       ├── NoteHistoryTableDataController.php
│   │       │   │       ├── ProductLookupController.php
│   │       │   │       └── SaveTransactionWorkspaceDraftController.php
│   │       │   ├── EmployeeFinance
│   │       │   │   ├── DisbursePayrollController.php
│   │       │   │   └── RegisterEmployeeController.php
│   │       │   ├── HealthCheckController.php
│   │       │   ├── IdentityAccess
│   │       │   │   ├── DisableAdminTransactionCapabilityController.php
│   │       │   │   └── EnableAdminTransactionCapabilityController.php
│   │       │   ├── Note
│   │       │   │   ├── AddNoteRowsController.php
│   │       │   │   ├── CorrectPaidServiceOnlyWorkItemController.php
│   │       │   │   ├── CorrectPaidWorkItemStatusController.php
│   │       │   │   ├── CreateNoteController.php
│   │       │   │   ├── CreateNoteProductRowAction.php
│   │       │   │   ├── CreateNoteRowsAction.php
│   │       │   │   ├── CreateNoteServiceRowAction.php
│   │       │   │   ├── RecordClosedNoteRefundController.php
│   │       │   │   ├── RecordNotePaymentController.php
│   │       │   │   ├── StoreNoteRevisionController.php
│   │       │   │   ├── StoreTransactionWorkspaceController.php
│   │       │   │   ├── Support
│   │       │   │   │   └── NoteRouteAreaResolver.php
│   │       │   │   └── UpdateTransactionWorkspaceController.php
│   │       │   ├── Procurement
│   │       │   │   ├── CreateSupplierInvoiceController.php
│   │       │   │   └── ReceiveSupplierInvoiceController.php
│   │       │   ├── ProductCatalog
│   │       │   │   ├── CreateProductController.php
│   │       │   │   └── UpdateProductController.php
│   │       │   └── PushNotification
│   │       │       ├── DeletePushSubscriptionController.php
│   │       │       └── StorePushSubscriptionController.php
│   │       ├── Middleware
│   │       │   ├── IdentityAccess
│   │       │   │   ├── AppShellDataBuilder.php
│   │       │   │   ├── EnsureAdminPageAccess.php
│   │       │   │   ├── EnsureCashierAreaAccess.php
│   │       │   │   ├── EnsureTransactionEntryAllowed.php
│   │       │   │   ├── ShareAppShellData.php
│   │       │   │   ├── UiFeedbackDataBuilder.php
│   │       │   │   └── UiFeedbackMessageNormalizer.php
│   │       │   └── Note
│   │       │       └── EnsureCashierNoteAccess.php
│   │       ├── Presenters
│   │       │   ├── Admin
│   │       │   │   └── Product
│   │       │   │       ├── Concerns
│   │       │   │       │   ├── FormatsProductDetailIdentity.php
│   │       │   │       │   └── FormatsProductDetailTimeline.php
│   │       │   │       └── ProductDetailPagePresenter.php
│   │       │   ├── JsonPresenter.php
│   │       │   └── Response
│   │       │       └── JsonResultResponder.php
│   │       ├── Requests
│   │       │   ├── Auth
│   │       │   │   └── LoginRequest.php
│   │       │   ├── EmployeeFinance
│   │       │   │   ├── AdjustEmployeeDebtPrincipalRequest.php
│   │       │   │   ├── DisbursePayrollRequest.php
│   │       │   │   ├── EmployeeDebtTableQueryRequest.php
│   │       │   │   ├── EmployeePayrollTableQueryRequest.php
│   │       │   │   ├── EmployeeTableQueryRequest.php
│   │       │   │   ├── PayEmployeeDebtRequest.php
│   │       │   │   ├── PayrollTableQueryRequest.php
│   │       │   │   ├── RecordEmployeeDebtRequest.php
│   │       │   │   ├── RegisterEmployeeRequest.php
│   │       │   │   ├── ReverseEmployeeDebtPaymentRequest.php
│   │       │   │   ├── ReversePayrollDisbursementRequest.php
│   │       │   │   └── UpdateEmployeeProfileRequest.php
│   │       │   ├── Expense
│   │       │   │   ├── ExpenseCategoryTableQueryRequest.php
│   │       │   │   ├── ExpenseTableQueryRequest.php
│   │       │   │   ├── StoreExpenseCategoryRequest.php
│   │       │   │   ├── StoreExpenseRequest.php
│   │       │   │   └── UpdateExpenseCategoryRequest.php
│   │       │   ├── IdentityAccess
│   │       │   │   ├── DisableAdminTransactionCapabilityRequest.php
│   │       │   │   └── EnableAdminTransactionCapabilityRequest.php
│   │       │   ├── Note
│   │       │   │   ├── AddNoteRowsRequest.php
│   │       │   │   ├── AdminNoteTableQueryRequest.php
│   │       │   │   ├── CashierNoteTableQueryRequest.php
│   │       │   │   ├── CorrectPaidServiceOnlyWorkItemRequest.php
│   │       │   │   ├── CorrectPaidWorkItemStatusRequest.php
│   │       │   │   ├── CreateNoteRequest.php
│   │       │   │   ├── CreateNoteRowInputNormalizer.php
│   │       │   │   ├── CreateNoteRowValidator.php
│   │       │   │   ├── RecordClosedNoteRefundRequest.php
│   │       │   │   ├── RecordNotePaymentBusinessValidator.php
│   │       │   │   ├── RecordNotePaymentInputNormalizer.php
│   │       │   │   ├── RecordNotePaymentRequest.php
│   │       │   │   ├── ReopenClosedNoteRequest.php
│   │       │   │   ├── SaveTransactionWorkspaceDraftRequest.php
│   │       │   │   ├── StoreNoteRevisionRequest.php
│   │       │   │   ├── StoreTransactionWorkspaceExternalPurchaseLineNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspaceGrandTotalCalculator.php
│   │       │   │   ├── StoreTransactionWorkspaceInputNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspaceItemNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspaceItemValidator.php
│   │       │   │   ├── StoreTransactionWorkspaceNoteNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspacePaymentNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspacePaymentValidator.php
│   │       │   │   ├── StoreTransactionWorkspaceProductItemValidator.php
│   │       │   │   ├── StoreTransactionWorkspaceProductLineNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspaceRequest.php
│   │       │   │   ├── StoreTransactionWorkspaceRules.php
│   │       │   │   ├── StoreTransactionWorkspaceServiceItemValidator.php
│   │       │   │   ├── StoreTransactionWorkspaceServiceNormalizer.php
│   │       │   │   ├── StoreTransactionWorkspaceValidator.php
│   │       │   │   ├── UpdateTransactionWorkspaceInputNormalizer.php
│   │       │   │   ├── UpdateTransactionWorkspaceRequest.php
│   │       │   │   ├── UpdateTransactionWorkspaceRules.php
│   │       │   │   └── UpdateTransactionWorkspaceValidator.php
│   │       │   ├── Procurement
│   │       │   │   ├── CreateSupplierInvoiceDatePostValidation.php
│   │       │   │   ├── CreateSupplierInvoiceDuplicateNumberPostValidation.php
│   │       │   │   ├── CreateSupplierInvoiceDuplicateProductPostValidation.php
│   │       │   │   ├── CreateSupplierInvoiceGrandTotalStorageLimitValidation.php
│   │       │   │   ├── CreateSupplierInvoiceInputNormalizer.php
│   │       │   │   ├── CreateSupplierInvoiceLinesPostValidation.php
│   │       │   │   ├── CreateSupplierInvoicePostValidator.php
│   │       │   │   ├── CreateSupplierInvoiceRequest.php
│   │       │   │   ├── ProcurementInvoiceTableQueryRequest.php
│   │       │   │   ├── ReceiveSupplierInvoiceRequest.php
│   │       │   │   ├── ReverseSupplierPaymentRequest.php
│   │       │   │   ├── ReverseSupplierReceiptRequest.php
│   │       │   │   ├── SupplierTableQueryRequest.php
│   │       │   │   ├── UpdateSupplierInvoiceRequest.php
│   │       │   │   ├── UpdateSupplierRequest.php
│   │       │   │   └── VoidSupplierInvoiceRequest.php
│   │       │   ├── ProductCatalog
│   │       │   │   ├── CreateProductRequest.php
│   │       │   │   ├── ProductTableQueryRequest.php
│   │       │   │   └── UpdateProductRequest.php
│   │       │   ├── PushNotification
│   │       │   │   ├── DeletePushSubscriptionRequest.php
│   │       │   │   └── StorePushSubscriptionRequest.php
│   │       │   └── Reporting
│   │       │       ├── EmployeeDebtReportPageRequest.php
│   │       │       ├── InventoryStockValueReportPageRequest.php
│   │       │       ├── OperationalExpenseReportPageRequest.php
│   │       │       ├── OperationalProfitReportPageRequest.php
│   │       │       ├── PayrollReportPageRequest.php
│   │       │       ├── SupplierPayableReportPageRequest.php
│   │       │       ├── TransactionCashLedgerPageRequest.php
│   │       │       └── TransactionReportPageRequest.php
│   │       ├── Support
│   │       │   └── ReportArrayPaginator.php
│   │       └── ViewData
│   │           └── Admin
│   │               ├── AdminDashboardFilterDrawerViewData.php
│   │               └── AdminDashboardReportExportShortcuts.php
│   └── Out
│       ├── Audit
│       │   ├── AuditJsonPayload.php
│       │   ├── AuditLegacyEntityResolver.php
│       │   ├── AuditLogAdminEntrySorter.php
│       │   ├── AuditLogAdminListQuery.php
│       │   ├── AuditLogAdminQueryFilters.php
│       │   ├── AuditLogAdminRowMapper.php
│       │   ├── AuditLogAdminRowsQuery.php
│       │   ├── AuditReasonResolver.php
│       │   ├── DatabaseAuditLogAdapter.php
│       │   ├── DatabaseAuditLogReaderAdapter.php
│       │   └── NullAuditLogAdapter.php
│       ├── Auth
│       │   └── LaravelUuidAdapter.php
│       ├── Clock
│       │   └── SystemClockAdapter.php
│       ├── EmployeeFinance
│       │   ├── Concerns
│       │   │   ├── EmployeeVersionRevisionLookup.php
│       │   │   ├── EmployeeWritePayloads.php
│       │   │   ├── PersistsVersionedEmployeeWrites.php
│       │   │   └── RecordsEmployeeHistory.php
│       │   ├── DatabaseEmployeeDebtAdjustmentListQuery.php
│       │   ├── DatabaseEmployeeDebtAdjustmentWriterAdapter.php
│       │   ├── DatabaseEmployeeDebtDetailPageQuery.php
│       │   ├── DatabaseEmployeeDebtListPageQuery.php
│       │   ├── DatabaseEmployeeDebtPaymentListByEmployeeQuery.php
│       │   ├── DatabaseEmployeeDebtPaymentReversalListQuery.php
│       │   ├── DatabaseEmployeeDebtPaymentReversalWriterAdapter.php
│       │   ├── DatabaseEmployeeDebtReaderAdapter.php
│       │   ├── DatabaseEmployeeDebtRecordListByEmployeeQuery.php
│       │   ├── DatabaseEmployeeDebtSummaryByEmployeeQuery.php
│       │   ├── DatabaseEmployeeDebtWriterAdapter.php
│       │   ├── DatabaseEmployeeDetailPageQuery.php
│       │   ├── DatabaseEmployeeListPageQuery.php
│       │   ├── DatabaseEmployeePayrollHistoryByEmployeeQuery.php
│       │   ├── DatabaseEmployeePayrollSummaryByEmployeeQuery.php
│       │   ├── DatabaseEmployeePayrollTableReaderAdapter.php
│       │   ├── DatabaseEmployeeReaderAdapter.php
│       │   ├── DatabaseEmployeeTableReaderAdapter.php
│       │   ├── DatabasePayrollDisbursementReversalWriterAdapter.php
│       │   ├── DatabasePayrollDisbursementWriterAdapter.php
│       │   ├── DatabasePayrollListPageQuery.php
│       │   ├── DatabasePayrollTableReaderAdapter.php
│       │   ├── DatabaseVersionedEmployeeWriterAdapter.php
│       │   ├── EmployeeDetailCurrentIdentityMapper.php
│       │   ├── EmployeeDetailInitialIdentityMetaFactory.php
│       │   ├── EmployeeDetailLabelFormatter.php
│       │   ├── EmployeeDetailSnapshotReader.php
│       │   ├── EmployeeDetailTimelineEntryMapper.php
│       │   ├── EmployeeDetailVersionIdentityMapper.php
│       │   └── EmployeeDetailVersionLookup.php
│       ├── Expense
│       │   ├── Concerns
│       │   │   ├── ExpenseCategoryTableBaseQuery.php
│       │   │   ├── ExpenseCategoryTableFilters.php
│       │   │   ├── ExpenseCategoryTableOrdering.php
│       │   │   ├── ExpenseCategoryTablePayload.php
│       │   │   ├── OperationalExpenseTableBaseQuery.php
│       │   │   ├── OperationalExpenseTableFilters.php
│       │   │   ├── OperationalExpenseTableOrdering.php
│       │   │   └── OperationalExpenseTablePayload.php
│       │   ├── DatabaseExpenseCategoryListPageQuery.php
│       │   ├── DatabaseExpenseCategoryReaderAdapter.php
│       │   ├── DatabaseExpenseCategoryTableReaderAdapter.php
│       │   ├── DatabaseExpenseCategoryWriterAdapter.php
│       │   ├── DatabaseOperationalExpenseListPageQuery.php
│       │   ├── DatabaseOperationalExpenseTableReaderAdapter.php
│       │   └── DatabaseOperationalExpenseWriterAdapter.php
│       ├── IdentityAccess
│       │   ├── CachedActorAccessReaderAdapter.php
│       │   ├── CachedAdminCashierAreaAccessStateAdapter.php
│       │   ├── CachedAdminTransactionCapabilityStateAdapter.php
│       │   ├── DatabaseActorAccessReaderAdapter.php
│       │   ├── DatabaseAdminCashierAreaAccessStateAdapter.php
│       │   ├── DatabaseAdminTransactionCapabilityStateAdapter.php
│       │   ├── NullActorAccessReaderAdapter.php
│       │   └── NullAdminTransactionCapabilityStateAdapter.php
│       ├── Inventory
│       │   ├── DatabaseInventoryMovementReaderAdapter.php
│       │   ├── DatabaseInventoryMovementWriterAdapter.php
│       │   ├── DatabaseProductInventoryCostingProjectionWriterAdapter.php
│       │   ├── DatabaseProductInventoryCostingReaderAdapter.php
│       │   ├── DatabaseProductInventoryCostingWriterAdapter.php
│       │   ├── DatabaseProductInventoryProjectionWriterAdapter.php
│       │   ├── DatabaseProductInventoryReaderAdapter.php
│       │   └── DatabaseProductInventoryWriterAdapter.php
│       ├── Note
│       │   ├── Concerns
│       │   │   ├── QueriesNoteRevisionRecords.php
│       │   │   └── WritesNoteRevisionRecords.php
│       │   ├── DatabaseDueNoteReminderReaderAdapter.php
│       │   ├── DatabaseNoteActiveWorkItemFilter.php
│       │   ├── DatabaseNoteCorrectionHistoryReaderAdapter.php
│       │   ├── DatabaseNoteHistoryProjectionSourceReaderAdapter.php
│       │   ├── DatabaseNoteHistoryProjectionWriterAdapter.php
│       │   ├── DatabaseNoteMutationEventWriterAdapter.php
│       │   ├── DatabaseNoteMutationSnapshotWriterAdapter.php
│       │   ├── DatabaseNoteReaderAdapter.php
│       │   ├── DatabaseNoteWorkItemDetailLoader.php
│       │   ├── DatabaseNoteWriterAdapter.php
│       │   ├── DatabaseTransactionWorkspaceDraftDeleterAdapter.php
│       │   ├── DatabaseTransactionWorkspaceDraftReaderAdapter.php
│       │   ├── DatabaseTransactionWorkspaceDraftWriterAdapter.php
│       │   ├── DatabaseWorkItemStoreStockLineReaderAdapter.php
│       │   ├── DatabaseWorkItemWriterAdapter.php
│       │   ├── DbNoteRevisionLineRowMapper.php
│       │   ├── DbNoteRevisionPayloadCodec.php
│       │   ├── DbNoteRevisionRepository.php
│       │   ├── DbNoteRevisionRowMapper.php
│       │   ├── Mappers
│       │   │   ├── NoteMapper.php
│       │   │   └── WorkItemMapper.php
│       │   ├── Queries
│       │   │   ├── AdminNoteHistoryBaseQuery.php
│       │   │   ├── AdminNoteHistoryCriteria.php
│       │   │   ├── AdminNoteHistoryEditabilityResolver.php
│       │   │   ├── AdminNoteHistoryProjectionFilters.php
│       │   │   ├── AdminNoteHistoryProjectionItemMapper.php
│       │   │   ├── AdminNoteHistoryRowMapper.php
│       │   │   ├── AdminNoteHistoryTableQuery.php
│       │   │   ├── AdminNoteHistoryWorkSummaryFilter.php
│       │   │   ├── CashierNoteHistoryBaseQuery.php
│       │   │   ├── CashierNoteHistoryCriteria.php
│       │   │   ├── CashierNoteHistoryRowMapper.php
│       │   │   ├── CashierNoteHistoryTableQuery.php
│       │   │   ├── CashierNoteHistoryValueFormatter.php
│       │   │   ├── NoteHistoryAggregationSubqueries.php
│       │   │   ├── NoteHistoryComponentLineSummarySubquery.php
│       │   │   ├── NoteHistoryLegacyLineSummarySubquery.php
│       │   │   ├── NoteHistoryRowsQuery.php
│       │   │   ├── NoteHistorySearchScope.php
│       │   │   └── NoteHistorySelectColumns.php
│       │   ├── WorkItemDeletesTrait.php
│       │   ├── WorkItemLineInsertsTrait.php
│       │   └── WorkItemServiceUpdateGuardsTrait.php
│       ├── Payment
│       │   ├── DatabaseCustomerPaymentReaderAdapter.php
│       │   ├── DatabaseCustomerPaymentWriterAdapter.php
│       │   ├── DatabaseCustomerRefundReaderAdapter.php
│       │   ├── DatabaseCustomerRefundWriterAdapter.php
│       │   ├── DatabasePaymentAllocationReaderAdapter.php
│       │   ├── DatabasePaymentAllocationWriterAdapter.php
│       │   ├── DatabasePaymentComponentAllocationReaderAdapter.php
│       │   ├── DatabasePaymentComponentAllocationWriterAdapter.php
│       │   ├── DatabaseRefundComponentAllocationReaderAdapter.php
│       │   └── DatabaseRefundComponentAllocationWriterAdapter.php
│       ├── Persistence
│       │   └── DatabaseTransactionManagerAdapter.php
│       ├── Policy
│       ├── Procurement
│       │   ├── Concerns
│       │   │   ├── BuildsProcurementInvoiceTableRowPayload.php
│       │   │   ├── LoadsCurrentSupplierInvoiceWriteSnapshot.php
│       │   │   ├── PersistsVersionedSupplierInvoiceWrites.php
│       │   │   ├── ProcurementInvoiceDetailLinesQuery.php
│       │   │   ├── ProcurementInvoiceDetailPayload.php
│       │   │   ├── ProcurementInvoiceDetailSummaryQuery.php
│       │   │   ├── ProcurementInvoicePolicySqlFragments.php
│       │   │   ├── ProcurementInvoiceTablePayload.php
│       │   │   ├── RecordsSupplierInvoiceHistory.php
│       │   │   ├── SupplierInvoiceWritePayloads.php
│       │   │   ├── SupplierTableBaseQuery.php
│       │   │   ├── SupplierTableFilters.php
│       │   │   ├── SupplierTableOrdering.php
│       │   │   └── SupplierTablePayload.php
│       │   ├── DatabaseProcurementInvoiceDetailReaderAdapter.php
│       │   ├── DatabaseProcurementInvoiceTableReaderAdapter.php
│       │   ├── DatabaseSupplierInvoiceDuplicateNumberCheckerAdapter.php
│       │   ├── DatabaseSupplierInvoiceLineReaderAdapter.php
│       │   ├── DatabaseSupplierInvoiceListProjectionSourceReaderAdapter.php
│       │   ├── DatabaseSupplierInvoiceListProjectionWriterAdapter.php
│       │   ├── DatabaseSupplierInvoiceReaderAdapter.php
│       │   ├── DatabaseSupplierInvoiceVoidStatusReaderAdapter.php
│       │   ├── DatabaseSupplierInvoiceVoidWriterAdapter.php
│       │   ├── DatabaseSupplierInvoiceWriterAdapter.php
│       │   ├── DatabaseSupplierListProjectionSourceReaderAdapter.php
│       │   ├── DatabaseSupplierListProjectionWriterAdapter.php
│       │   ├── DatabaseSupplierPayableReminderReaderAdapter.php
│       │   ├── DatabaseSupplierPaymentProofAttachmentReaderAdapter.php
│       │   ├── DatabaseSupplierPaymentProofAttachmentWriterAdapter.php
│       │   ├── DatabaseSupplierPaymentReaderAdapter.php
│       │   ├── DatabaseSupplierPaymentReversalWriterAdapter.php
│       │   ├── DatabaseSupplierPaymentWriterAdapter.php
│       │   ├── DatabaseSupplierReaderAdapter.php
│       │   ├── DatabaseSupplierReceiptLineReaderAdapter.php
│       │   ├── DatabaseSupplierReceiptReversalWriterAdapter.php
│       │   ├── DatabaseSupplierReceiptWriterAdapter.php
│       │   ├── DatabaseSupplierTableReaderAdapter.php
│       │   ├── DatabaseSupplierWriterAdapter.php
│       │   ├── DatabaseVersionedSupplierInvoiceWriterAdapter.php
│       │   ├── LaravelSupplierPaymentProofFileStorageAdapter.php
│       │   ├── ProcurementInvoiceProjectionTableFilters.php
│       │   ├── ProcurementInvoiceProjectionTableSorting.php
│       │   ├── SupplierInvoiceListProjectionActivePaymentSubqueries.php
│       │   └── SupplierInvoiceListProjectionReceiptSubqueries.php
│       ├── ProductCatalog
│       │   ├── Concerns
│       │   │   ├── PersistsVersionedProductWrites.php
│       │   │   ├── ProductDetailSnapshotMapper.php
│       │   │   ├── ProductDetailVersionQueries.php
│       │   │   ├── ProductDuplicateLookupQuery.php
│       │   │   ├── ProductLifecycleSnapshots.php
│       │   │   ├── ProductListQuery.php
│       │   │   ├── ProductRowHydration.php
│       │   │   ├── ProductTableBaseQuery.php
│       │   │   ├── ProductTableFilters.php
│       │   │   ├── ProductTableOrdering.php
│       │   │   ├── ProductTablePayload.php
│       │   │   ├── ProductVersionRevisionLookup.php
│       │   │   ├── ProductWritePayloads.php
│       │   │   ├── RecordsProductHistory.php
│       │   │   ├── RestoresProducts.php
│       │   │   ├── SoftDeletesProducts.php
│       │   │   └── TranslatesProductWriteConflicts.php
│       │   ├── DatabaseProductDetailReaderAdapter.php
│       │   ├── DatabaseProductDuplicateCheckerAdapter.php
│       │   ├── DatabaseProductReaderAdapter.php
│       │   ├── DatabaseProductTableReaderAdapter.php
│       │   ├── DatabaseProductWriterAdapter.php
│       │   └── DatabaseVersionedProductWriterAdapter.php
│       ├── PushNotification
│       │   ├── DatabasePushSubscriptionReaderAdapter.php
│       │   ├── DatabasePushSubscriptionWriterAdapter.php
│       │   └── WebPushNotificationSenderAdapter.php
│       ├── Reporting
│       │   ├── DatabaseDashboardInventoryOverviewReaderAdapter.php
│       │   ├── DatabaseDashboardOperationalPerformanceReaderAdapter.php
│       │   ├── DatabaseDashboardTopSellingProductReaderAdapter.php
│       │   ├── DatabaseEmployeeDebtReportingSourceReaderAdapter.php
│       │   ├── DatabaseInventoryMovementReportingSourceReaderAdapter.php
│       │   ├── DatabaseOperationalExpenseReportingSourceReaderAdapter.php
│       │   ├── DatabaseOperationalProfitReportingSourceReaderAdapter.php
│       │   ├── DatabasePayrollReportingSourceReaderAdapter.php
│       │   ├── DatabaseSupplierPayableReportingSourceReaderAdapter.php
│       │   ├── DatabaseTransactionReportingSourceReaderAdapter.php
│       │   ├── InventoryCurrentSnapshotDatabaseQuery.php
│       │   ├── InventoryCurrentSnapshotRowMapper.php
│       │   ├── InventoryMovementReconciliationDatabaseQuery.php
│       │   ├── InventoryMovementSummaryDatabaseQuery.php
│       │   ├── InventoryMovementSummaryRowMapper.php
│       │   ├── LaravelDashboardReportCacheAdapter.php
│       │   ├── Queries
│       │   │   ├── DashboardInventory
│       │   │   │   ├── DashboardInventoryMovementSummaryQuery.php
│       │   │   │   ├── DashboardInventorySnapshotSummaryQuery.php
│       │   │   │   ├── DashboardInventorySummaryQuery.php
│       │   │   │   └── DashboardRestockPriorityQuery.php
│       │   │   ├── DashboardOperationalPerformance
│       │   │   │   ├── CashInPerDayQuery.php
│       │   │   │   ├── DashboardOperationalPerformancePeriodAmountMerger.php
│       │   │   │   ├── DashboardOperationalPerformancePeriodProfitCalculator.php
│       │   │   │   ├── DashboardOperationalPerformancePeriodRowMapFactory.php
│       │   │   │   ├── EmployeeDebtCashOutPerDayQuery.php
│       │   │   │   ├── ExternalPurchaseCostPerDayQuery.php
│       │   │   │   ├── OperationalExpensePerDayQuery.php
│       │   │   │   ├── PayrollDisbursementPerDayQuery.php
│       │   │   │   ├── PotentialChangeAmountRowsQuery.php
│       │   │   │   ├── PotentialChangePerDayQuery.php
│       │   │   │   ├── RefundPerDayQuery.php
│       │   │   │   └── StoreStockCogsPerDayQuery.php
│       │   │   ├── DashboardOperationalPerformancePeriodQuery.php
│       │   │   ├── DashboardTopSellingProductQuery.php
│       │   │   ├── OperationalProfit
│       │   │   │   ├── CashFlowMetricQuery.php
│       │   │   │   ├── OperatingCostMetricQuery.php
│       │   │   │   └── ProductCostMetricQuery.php
│       │   │   ├── OperationalProfitMetricsQuery.php
│       │   │   ├── TransactionCashLedgerPaymentRowsQuery.php
│       │   │   ├── TransactionCashLedgerRefundRowsQuery.php
│       │   │   ├── TransactionCashLedgerReportingQuery.php
│       │   │   └── TransactionSummaryReportingQuery.php
│       │   └── SupplierPayableReportingQueryFactory.php
│       └── Routing
│           └── LaravelRouteUrlGeneratorAdapter.php
├── Application
│   ├── Audit
│   │   └── Services
│   │       └── AuditLogIndexPageData.php
│   ├── EmployeeFinance
│   │   ├── Context
│   │   │   └── EmployeeChangeContext.php
│   │   ├── DTO
│   │   │   ├── EmployeeDebtTableQuery.php
│   │   │   ├── EmployeePayrollTableQuery.php
│   │   │   ├── EmployeeTableQuery.php
│   │   │   └── PayrollTableQuery.php
│   │   ├── Services
│   │   │   ├── CreateEmployeeDebtPageDataBuilder.php
│   │   │   ├── CreatePayrollPageDataBuilder.php
│   │   │   ├── EditEmployeePageData.php
│   │   │   ├── EmployeeDebtDetailPageDataBuilder.php
│   │   │   ├── EmployeeDebtPrincipalPageDataBuilder.php
│   │   │   ├── EmployeeDetailPageDataBuilder.php
│   │   │   ├── EmployeeListPageData.php
│   │   │   └── EmployeePayrollDetailPageDataBuilder.php
│   │   ├── Support
│   │   │   ├── EmployeeProfileAuditSnapshotBuilder.php
│   │   │   └── EmployeeProfileValueCaster.php
│   │   └── UseCases
│   │       ├── AdjustEmployeeDebtPrincipalHandler.php
│   │       ├── DisbursePayrollHandler.php
│   │       ├── GetEmployeeDebtTableHandler.php
│   │       ├── GetEmployeePayrollTableHandler.php
│   │       ├── GetEmployeeTableHandler.php
│   │       ├── GetPayrollTableHandler.php
│   │       ├── PayEmployeeDebtHandler.php
│   │       ├── PayrollBatchRowProcessor.php
│   │       ├── RecordEmployeeDebtHandler.php
│   │       ├── RegisterEmployeeHandler.php
│   │       ├── ReverseEmployeeDebtPaymentHandler.php
│   │       ├── ReversePayrollDisbursementHandler.php
│   │       └── UpdateEmployeeProfileHandler.php
│   ├── Expense
│   │   ├── DTO
│   │   │   ├── ExpenseCategoryTableQuery.php
│   │   │   └── ExpenseTableQuery.php
│   │   ├── Services
│   │   │   ├── EditExpenseCategoryPageData.php
│   │   │   └── ExpenseCategoryOptionList.php
│   │   └── UseCases
│   │       ├── ActivateExpenseCategoryHandler.php
│   │       ├── CreateExpenseCategoryHandler.php
│   │       ├── DeactivateExpenseCategoryHandler.php
│   │       ├── GetExpenseCategoryTableHandler.php
│   │       ├── GetExpenseTableHandler.php
│   │       ├── RecordOperationalExpenseHandler.php
│   │       ├── SoftDeleteOperationalExpenseHandler.php
│   │       └── UpdateExpenseCategoryHandler.php
│   ├── IdentityAccess
│   │   ├── Policies
│   │   │   ├── AdminPageAccessPolicy.php
│   │   │   ├── CashierAreaAccessPolicy.php
│   │   │   └── TransactionEntryPolicy.php
│   │   ├── Request
│   │   │   ├── IdentityAccessRequestCache.php
│   │   │   └── IdentityAccessRequestStore.php
│   │   ├── Services
│   │   │   ├── AdminPageRouteAccessDecision.php
│   │   │   ├── AppShellDataResolver.php
│   │   │   ├── CashierAreaRouteAccessDecision.php
│   │   │   └── LoginActorAccessDecision.php
│   │   └── UseCases
│   │       ├── DisableAdminTransactionCapabilityHandler.php
│   │       └── EnableAdminTransactionCapabilityHandler.php
│   ├── Inventory
│   │   ├── Policies
│   │   │   └── DefaultNegativeStockPolicy.php
│   │   ├── Services
│   │   │   ├── AutoReverseRefundedStoreStockInventory.php
│   │   │   ├── InventoryCostingProjectionBuilder.php
│   │   │   ├── InventoryProjectionBuilder.php
│   │   │   ├── InventoryProjectionService.php
│   │   │   ├── IssueInventoryOperation.php
│   │   │   ├── RefundedStoreStockComponentTargets.php
│   │   │   ├── ReverseIssuedInventoryOperation.php
│   │   │   ├── ReverseNoteStoreStockInventoryOperation.php
│   │   │   └── StockAdjustmentReversalPreflight.php
│   │   └── UseCases
│   │       ├── IssueInventoryHandler.php
│   │       ├── RebuildInventoryCostingProjectionHandler.php
│   │       ├── RebuildInventoryProjectionHandler.php
│   │       ├── RecordStockAdjustmentHandler.php
│   │       └── ReverseStockAdjustmentHandler.php
│   ├── Note
│   │   ├── DTO
│   │   │   └── DueNoteReminderRow.php
│   │   ├── Policies
│   │   │   ├── CashierNoteAccessGuard.php
│   │   │   ├── NoteAddabilityPolicy.php
│   │   │   └── NotePaidStatusPolicy.php
│   │   ├── Services
│   │   │   ├── AddWorkItemErrorClassifier.php
│   │   │   ├── AdminNoteHistoryTableData.php
│   │   │   ├── ApplyNoteRevisionAsActiveReplacement.php
│   │   │   ├── AutoCloseNoteWhenFullyPaid.php
│   │   │   ├── AutoRefundNoteWhenFullyRefunded.php
│   │   │   ├── CancelSelectedRowsAndSyncActiveNoteTotal.php
│   │   │   ├── CashierNoteDetailPageAccessData.php
│   │   │   ├── CashierNoteHistoryTableData.php
│   │   │   ├── CashierNoteProductLookupData.php
│   │   │   ├── CashierNoteRouteAccessData.php
│   │   │   ├── Concerns
│   │   │   │   └── BuildsNoteRevisionLines.php
│   │   │   ├── CorrectPaidServiceOnlyWorkItemFinalizer.php
│   │   │   ├── CorrectPaidServiceOnlyWorkItemMutation.php
│   │   │   ├── CorrectPaidServiceOnlyWorkItemTransaction.php
│   │   │   ├── CreateNotePageDataBuilder.php
│   │   │   ├── CreateTransactionWorkspaceAuditPayloadBuilder.php
│   │   │   ├── CreateTransactionWorkspaceExternalPurchaseLineMapper.php
│   │   │   ├── CreateTransactionWorkspaceInlinePaymentAmountResolver.php
│   │   │   ├── CreateTransactionWorkspaceInlinePaymentContextResolver.php
│   │   │   ├── CreateTransactionWorkspaceInlinePaymentRecorder.php
│   │   │   ├── CreateTransactionWorkspaceNoteFactory.php
│   │   │   ├── CreateTransactionWorkspacePageDataBuilder.php
│   │   │   ├── CreateTransactionWorkspaceServiceWorkItemVariantResolver.php
│   │   │   ├── CreateTransactionWorkspaceStoreStockLineMapper.php
│   │   │   ├── CreateTransactionWorkspaceSuccessMessageBuilder.php
│   │   │   ├── CreateTransactionWorkspaceWorkItemPayloadMapper.php
│   │   │   ├── CreateTransactionWorkspaceWorkItemPersister.php
│   │   │   ├── CurrentRevision
│   │   │   │   ├── CurrentRevisionComponentSettlementSummaryBuilder.php
│   │   │   │   ├── CurrentRevisionDetailRowMapper.php
│   │   │   │   ├── CurrentRevisionLegacySettlementSummaryBuilder.php
│   │   │   │   ├── CurrentRevisionLinePresentationSupport.php
│   │   │   │   └── CurrentRevisionRowSettlementProjector.php
│   │   │   ├── DeleteTransactionWorkspaceDraftOperation.php
│   │   │   ├── EditableWorkspaceNoteGuard.php
│   │   │   ├── EditTransactionWorkspacePageDataBuilder.php
│   │   │   ├── EditTransactionWorkspaceRouteNames.php
│   │   │   ├── EnsureInitialNoteRevisionExists.php
│   │   │   ├── ExternalPurchaseLinesSubtotal.php
│   │   │   ├── FinalizePaidNoteCorrection.php
│   │   │   ├── FinalizeRefundedNoteFromActiveRows.php
│   │   │   ├── NoteBillingProjectionBuilder.php
│   │   │   ├── NoteBillingProjectionFromWorkspaceRowsBuilder.php
│   │   │   ├── NoteBillingProjectionRowMapper.php
│   │   │   ├── NoteBillingProjectionSupport.php
│   │   │   ├── NoteCorrectionHistoryBuilder.php
│   │   │   ├── NoteCorrectionSnapshotBuilder.php
│   │   │   ├── NoteCorrectionUiOptionsBuilder.php
│   │   │   ├── NoteCurrentRevisionResolver.php
│   │   │   ├── NoteDetailActionModalPayloadBuilder.php
│   │   │   ├── NoteDetailNotePayloadBuilder.php
│   │   │   ├── NoteDetailOperationalPayloadBuilder.php
│   │   │   ├── NoteDetailPageDataBuilder.php
│   │   │   ├── NoteDetailProductLabelResolver.php
│   │   │   ├── NoteDetailRevisionTimelineBuilder.php
│   │   │   ├── NoteDetailRevisionViewDataBuilder.php
│   │   │   ├── NoteDetailRowMapper.php
│   │   │   ├── NoteDetailRowPresentationSupport.php
│   │   │   ├── NoteDetailRowPrimaryLabelResolver.php
│   │   │   ├── NoteDetailRowSubtitleBuilder.php
│   │   │   ├── NoteHistoryProjectionService.php
│   │   │   ├── NoteLineSummaryBuilder.php
│   │   │   ├── NoteOperationalComponentAllocationTotalsGrouper.php
│   │   │   ├── NoteOperationalComponentSettlementSummaryBuilder.php
│   │   │   ├── NoteOperationalLegacySettlementSummaryBuilder.php
│   │   │   ├── NoteOperationalRowSettlementProjector.php
│   │   │   ├── NoteOperationalSettlementLabelResolver.php
│   │   │   ├── NoteOperationalStatusEvaluator.php
│   │   │   ├── NoteOperationalStatusResolver.php
│   │   │   ├── NoteOutstandingPaymentAmountResolver.php
│   │   │   ├── NotePaymentStatusResolver.php
│   │   │   ├── NoteProductOptionsBuilder.php
│   │   │   ├── NoteProductSaleOnlyLineTotalResolver.php
│   │   │   ├── NoteRefundPaymentOptionsBuilder.php
│   │   │   ├── NoteReplacementPaymentAllocationReconciler.php
│   │   │   ├── NoteRevisionBootstrapFactory.php
│   │   │   ├── NoteRevisionLineChangeFormatter.php
│   │   │   ├── NoteRevisionLinePayloadMapper.php
│   │   │   ├── NoteRevisionLineSnapshotLabelResolver.php
│   │   │   ├── NoteRevisionLineSnapshotViewMapper.php
│   │   │   ├── NoteRevisionTimelineParentResolver.php
│   │   │   ├── NoteRevisionTimelineSummaryBuilder.php
│   │   │   ├── NoteRevisionWorkspaceExistingItemMapper.php
│   │   │   ├── NoteRowSettlementSummaryBuilder.php
│   │   │   ├── NoteRowsStartingLineNoResolver.php
│   │   │   ├── NoteWorkspacePanelDataBuilder.php
│   │   │   ├── NoteWorkspacePanelPayloadFactory.php
│   │   │   ├── PersistNoteMutationTimeline.php
│   │   │   ├── RefundImpactPayloadBuilder.php
│   │   │   ├── RefundImpactProductLabelResolver.php
│   │   │   ├── RefundImpactReturnsMapper.php
│   │   │   ├── RefundImpactSourceValueReader.php
│   │   │   ├── ReopenClosedNoteTransaction.php
│   │   │   ├── ReverseIssuedInventoryByNoteService.php
│   │   │   ├── RevisionWorkspace
│   │   │   │   ├── RevisionWorkspaceProductLineMapper.php
│   │   │   │   ├── RevisionWorkspaceProductOnlyMapper.php
│   │   │   │   ├── RevisionWorkspaceServiceExternalMapper.php
│   │   │   │   ├── RevisionWorkspaceServiceOnlyMapper.php
│   │   │   │   └── RevisionWorkspaceServiceStoreStockMapper.php
│   │   │   ├── SaveTransactionWorkspaceDraftOperation.php
│   │   │   ├── SelectedActiveWorkItemsResolver.php
│   │   │   ├── SelectedNoteRowsPaymentAmountResolver.php
│   │   │   ├── SelectedNoteRowsPaymentSelectionExpander.php
│   │   │   ├── SelectedNoteRowsRefundAmountResolver.php
│   │   │   ├── SelectedNoteRowsRefundPlanResolver.php
│   │   │   ├── SelectedRowsRefundBucketsBuilder.php
│   │   │   ├── StoreStockLinesSubtotal.php
│   │   │   ├── TransactionWorkspaceDraftData.php
│   │   │   ├── TransactionWorkspaceExistingItemMapper.php
│   │   │   ├── TransactionWorkspaceExistingProductMetaBuilder.php
│   │   │   ├── TransactionWorkspaceExistingProductOnlyMapper.php
│   │   │   ├── TransactionWorkspaceExistingServiceExternalMapper.php
│   │   │   ├── TransactionWorkspaceExistingServiceOnlyMapper.php
│   │   │   ├── TransactionWorkspaceExistingServiceStoreStockMapper.php
│   │   │   ├── UpdateTransactionWorkspaceResultBuilder.php
│   │   │   ├── UpdateTransactionWorkspaceWorkItemPersister.php
│   │   │   ├── WorkItemFactory.php
│   │   │   ├── WorkItemOperationalStatusResolver.php
│   │   │   └── WorkItemStatusTransitionService.php
│   │   └── UseCases
│   │       ├── AddWorkItemHandler.php
│   │       ├── CorrectPaidServiceOnlySupportTrait.php
│   │       ├── CorrectPaidServiceOnlyWorkItemHandler.php
│   │       ├── CorrectPaidServiceWithExternalPurchaseServiceFeeOnlyHandler.php
│   │       ├── CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler.php
│   │       ├── CorrectPaidWorkItemStatusHandler.php
│   │       ├── CreateNoteHandler.php
│   │       ├── CreateNoteRevisionAuditPayloadBuilder.php
│   │       ├── CreateNoteRevisionCommitter.php
│   │       ├── CreateNoteRevisionHandler.php
│   │       ├── CreateNoteRevisionItemNormalizer.php
│   │       ├── CreateNoteRevisionPayloadNoteBuilder.php
│   │       ├── CreateNoteRevisionProductItemBuilder.php
│   │       ├── CreateNoteRevisionResult.php
│   │       ├── CreateNoteRevisionServiceItemBuilder.php
│   │       ├── CreateTransactionWorkspaceHandler.php
│   │       ├── GetDueNoteRemindersHandler.php
│   │       ├── ReopenClosedNoteHandler.php
│   │       ├── UpdateTransactionWorkspaceHandler.php
│   │       └── UpdateWorkItemStatusHandler.php
│   ├── Payment
│   │   ├── DTO
│   │   │   ├── PayableNoteComponent.php
│   │   │   ├── RecordedCustomerRefund.php
│   │   │   ├── RecordedNotePayment.php
│   │   │   ├── RecordedSelectedRowsRefundPlanResult.php
│   │   │   ├── SelectedRowsRefundPaymentBucket.php
│   │   │   ├── SelectedRowsRefundPlanArraySerializer.php
│   │   │   └── SelectedRowsRefundPlan.php
│   │   ├── Services
│   │   │   ├── AllocatePaymentAcrossComponents.php
│   │   │   ├── AllocatePaymentErrorClassifier.php
│   │   │   ├── AllocateRefundAcrossComponents.php
│   │   │   ├── BuildCustomerPaymentCashDetail.php
│   │   │   ├── ExistingPaymentComponentTotals.php
│   │   │   ├── PayableComponentsFromWorkItem.php
│   │   │   ├── PaymentComponentTypePriority.php
│   │   │   ├── PaymentDateParser.php
│   │   │   ├── RecordAndAllocateNotePaymentOperation.php
│   │   │   ├── RecordCustomerRefundOperation.php
│   │   │   ├── RecordCustomerRefundTransaction.php
│   │   │   ├── RecordSelectedRowsRefundPlanAuditRecorder.php
│   │   │   ├── RecordSelectedRowsRefundPlanBucketProcessor.php
│   │   │   ├── RecordSelectedRowsRefundPlanTransaction.php
│   │   │   ├── RefundablePaymentAllocations.php
│   │   │   ├── RefundedComponentTotals.php
│   │   │   ├── RefundPairLimitGuard.php
│   │   │   ├── ResolveNotePayableComponents.php
│   │   │   ├── ResolveNotePayableComponentsSelectedRows.php
│   │   │   ├── ResolveNotePayableComponentsSelectionId.php
│   │   │   └── SortPayableNoteComponents.php
│   │   └── UseCases
│   │       ├── AllocateCustomerPaymentHandler.php
│   │       ├── RecordAndAllocateNotePaymentHandler.php
│   │       ├── RecordCustomerPaymentHandler.php
│   │       ├── RecordCustomerRefundHandler.php
│   │       └── RecordCustomerRefundSupportTrait.php
│   ├── Procurement
│   │   ├── Context
│   │   │   └── SupplierInvoiceChangeContext.php
│   │   ├── DTO
│   │   │   ├── ProcurementInvoiceTableQuery.php
│   │   │   ├── SupplierInvoiceRevisionContextSnapshot.php
│   │   │   ├── SupplierPayableReminderRow.php
│   │   │   ├── SupplierPaymentProofAttachmentFile.php
│   │   │   └── SupplierTableQuery.php
│   │   ├── Services
│   │   │   ├── AttachSupplierPaymentProofTransaction.php
│   │   │   ├── EditSupplierPageData.php
│   │   │   ├── ProcurementInvoicePaymentProofPageData.php
│   │   │   ├── ProcurementProductLookupData.php
│   │   │   ├── RecordSupplierPaymentAuditLog.php
│   │   │   ├── RecordSupplierPaymentUnderLock.php
│   │   │   ├── ServeSupplierPaymentProofAttachmentData.php
│   │   │   ├── SupplierInvoiceAutoReceiveProcessor.php
│   │   │   ├── SupplierInvoiceDuplicateNumberChecker.php
│   │   │   ├── SupplierInvoiceEditabilityGuard.php
│   │   │   ├── SupplierInvoiceFactory.php
│   │   │   ├── SupplierInvoiceFlowDateResolver.php
│   │   │   ├── SupplierInvoiceListProjectionService.php
│   │   │   ├── SupplierInvoiceProductOptionsData.php
│   │   │   ├── SupplierInvoiceQueryExceptionClassifier.php
│   │   │   ├── SupplierInvoiceRevisionContextResolver.php
│   │   │   ├── SupplierInvoiceRevisionDeltaMovementsBuilder.php
│   │   │   ├── SupplierInvoiceRevisionDeltaStockGuard.php
│   │   │   ├── SupplierInvoiceRevisionInventoryEffectsApplier.php
│   │   │   ├── SupplierInvoiceRevisionLineMapFactory.php
│   │   │   ├── SupplierInvoiceRevisionMovementFactory.php
│   │   │   ├── SupplierInvoiceRevisionPairedLineDeltaResolver.php
│   │   │   ├── SupplierInvoiceVoidStatus.php
│   │   │   ├── SupplierListProjectionService.php
│   │   │   ├── SupplierLookupData.php
│   │   │   ├── SupplierPaymentPreflight.php
│   │   │   ├── SupplierPaymentProofAttachmentFactory.php
│   │   │   ├── SupplierPaymentReversalPreflight.php
│   │   │   ├── SupplierPaymentReversalRecorder.php
│   │   │   ├── SupplierReceiptFactory.php
│   │   │   ├── SupplierReceiptReversalDeltaMovementsBuilder.php
│   │   │   ├── SupplierReceiptReversalPreflight.php
│   │   │   ├── SupplierReceiptReversalRecorder.php
│   │   │   ├── SupplierService.php
│   │   │   ├── UpdatedSupplierInvoiceBuilder.php
│   │   │   ├── UpdateSupplierInvoiceOperation.php
│   │   │   ├── UpdateSupplierInvoiceTransactionalRunner.php
│   │   │   └── VoidedSupplierInvoiceGuard.php
│   │   └── UseCases
│   │       ├── AttachSupplierPaymentProofHandler.php
│   │       ├── AttachSupplierPaymentProofResultBuilder.php
│   │       ├── CreateSupplierInvoiceFlowHandler.php
│   │       ├── CreateSupplierInvoiceFlowOperation.php
│   │       ├── CreateSupplierInvoiceFlowQueryExceptionClassifier.php
│   │       ├── CreateSupplierInvoiceHandler.php
│   │       ├── GetProcurementInvoiceDetailHandler.php
│   │       ├── GetProcurementInvoiceTableHandler.php
│   │       ├── GetSupplierPayableRemindersHandler.php
│   │       ├── GetSupplierPaymentProofAttachmentFileHandler.php
│   │       ├── GetSupplierTableHandler.php
│   │       ├── ReceiveSupplierInvoiceHandler.php
│   │       ├── RecordSupplierPaymentHandler.php
│   │       ├── ReverseSupplierPaymentHandler.php
│   │       ├── ReverseSupplierReceiptHandler.php
│   │       ├── UpdateSupplierHandler.php
│   │       ├── UpdateSupplierInvoiceHandler.php
│   │       └── VoidSupplierInvoiceHandler.php
│   ├── ProductCatalog
│   │   ├── Context
│   │   │   └── ProductChangeContext.php
│   │   ├── DTO
│   │   │   └── ProductTableQuery.php
│   │   ├── Services
│   │   │   └── EditProductPageData.php
│   │   └── UseCases
│   │       ├── Concerns
│   │       │   ├── HandlesProductWriteExceptions.php
│   │       │   └── NormalizesProductMasterInput.php
│   │       ├── CreateProductHandler.php
│   │       ├── GetProductDetailHandler.php
│   │       ├── GetProductTableHandler.php
│   │       ├── RestoreProductHandler.php
│   │       ├── SoftDeleteProductHandler.php
│   │       ├── UpdateProductDuplicateGuard.php
│   │       ├── UpdateProductHandler.php
│   │       └── UpdateProductSuccessPayloadBuilder.php
│   ├── PushNotification
│   │   ├── DTO
│   │   │   ├── DueNoteReminderPushSendSummary.php
│   │   │   ├── PushNotificationPayload.php
│   │   │   ├── PushNotificationSendResult.php
│   │   │   ├── PushSubscriptionData.php
│   │   │   ├── StoredPushSubscription.php
│   │   │   └── SupplierPayableReminderPushSendSummary.php
│   │   ├── Services
│   │   │   ├── DueNoteReminderPushPayloadFactory.php
│   │   │   └── SupplierPayableReminderPushPayloadFactory.php
│   │   └── UseCases
│   │       ├── DeletePushSubscriptionHandler.php
│   │       ├── SendDueNoteReminderPushHandler.php
│   │       ├── SendSupplierPayableReminderPushHandler.php
│   │       └── StorePushSubscriptionHandler.php
│   ├── Reporting
│   │   ├── DTO
│   │   │   ├── Concerns
│   │   │   │   ├── InventoryMovementSummaryRowAccessors.php
│   │   │   │   └── OperationalExpenseSummaryRowAccessors.php
│   │   │   ├── EmployeeDebtReportPageQuery.php
│   │   │   ├── EmployeeDebtSummaryRow.php
│   │   │   ├── InventoryMovementSummaryRow.php
│   │   │   ├── InventoryStockValueReportPageQuery.php
│   │   │   ├── OperationalExpenseReportPageQuery.php
│   │   │   ├── OperationalExpenseSummaryRow.php
│   │   │   ├── OperationalProfitReportPageQuery.php
│   │   │   ├── OperationalProfitSummaryRow.php
│   │   │   ├── PayrollReportPageQuery.php
│   │   │   ├── PayrollReportRow.php
│   │   │   ├── SupplierPayableReportPageQuery.php
│   │   │   ├── SupplierPayableSummaryRow.php
│   │   │   ├── TransactionCashLedgerPageQuery.php
│   │   │   ├── TransactionCashLedgerPerNoteRow.php
│   │   │   ├── TransactionReportPageQuery.php
│   │   │   └── TransactionSummaryPerNoteRow.php
│   │   ├── Exports
│   │   │   ├── Concerns
│   │   │   │   └── FormatsPdfReportValues.php
│   │   │   ├── EmployeeDebtReportExcelDetailSheetWriter.php
│   │   │   ├── EmployeeDebtReportExcelPeriodSheetWriter.php
│   │   │   ├── EmployeeDebtReportExcelStatusSheetWriter.php
│   │   │   ├── EmployeeDebtReportExcelSummarySheetWriter.php
│   │   │   ├── EmployeeDebtReportExcelWorkbookBuilder.php
│   │   │   ├── EmployeeDebtReportPdfViewDataBuilder.php
│   │   │   ├── InventoryStockValueReportExcelMovementSheetWriter.php
│   │   │   ├── InventoryStockValueReportExcelSnapshotSheetWriter.php
│   │   │   ├── InventoryStockValueReportExcelSummarySheetWriter.php
│   │   │   ├── InventoryStockValueReportExcelWorkbookBuilder.php
│   │   │   ├── InventoryStockValueReportPdfViewDataBuilder.php
│   │   │   ├── OperationalExpenseReportExcelCategorySheetWriter.php
│   │   │   ├── OperationalExpenseReportExcelDetailSheetWriter.php
│   │   │   ├── OperationalExpenseReportExcelPeriodSheetWriter.php
│   │   │   ├── OperationalExpenseReportExcelSummarySheetWriter.php
│   │   │   ├── OperationalExpenseReportExcelWorkbookBuilder.php
│   │   │   ├── OperationalExpenseReportPdfViewDataBuilder.php
│   │   │   ├── OperationalProfitReportExcelSummarySheetWriter.php
│   │   │   ├── OperationalProfitReportExcelWorkbookBuilder.php
│   │   │   ├── OperationalProfitReportPdfViewDataBuilder.php
│   │   │   ├── PayrollReportExcelDetailSheetWriter.php
│   │   │   ├── PayrollReportExcelModeSheetWriter.php
│   │   │   ├── PayrollReportExcelPeriodSheetWriter.php
│   │   │   ├── PayrollReportExcelSummarySheetWriter.php
│   │   │   ├── PayrollReportExcelWorkbookBuilder.php
│   │   │   ├── PayrollReportPdfViewDataBuilder.php
│   │   │   ├── SupplierPayableReportExcelDetailSheetWriter.php
│   │   │   ├── SupplierPayableReportExcelPeriodSheetWriter.php
│   │   │   ├── SupplierPayableReportExcelSummarySheetWriter.php
│   │   │   ├── SupplierPayableReportExcelSupplierSheetWriter.php
│   │   │   ├── SupplierPayableReportExcelWorkbookBuilder.php
│   │   │   ├── SupplierPayableReportPdfViewDataBuilder.php
│   │   │   ├── TransactionCashLedgerExcelDetailSheetWriter.php
│   │   │   ├── TransactionCashLedgerExcelPeriodSheetWriter.php
│   │   │   ├── TransactionCashLedgerExcelSummarySheetWriter.php
│   │   │   ├── TransactionCashLedgerExcelWorkbookBuilder.php
│   │   │   ├── TransactionCashLedgerPdfViewDataBuilder.php
│   │   │   ├── TransactionReportExcelCustomerSheetWriter.php
│   │   │   ├── TransactionReportExcelDetailSheetWriter.php
│   │   │   ├── TransactionReportExcelPeriodSheetWriter.php
│   │   │   ├── TransactionReportExcelSummarySheetWriter.php
│   │   │   ├── TransactionReportExcelTableWriter.php
│   │   │   ├── TransactionReportExcelWorkbookBuilder.php
│   │   │   └── TransactionReportPdfViewDataBuilder.php
│   │   ├── Services
│   │   │   ├── CashChangeDenominationCalculator.php
│   │   │   ├── EmployeeDebtPeriodBreakdownBuilder.php
│   │   │   ├── EmployeeDebtReportingReconciliationService.php
│   │   │   ├── EmployeeDebtReportSummaryBuilder.php
│   │   │   ├── EmployeeDebtStatusBreakdownBuilder.php
│   │   │   ├── EmployeeDebtSummaryBuilder.php
│   │   │   ├── InventoryMovementReportingReconciliationService.php
│   │   │   ├── InventoryMovementSummaryBuilder.php
│   │   │   ├── InventoryStockValueReportSummaryBuilder.php
│   │   │   ├── OperationalExpenseCategoryBreakdownBuilder.php
│   │   │   ├── OperationalExpensePeriodBreakdownBuilder.php
│   │   │   ├── OperationalExpenseReportingReconciliationService.php
│   │   │   ├── OperationalExpenseReportSummaryBuilder.php
│   │   │   ├── OperationalExpenseSummaryBuilder.php
│   │   │   ├── OperationalProfitReportingReconciliationService.php
│   │   │   ├── OperationalProfitSummaryBuilder.php
│   │   │   ├── PayrollReportingReconciliationService.php
│   │   │   ├── PayrollReportModeBreakdownBuilder.php
│   │   │   ├── PayrollReportPeriodBreakdownBuilder.php
│   │   │   ├── PayrollReportRowBuilder.php
│   │   │   ├── PayrollReportSummaryBuilder.php
│   │   │   ├── SupplierPayableDueStatusResolver.php
│   │   │   ├── SupplierPayablePeriodBreakdownBuilder.php
│   │   │   ├── SupplierPayableReportingReconciliationService.php
│   │   │   ├── SupplierPayableReportSummaryBuilder.php
│   │   │   ├── SupplierPayableSummaryBuilder.php
│   │   │   ├── SupplierPayableSupplierBreakdownBuilder.php
│   │   │   ├── TransactionCashLedgerPeriodTableBuilder.php
│   │   │   ├── TransactionCashLedgerPerNoteBuilder.php
│   │   │   ├── TransactionCashLedgerSummaryBuilder.php
│   │   │   ├── TransactionCustomerBreakdownBuilder.php
│   │   │   ├── TransactionPaymentStatusLabelResolver.php
│   │   │   ├── TransactionPeriodBreakdownBuilder.php
│   │   │   ├── TransactionReportingReconciliationService.php
│   │   │   ├── TransactionReportSummaryBuilder.php
│   │   │   └── TransactionSummaryPerNoteBuilder.php
│   │   └── UseCases
│   │       ├── AdminDashboardAnalyticsChartsPayloadBuilder.php
│   │       ├── AdminDashboardAnalyticsPayloadBuilder.php
│   │       ├── AdminDashboardAnalyticsPeriod.php
│   │       ├── AdminDashboardOverviewPayloadBuilder.php
│   │       ├── AdminDashboardOverviewPayload.php
│   │       ├── AdminDashboardOverviewPeriod.php
│   │       ├── AdminDashboardPeriod.php
│   │       ├── AdminDashboardSharedReportFragments.php
│   │       ├── Charts
│   │       │   ├── BuildCashflowLineChart.php
│   │       │   ├── BuildOperationalPerformanceBarChart.php
│   │       │   ├── BuildStockStatusDonutChart.php
│   │       │   ├── BuildTopSellingBarChart.php
│   │       │   ├── CashflowLineChartDatasetBuilder.php
│   │       │   └── CashflowLineChartPeriodsFactory.php
│   │       ├── Concerns
│   │       │   ├── BuildsAdminDashboardOverviewContext.php
│   │       │   └── FormatsAdminDashboardTopSellingRows.php
│   │       ├── DashboardCashLedgerTotals.php
│   │       ├── DashboardRestockPriorityRows.php
│   │       ├── GetAdminDashboardAnalyticsHandler.php
│   │       ├── GetAdminDashboardOverviewHandler.php
│   │       ├── GetAdminDashboardPagePayloadHandler.php
│   │       ├── GetDashboardOperationalPerformanceDatasetHandler.php
│   │       ├── GetEmployeeDebtReportDatasetHandler.php
│   │       ├── GetEmployeeDebtSummaryHandler.php
│   │       ├── GetInventoryMovementSummaryHandler.php
│   │       ├── GetInventoryStockValueReportDatasetHandler.php
│   │       ├── GetOperationalExpenseReportDatasetHandler.php
│   │       ├── GetOperationalExpenseSummaryHandler.php
│   │       ├── GetOperationalProfitSummaryHandler.php
│   │       ├── GetPayrollReportDatasetHandler.php
│   │       ├── GetSupplierPayableReportDatasetHandler.php
│   │       ├── GetSupplierPayableSummaryHandler.php
│   │       ├── GetTransactionCashLedgerPerNoteHandler.php
│   │       ├── GetTransactionReportDatasetHandler.php
│   │       ├── GetTransactionSummaryPerNoteHandler.php
│   │       └── ReportingResultDataExtractor.php
│   ├── Shared
│   │   ├── Commands
│   │   ├── DTO
│   │   │   └── Result.php
│   │   ├── Queries
│   │   ├── Services
│   │   └── UseCases
│   └── System
│       └── Health
│           └── HealthCheckHandler.php
├── Core
│   ├── EmployeeFinance
│   │   ├── Employee
│   │   │   ├── EmployeeAccessors.php
│   │   │   ├── Employee.php
│   │   │   ├── EmployeeStatus.php
│   │   │   ├── EmployeeValidation.php
│   │   │   └── PayPeriod.php
│   │   ├── EmployeeDebt
│   │   │   ├── DebtPayment.php
│   │   │   ├── DebtStatus.php
│   │   │   └── EmployeeDebt.php
│   │   └── Payroll
│   │       ├── DisbursementMode.php
│   │       └── PayrollDisbursement.php
│   ├── Expense
│   │   ├── ExpenseCategory
│   │   │   ├── ExpenseCategory.php
│   │   │   └── ExpenseCategoryValidation.php
│   │   └── OperationalExpense
│   │       ├── OperationalExpenseAccessors.php
│   │       ├── OperationalExpense.php
│   │       └── OperationalExpenseValidation.php
│   ├── IdentityAccess
│   │   ├── Actor
│   │   │   └── ActorAccess.php
│   │   ├── Capability
│   │   │   ├── AdminCashierAreaAccessState.php
│   │   │   └── AdminTransactionCapabilityState.php
│   │   ├── Role
│   │   │   └── Role.php
│   │   └── Score
│   │       └── TransactionEntryScore.php
│   ├── Inventory
│   │   ├── Costing
│   │   │   ├── ProductInventoryCosting.php
│   │   │   ├── ProductInventoryCostingState.php
│   │   │   └── ProductInventoryCostingValidation.php
│   │   ├── Movement
│   │   │   ├── InventoryMovement.php
│   │   │   ├── InventoryMovementState.php
│   │   │   └── InventoryMovementValidation.php
│   │   ├── Policies
│   │   │   └── NegativeStockPolicy.php
│   │   └── ProductInventory
│   │       └── ProductInventory.php
│   ├── Note
│   │   ├── Mutation
│   │   │   ├── NoteMutationEventGuard.php
│   │   │   ├── NoteMutationEvent.php
│   │   │   └── NoteMutationSnapshot.php
│   │   ├── Note
│   │   │   ├── NoteMutations.php
│   │   │   ├── NoteNormalization.php
│   │   │   ├── NoteOperationalStateMutations.php
│   │   │   ├── Note.php
│   │   │   ├── NoteState.php
│   │   │   └── NoteValidation.php
│   │   ├── Policies
│   │   ├── Revision
│   │   │   ├── Concerns
│   │   │   │   ├── NoteRevisionAccessors.php
│   │   │   │   ├── NoteRevisionLineSnapshotAccessors.php
│   │   │   │   ├── NoteRevisionLineSnapshotValidation.php
│   │   │   │   └── NoteRevisionValidation.php
│   │   │   ├── NoteRevisionLineSnapshot.php
│   │   │   └── NoteRevision.php
│   │   └── WorkItem
│   │       ├── ExternalPurchaseLine.php
│   │       ├── ServiceDetail.php
│   │       ├── StoreStockLine.php
│   │       ├── WorkItemGuard.php
│   │       └── WorkItem.php
│   ├── Payment
│   │   ├── CustomerPayment
│   │   │   ├── CustomerPaymentCashDetail.php
│   │   │   ├── CustomerPaymentMethod.php
│   │   │   └── CustomerPayment.php
│   │   ├── CustomerRefund
│   │   │   └── CustomerRefund.php
│   │   ├── PaymentAllocation
│   │   │   └── PaymentAllocation.php
│   │   ├── PaymentComponentAllocation
│   │   │   ├── PaymentComponentAllocationGuard.php
│   │   │   ├── PaymentComponentAllocation.php
│   │   │   └── PaymentComponentType.php
│   │   ├── Policies
│   │   │   └── PaymentAllocationPolicy.php
│   │   └── RefundComponentAllocation
│   │       ├── RefundComponentAllocationGuard.php
│   │       └── RefundComponentAllocation.php
│   ├── Procurement
│   │   ├── Supplier
│   │   │   └── Supplier.php
│   │   ├── SupplierInvoice
│   │   │   ├── SupplierInvoiceLine.php
│   │   │   ├── SupplierInvoiceLineState.php
│   │   │   ├── SupplierInvoiceLineValidation.php
│   │   │   ├── SupplierInvoice.php
│   │   │   ├── SupplierInvoiceState.php
│   │   │   └── SupplierInvoiceValidation.php
│   │   ├── SupplierPayment
│   │   │   ├── SupplierPayment.php
│   │   │   ├── SupplierPaymentState.php
│   │   │   └── SupplierPaymentValidation.php
│   │   ├── SupplierPaymentProofAttachment
│   │   │   ├── SupplierPaymentProofAttachment.php
│   │   │   ├── SupplierPaymentProofAttachmentState.php
│   │   │   └── SupplierPaymentProofAttachmentValidation.php
│   │   └── SupplierReceipt
│   │       ├── SupplierReceiptLine.php
│   │       └── SupplierReceipt.php
│   ├── ProductCatalog
│   │   ├── Policies
│   │   │   └── MinSellingPricePolicy.php
│   │   └── Product
│   │       ├── ProductFactory.php
│   │       ├── ProductMutation.php
│   │       ├── Product.php
│   │       ├── ProductState.php
│   │       └── ProductValidation.php
│   ├── Shared
│   │   ├── Contracts
│   │   │   └── ValueObject.php
│   │   ├── Exceptions
│   │   │   └── DomainException.php
│   │   ├── Support
│   │   └── ValueObjects
│   │       └── Money.php
│   └── System
│       └── Health
├── Models
│   └── User.php
├── Ports
│   ├── In
│   │   └── HealthCheckUseCase.php
│   └── Out
│       ├── AuditLogPort.php
│       ├── AuditLogReaderPort.php
│       ├── ClockPort.php
│       ├── EmployeeFinance
│       │   ├── EmployeeDebtAdjustmentListReaderPort.php
│       │   ├── EmployeeDebtAdjustmentWriterPort.php
│       │   ├── EmployeeDebtDetailPageReaderPort.php
│       │   ├── EmployeeDebtPaymentReversalListReaderPort.php
│       │   ├── EmployeeDebtPaymentReversalWriterPort.php
│       │   ├── EmployeeDebtReaderPort.php
│       │   ├── EmployeeDebtSummaryByEmployeeReaderPort.php
│       │   ├── EmployeeDebtTableReaderPort.php
│       │   ├── EmployeeDebtWriterPort.php
│       │   ├── EmployeeDetailPageReaderPort.php
│       │   ├── EmployeeListPageReaderPort.php
│       │   ├── EmployeePayrollSummaryByEmployeeReaderPort.php
│       │   ├── EmployeePayrollTableReaderPort.php
│       │   ├── EmployeeReaderPort.php
│       │   ├── EmployeeTableReaderPort.php
│       │   ├── EmployeeWriterPort.php
│       │   ├── PayrollDisbursementReversalWriterPort.php
│       │   ├── PayrollDisbursementWriterPort.php
│       │   └── PayrollTableReaderPort.php
│       ├── Expense
│       │   ├── ExpenseCategoryOptionReaderPort.php
│       │   ├── ExpenseCategoryReaderPort.php
│       │   ├── ExpenseCategoryTableReaderPort.php
│       │   ├── ExpenseCategoryWriterPort.php
│       │   ├── OperationalExpenseTableReaderPort.php
│       │   └── OperationalExpenseWriterPort.php
│       ├── IdentityAccess
│       │   ├── ActorAccessReaderPort.php
│       │   ├── AdminCashierAreaAccessStatePort.php
│       │   └── AdminTransactionCapabilityStatePort.php
│       ├── Inventory
│       │   ├── InventoryMovementReaderPort.php
│       │   ├── InventoryMovementWriterPort.php
│       │   ├── ProductInventoryCostingProjectionWriterPort.php
│       │   ├── ProductInventoryCostingReaderPort.php
│       │   ├── ProductInventoryCostingWriterPort.php
│       │   ├── ProductInventoryProjectionWriterPort.php
│       │   ├── ProductInventoryReaderPort.php
│       │   └── ProductInventoryWriterPort.php
│       ├── Note
│       │   ├── AdminNoteHistoryTableReaderPort.php
│       │   ├── CashierNoteHistoryTableReaderPort.php
│       │   ├── DueNoteReminderReaderPort.php
│       │   ├── NoteCorrectionHistoryReaderPort.php
│       │   ├── NoteHistoryProjectionSourceReaderPort.php
│       │   ├── NoteHistoryProjectionWriterPort.php
│       │   ├── NoteMutationEventWriterPort.php
│       │   ├── NoteMutationSnapshotWriterPort.php
│       │   ├── NoteReaderPort.php
│       │   ├── NoteRevisionReaderPort.php
│       │   ├── NoteRevisionWriterPort.php
│       │   ├── NoteWriterPort.php
│       │   ├── TransactionWorkspaceDraftDeleterPort.php
│       │   ├── TransactionWorkspaceDraftReaderPort.php
│       │   ├── TransactionWorkspaceDraftWriterPort.php
│       │   ├── WorkItemStoreStockLineReaderPort.php
│       │   └── WorkItemWriterPort.php
│       ├── Payment
│       │   ├── CustomerPaymentReaderPort.php
│       │   ├── CustomerPaymentWriterPort.php
│       │   ├── CustomerRefundReaderPort.php
│       │   ├── CustomerRefundWriterPort.php
│       │   ├── PaymentAllocationReaderPort.php
│       │   ├── PaymentAllocationWriterPort.php
│       │   ├── PaymentComponentAllocationReaderPort.php
│       │   ├── PaymentComponentAllocationWriterPort.php
│       │   ├── RefundComponentAllocationReaderPort.php
│       │   └── RefundComponentAllocationWriterPort.php
│       ├── Procurement
│       │   ├── ProcurementInvoiceDetailReaderPort.php
│       │   ├── ProcurementInvoiceTableReaderPort.php
│       │   ├── SupplierInvoiceDuplicateNumberCheckerPort.php
│       │   ├── SupplierInvoiceLifecyclePort.php
│       │   ├── SupplierInvoiceLineReaderPort.php
│       │   ├── SupplierInvoiceListProjectionSourceReaderPort.php
│       │   ├── SupplierInvoiceListProjectionWriterPort.php
│       │   ├── SupplierInvoiceReaderPort.php
│       │   ├── SupplierInvoiceVoidStatusReaderPort.php
│       │   ├── SupplierInvoiceVoidWriterPort.php
│       │   ├── SupplierInvoiceWriterPort.php
│       │   ├── SupplierListProjectionSourceReaderPort.php
│       │   ├── SupplierListProjectionWriterPort.php
│       │   ├── SupplierPayableReminderReaderPort.php
│       │   ├── SupplierPaymentProofAttachmentReaderPort.php
│       │   ├── SupplierPaymentProofAttachmentWriterPort.php
│       │   ├── SupplierPaymentProofFileStoragePort.php
│       │   ├── SupplierPaymentReaderPort.php
│       │   ├── SupplierPaymentReversalWriterPort.php
│       │   ├── SupplierPaymentWriterPort.php
│       │   ├── SupplierReaderPort.php
│       │   ├── SupplierReceiptLineReaderPort.php
│       │   ├── SupplierReceiptReversalWriterPort.php
│       │   ├── SupplierReceiptWriterPort.php
│       │   ├── SupplierTableReaderPort.php
│       │   └── SupplierWriterPort.php
│       ├── ProductCatalog
│       │   ├── ProductDetailReaderPort.php
│       │   ├── ProductDuplicateCheckerPort.php
│       │   ├── ProductLifecyclePort.php
│       │   ├── ProductReaderPort.php
│       │   ├── ProductTableReaderPort.php
│       │   ├── ProductWriteConflictException.php
│       │   └── ProductWriterPort.php
│       ├── PushNotification
│       │   ├── PushNotificationSenderPort.php
│       │   ├── PushSubscriptionReaderPort.php
│       │   └── PushSubscriptionWriterPort.php
│       ├── Reporting
│       │   ├── DashboardInventoryOverviewReaderPort.php
│       │   ├── DashboardOperationalPerformanceReaderPort.php
│       │   ├── DashboardReportCachePort.php
│       │   ├── DashboardTopSellingProductReaderPort.php
│       │   ├── EmployeeDebtReportingSourceReaderPort.php
│       │   ├── InventoryMovementReportingSourceReaderPort.php
│       │   ├── OperationalExpenseReportingSourceReaderPort.php
│       │   ├── OperationalProfitReportingSourceReaderPort.php
│       │   ├── PayrollReportingSourceReaderPort.php
│       │   ├── SupplierPayableReportingSourceReaderPort.php
│       │   └── TransactionReportingSourceReaderPort.php
│       ├── RouteUrlGeneratorPort.php
│       ├── Shared
│       │   └── PaginatedResult.php
│       ├── TransactionManagerPort.php
│       └── UuidPort.php
├── Providers
│   ├── AppServiceProvider.php
│   └── HexagonalServiceProvider.php
└── Support
    └── ViewDateFormatter.php
database
├── factories
│   └── UserFactory.php
├── migrations
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2026_03_10_000100_create_actor_accesses_table.php
│   ├── 2026_03_10_000200_create_admin_transaction_capability_states_table.php
│   ├── 2026_03_10_000300_create_audit_logs_table.php
│   ├── 2026_03_11_000100_create_products_table.php
│   ├── 2026_03_12_000100_create_suppliers_table.php
│   ├── 2026_03_12_000200_create_supplier_invoices_table.php
│   ├── 2026_03_12_000300_create_supplier_invoice_lines_table.php
│   ├── 2026_03_12_000400_create_supplier_receipts_table.php
│   ├── 2026_03_12_000500_create_supplier_receipt_lines_table.php
│   ├── 2026_03_12_000600_create_inventory_movements_table.php
│   ├── 2026_03_12_000700_create_product_inventory_table.php
│   ├── 2026_03_12_000800_create_supplier_payments_table.php
│   ├── 2026_03_13_000100_create_product_inventory_costing_table.php
│   ├── 2026_03_14_000100_create_notes_table.php
│   ├── 2026_03_14_000200_create_work_items_table.php
│   ├── 2026_03_14_000300_create_work_item_service_details_table.php
│   ├── 2026_03_14_000400_create_work_item_external_purchase_lines_table.php
│   ├── 2026_03_14_000500_create_work_item_store_stock_lines_table.php
│   ├── 2026_03_14_000600_create_customer_payments_table.php
│   ├── 2026_03_14_000700_create_payment_allocations_table.php
│   ├── 2026_03_15_000100_create_customer_refunds_table.php
│   ├── 2026_03_15_000100_create_employees_table.php
│   ├── 2026_03_15_000110_create_employee_debts_table.php
│   ├── 2026_03_15_000120_create_employee_debt_payments_table.php
│   ├── 2026_03_15_000130_create_payroll_disbursements_table.php
│   ├── 2026_03_15_000140_create_expense_categories_table.php
│   ├── 2026_03_15_000150_create_operational_expenses_table.php
│   ├── 2026_03_16_000100_create_admin_cashier_area_access_states_table.php
│   ├── 2026_03_16_000110_create_supplier_payment_proof_attachments_table.php
│   ├── 2026_03_16_000130_create_employee_debt_adjustments_table.php
│   ├── 2026_03_16_000140_create_payroll_disbursement_reversals_table.php
│   ├── 2026_04_02_000800_create_payment_component_allocations_table.php
│   ├── 2026_04_02_000900_create_refund_component_allocations_table.php
│   ├── 2026_04_02_001000_create_note_mutation_events_table.php
│   ├── 2026_04_02_001100_create_note_mutation_snapshots_table.php
│   ├── 2026_04_04_100000_create_transaction_workspace_drafts_table.php
│   ├── 2026_04_06_210000_add_v2_hot_path_indexes_for_existing_tables.php
│   ├── 2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php
│   ├── 2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php
│   ├── 2026_04_06_220300_add_v2_note_mutation_workspace_foreign_keys.php
│   ├── 2026_04_06_230100_create_audit_events_and_snapshots_tables.php
│   ├── 2026_04_06_230200_add_soft_delete_foundation_to_products_and_suppliers.php
│   ├── 2026_04_06_230300_create_product_and_supplier_versions_tables.php
│   ├── 2026_04_06_230400_add_product_search_normalization_and_duplicate_hardening.php
│   ├── 2026_04_07_160100_fix_products_unique_constraints_for_soft_delete.php
│   ├── 2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php
│   ├── 2026_04_09_000200_create_supplier_invoice_versions_table.php
│   ├── 2026_04_10_000100_alter_employees_table_for_employee_master_v2.php
│   ├── 2026_04_10_000200_create_employee_versions_table.php
│   ├── 2026_04_11_000200_create_employee_debt_payment_reversals_table.php
│   ├── 2026_04_17_013500_add_stock_threshold_columns_to_products_table.php
│   ├── 2026_04_18_000100_alter_supplier_invoice_lines_for_revisioned_post_receipt_edit.php
│   ├── 2026_04_18_000200_alter_supplier_receipt_lines_add_snapshots.php
│   ├── 2026_04_18_000300_create_inventory_cost_adjustments_table.php
│   ├── 2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php
│   ├── 2026_04_19_000100_create_supplier_receipt_reversals_table.php
│   ├── 2026_04_19_000200_create_supplier_payment_reversals_table.php
│   ├── 2026_04_19_100000_create_supplier_invoice_list_projection_table.php
│   ├── 2026_04_19_100100_create_note_history_projection_table.php
│   ├── 2026_04_22_000001_create_note_revisions_table.php
│   ├── 2026_04_22_000002_create_note_revision_lines_table.php
│   ├── 2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
│   ├── 2026_04_23_120000_add_admin_procurement_projection_indexes.php
│   ├── 2026_04_23_130000_add_desc_index_for_admin_procurement_default_load.php
│   ├── 2026_04_23_140000_add_invoice_search_index_for_procurement_projection.php
│   ├── 2026_04_23_150000_create_supplier_list_projection_table.php
│   ├── 2026_04_27_000100_add_due_date_to_notes_table.php
│   ├── 2026_04_27_000200_create_push_subscriptions_table.php
│   ├── 2026_04_27_000300_add_expiration_audit_to_push_subscriptions_table.php
│   └── 2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php
└── seeders
    ├── DatabaseLoadSeeder.php
    ├── DatabaseSeeder.php
    ├── EmployeeFinanceBaselineSeeder.php
    ├── EmployeeFinanceSeeder.php
    ├── Expense
    │   └── ExpenseBaselineSeeder.php
    ├── ExpenseSeeder.php
    ├── FinancialCorrectionSeeder.php
    ├── Load
    │   ├── CustomerCorrectionLoadSeeder.php
    │   ├── CustomerPaymentLoadSeeder.php
    │   ├── CustomerRefundLoadSeeder.php
    │   ├── CustomerTransactionLoadSeeder.php
    │   ├── ExpenseLoadSeeder.php
    │   ├── ProcurementLoadSeeder.php
    │   └── ProductLoadSeeder.php
    ├── Product
    │   ├── ProductScenarioActiveBasicSeeder.php
    │   ├── ProductScenarioEditedSeeder.php
    │   ├── ProductScenarioLegacyIncompleteSeeder.php
    │   ├── ProductScenarioRecreatedSeeder.php
    │   ├── ProductScenarioSoftDeletedSeeder.php
    │   ├── ProductSeedCatalog.php
    │   └── ProductSeedThresholds.php
    ├── ProductInventoryThresholdBackfillSeeder.php
    ├── ProductSeeder.php
    ├── SeedLevel1Seeder.php
    ├── SeedLevel2Seeder.php
    ├── SeedLevel3Seeder.php
    ├── SupplierInvoiceAnnualDenseSeeder.php
    ├── SupplierInvoiceBaselineSeeder.php
    ├── SupplierInvoiceScenarioSeeder.php
    ├── SupplierInvoiceSeeder.php
    ├── SupplierInvoiceVoidedScenarioSeeder.php
    ├── SupplierPaymentProofSeeder.php
    ├── SupplierSeeder.php
    ├── Support
    │   ├── SeedDensity.php
    │   └── SeedWindow.php
    ├── Transaction
    │   ├── CustomerCorrectionBaselineSeeder.php
    │   ├── CustomerPaymentBaselineSeeder.php
    │   ├── CustomerRefundBaselineSeeder.php
    │   └── CustomerTransactionBaselineSeeder.php
    ├── UserSeeder.php
    └── WorkshopStressTestSeeder.php
docs
├── adr
│   ├── 0001-one-note-multi-item.md
│   ├── 0002-negative-stock-policy-default-off.md
│   ├── 0003-external-spare-part-as-case-cost.md
│   ├── 0004-minimum-selling-price-guard.md
│   ├── 0005-paid-note-correction-requires-audit.md
│   ├── 0006-costing-strategy-default-average-fifo-ready.md
│   ├── 0007-admin-transaction-entry-behind-capability-policy.md
│   ├── 0008-audit-first-sensitive-mutations.md
│   ├── 0009-reporting-as-read-model.md
│   ├── 0010-telegram-wa-integration-as-adapter.md
│   ├── 0011-money-stored-as-integer-rupiah.md
│   ├── 0012-product-master-must-exist-before-supplier-receipt.md
│   ├── 0013-employee-finance-foundation.md
│   ├── 0014-note-operational-status-open-close-editable-partial-payment.md
│   ├── 0015-note-operational-status-open-close-editable-partial-payment.md
│   ├── 0016-post-close-note-correction-and-refund-flexibility.md
│   ├── 0017-audit-log-retention-and-archive-evaluation.md
│   ├── 0018-note-revision-settlement-external-product-lifecycle.md
│   ├── 0021-note-detail-hybrid-versioning-billing-refund.md
│   ├── 2026-04-29-note-current-projection-and-current-only-refund.md
│   ├── 2026-05-04-note-revision-carry-forward-settlement.md
│   └── README.md
├── AI_RULES
│   ├── 00_INDEX.md
│   ├── 01_DECISION_POLICY.md
│   ├── 02_GPT_BOOTSTRAP_PROMPT.md
│   ├── 03_SESSION_START_PROTOCOL.md
│   ├── 04_HANDOFF_TEMPLATE.md
│   ├── 05_FINAL_REVIEW_CHECKLIST.md
│   ├── 10_CORE
│   │   ├── 10_SCOPE_AND_FACTS.md
│   │   ├── 11_BLUEPRINT_FIRST.md
│   │   ├── 12_STEP_BY_STEP_EXECUTION.md
│   │   └── 13_PROOF_AND_PROGRESS.md
│   ├── 20_WORKFLOW
│   │   ├── 20_RESPONSE_STRUCTURE.md
│   │   ├── 21_ACTIVE_STEP_POLICY.md
│   │   ├── 22_OPTION_EVALUATION.md
│   │   ├── 23_HANDOFF_POLICY.md
│   │   └── 24_SESSION_CAPACITY_POLICY.md
│   ├── 30_OUTPUT
│   │   ├── 30_FILE_DELIVERY.md
│   │   ├── 31_MARKDOWN_OUTPUT_RULE.md
│   │   ├── 32_BLADE_RULE.md
│   │   └── 33_TERMINAL_COMMAND_DELIVERY.md
│   ├── 40_ARCHITECTURE
│   │   ├── 40_HEXAGONAL_BASELINE.md
│   │   ├── 41_PUBLIC_CONTRACTS.md
│   │   ├── 42_ERROR_HANDLING_AND_REDACTION.md
│   │   ├── 43_DEBUG_GATING.md
│   │   └── 44_AUDIT_AND_DOD.md
│   ├── 50_DOMAIN_KASIR
│   │   ├── 50_FINAL_DOMAIN_MAP.md
│   │   ├── 51_UI_TERMS_AND_STATUS.md
│   │   ├── 52_PAYMENT_LIFECYCLE.md
│   │   └── 53_REPORTING_BOUNDARY.md
│   ├── 60_STACK
│   │   ├── 60_LARAVEL_RULES.md
│   │   ├── 61_GO_RULES.md
│   │   └── 62_AWS_BASELINE.md
│   ├── 99_CHANGELOG.md
│   └── HANDOFF_AI_RULES_MODULAR_2026_03_26.md
├── AI_USAGE_GUIDE.md
├── audit
│   └── codex-security
│       └── 2026-05-04-refunded-revisions-undercount-paid-totals-audit.md
├── blueprint
│   ├── v1
│   │   └── blueprint_v1.md
│   └── v2
│       ├── feature-continuation
│       │   └── 00-blueprint.md
│       ├── note-finance
│       │   ├── 2026-04-29-note-finance-current-projection-addendum.md
│       │   └── 2026-04-29-note-finance-stabilization-blueprint.md
│       └── report-export
│           └── 2026-05-01-pdf-excel-export-blueprint.md
├── DOCS_HELP.md
├── dod
│   ├── dod_v1.md
│   └── report-export-dod.md
├── error_log
│   ├── 001-refunds-counted-as-paid-in-note-totals.md
│   ├── 002-seeder-introduces-predictable-admin-credentials.md
│   ├── 003-refunded-revised-notes-are-misclassified-as-underpaid.md
│   ├── 004-refunded-work-items-survive-revisions-and-inflate-stock.md
│   ├── 005-note-revision-silently-drops-overpaid-allocations.md
│   ├── 006-client-controlled-price-basis-bypasses-minimum-price-checks.md
│   ├── 007-admin-note-edit-page-exposes-stored-xss.md
│   ├── 008-legacy-paid-notes-can-be-paid-again.md
│   ├── 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
│   ├── 010-revision-reallocation-can-lose-concurrent-payments.md
│   ├── 011-cashier-revision-path-mutates-settled-note-state.md
│   ├── 012-canceled-note-rows-re-enter-payment-flows.md
│   ├── 013-forged-row-refund-can-auto-finalize-unpaid-notes.md
│   ├── 014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
│   ├── 015-refunded-notes-expose-edit-workspace.md
│   ├── 016-unauthenticated-admin-capability-toggle-endpoints.md
│   ├── 017-workspace-edit-payments-ignore-existing-note-payments.md
│   ├── 018-refunded-notes-bypass-cashier-closed-note-guards.md
│   ├── 019-cashiers-can-list-historical-closed-notes-by-date.md
│   ├── 020-admin-note-actions-bypass-transaction-capability.md
│   ├── 021-refunds-can-be-recorded-on-open-notes.md
│   ├── 022-cashier-refund-route-bypasses-note-access-guard.md
│   ├── 023-public-helper-can-expose-private-storage.md
│   ├── 024-reflected-xss-in-expense-create-json-config.md
│   ├── 025-reflected-javascript-url-in-product-return-link.md
│   ├── 026-concurrent-note-payments-can-over-allocate-balances.md
│   ├── 027-admin-invoice-creation-bypasses-transaction-entry-gate.md
│   ├── 028-di-fix-exposes-unsafe-proof-attachment-content-type.md
│   └── 029-cashier-create-page-leaks-total-note-count.md
├── handoff
│   ├── codex-security
│   │   └── 2026-05-04-refunded-revisions-undercount-paid-totals-handoff.md
│   ├── handoff_step_02.md
│   ├── handoff_step_03-05.md
│   ├── handoff_step_03a.md
│   ├── handoff_step_03b.md
│   ├── handoff_step_03_enforcement_cleanup.md
│   ├── handoff_step_04.md
│   ├── handoff_step_05_gate_step_6.md
│   ├── handoff_step_05.md
│   ├── handoff_step_06_inventory_engine.md
│   ├── handoff_step_07a_preparation_design_lock.md
│   ├── handoff_step_07b_note_multi_item_engine.md
│   ├── handoff_step_07c_final_closure.md
│   ├── handoff_step_08b_final_audit.md
│   ├── handoff_step_08_payment_receivable_engine_transition_to_step_9.md
│   ├── handoff_step_09_correction_refund_audit.md
│   ├── handoff_step_10_employee_finance.md
│   ├── handoff_step_11_operational_expense.md
│   ├── handoff_step_12_reporting_mapping.md
│   ├── handoff_step_12_reporting_v1_closed.md
│   ├── handoff_template.md
│   ├── README.md
│   ├── ui
│   │   ├── audit_filter_drawer_theme.md
│   │   ├── handoff_01_auth_ui_access_slice.md
│   │   ├── handoff_02_product_ui_interactive_admin_table.md
│   │   ├── handoff_03_supplier_procurement_followup_questions.md
│   │   ├── handoff_04_supplier_procurement_policy_and_supplier_summary_execution_ready.md
│   │   ├── handoff_05_admin_procurement_product_ui_consistency.md
│   │   ├── handoff_06_employee_finance_payroll_debt.md
│   │   ├── handoff_07_operational_expense_hardening_closure.md
│   │   ├── handoff_08_transaction_ui_note_payment_edit_policy.md
│   │   └── handoff_09_global_alert_datepicker_standardization.md
│   └── v2
│       ├── 01_foundation_migration_and_cleanup.md
│       ├── 02-product-handoff.md
│       ├── 03-supplier-invoice-blueprint-handoff.md
│       ├── 04-procurement-supplier-invoice.md
│       ├── 05-employee-salary-debt-v1.md
│       ├── 06-employee-finance-handoff-v2.md
│       ├── 07-employee-payroll-handoff.md
│       ├── 08-employee-payroll-debt-handoff.md
│       ├── 09-operational-expense-implementation-handoff.md
│       ├── 10-note-open-close-refund-handoff.md
│       ├── 10-seeder-levels-handoff.md
│       ├── 11-reporting-v2-repair-handoff.md
│       ├── 12-product-threshold-dashboard-seeder-handoff.md
│       ├── 13-chart-analytics.md
│       ├── audit
│       │   └── 2026-05-01-audit-log-unified-reader-proof.md
│       ├── cashier
│       │   └── handoff_note_detail_hybrid_rebuild.md
│       ├── dashboard
│       │   ├── 2026-05-01-dashboard-performance-completion-proof.md
│       │   ├── 2026-05-01-dashboard-performance-goal-closed.md
│       │   ├── 2026-05-01-dashboard-visibility-performance-proof.md
│       │   ├── 2026-05-02-dashboard-export-option-a-decision.md
│       │   ├── 2026-05-02-dashboard-stock-visual-severity-proof.md
│       │   ├── 2026-05-02-dashboard-visual-trend-proof.md
│       │   ├── 2026-05-03-dashboard-header-filter-drawer-closeout.md
│       │   └── 2026-05-03-dashboard-stock-visual-severity-closeout.md
│       ├── feature-continuation
│       │   ├── 01-system-ambiguity-inventory.md
│       │   ├── 2026-05-01-reporting-dashboard-refund-fixes.md
│       │   └── adr-0016-post-close-handoff.md
│       ├── note-finance
│       │   ├── 2026-04-29-current-projection-refund-edit-handoff.md
│       │   └── 2026-04-30-adr-0016-completion-handoff.md
│       ├── report
│       │   ├── 00-reporting-blueprint-handoff.md
│       │   └── reporting_v2_handoff_step1_to_step8.md
│       ├── reporting
│       │   └── 2026-05-02-report-export-completion-proof.md
│       ├── seeder-audit
│       │   └── 2026-04-26-migration-seeder-audit-status.md
│       ├── seedernew
│       │   ├── 2026-04-26-seedernew-audit-command-proof.md
│       │   ├── 2026-04-26-seedernew-finance-blueprint-adr.md
│       │   ├── 2026-04-26-seedernew-make2-idempotency-proof.md
│       │   └── 2026-04-26-seedernew-scenario-matrix.md
│       └── ui
│           ├── handoff_05_procurement_edit_locked_to_koreksi.md
│           ├── handoff-note-ui-vnext-live.md
│           ├── handoff_projection_index_and_test_alignment.md
│           ├── handoff-refund-detail-ui.md
│           ├── note-detail-presentation-contract-change.md
│           ├── note-hybrid-phase.md
│           ├── note-refund-a1-t1-hard-handoff-2.md
│           ├── note-refund-a1-t1-hard-handoff.md
│           ├── note-refund-ui-failure-handoff.md
│           ├── note-ui-payment-report-polish-handoff.md
│           ├── note-workspace-ui-handoff.md
│           └── procurement_void_integrity_and_seeder_alignment.md
├── README.md
└── workflow
    ├── report-export-v1.md
    ├── reporting_v2.md
    └── workflow_v1.md
lang
└── id
    └── validation.php
mk
├── hexagonal.mk
└── push.mk
resources
└── views
    ├── admin
    │   ├── audit_logs
    │   │   ├── index.blade.php
    │   │   └── partials
    │   │       └── row.blade.php
    │   ├── dashboard
    │   │   ├── index.blade.php
    │   │   └── partials
    │   │       ├── cash-change-denominations.blade.php
    │   │       └── filter_drawer.blade.php
    │   ├── employee_debts
    │   │   ├── create.blade.php
    │   │   ├── index.blade.php
    │   │   ├── principal.blade.php
    │   │   └── show.blade.php
    │   ├── employees
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   ├── index.blade.php
    │   │   ├── payrolls.blade.php
    │   │   └── show.blade.php
    │   ├── expenses
    │   │   ├── categories
    │   │   │   ├── create.blade.php
    │   │   │   ├── edit.blade.php
    │   │   │   ├── index.blade.php
    │   │   │   └── partials
    │   │   │       └── filter_drawer.blade.php
    │   │   ├── create.blade.php
    │   │   ├── index.blade.php
    │   │   └── partials
    │   │       └── filter_drawer.blade.php
    │   ├── notes
    │   │   ├── index.blade.php
    │   │   ├── partials
    │   │   │   └── filter-drawer.blade.php
    │   │   └── show.blade.php
    │   ├── payrolls
    │   │   ├── create.blade.php
    │   │   └── index.blade.php
    │   ├── procurement
    │   │   └── supplier_invoices
    │   │       ├── create.blade.php
    │   │       ├── edit.blade.php
    │   │       ├── index.blade.php
    │   │       ├── partials
    │   │       │   ├── create-dropdown-overflow-fix.blade.php
    │   │       │   └── filter_drawer.blade.php
    │   │       ├── payment_proofs.blade.php
    │   │       └── show.blade.php
    │   ├── products
    │   │   ├── create.blade.php
    │   │   ├── edit.blade.php
    │   │   ├── index.blade.php
    │   │   ├── partials
    │   │   │   └── filter_drawer.blade.php
    │   │   ├── show.blade.php
    │   │   └── stock-edit.blade.php
    │   ├── reporting
    │   │   ├── employee_debt
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── inventory_stock_value
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── operational_expense
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── operational_profit
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── partials
    │   │   │   └── period_filter.blade.php
    │   │   ├── payroll
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── supplier_payable
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   ├── transaction_cash_ledger
    │   │   │   ├── export_pdf.blade.php
    │   │   │   └── index.blade.php
    │   │   └── transaction_summary
    │   │       ├── export_pdf.blade.php
    │   │       └── index.blade.php
    │   └── suppliers
    │       ├── edit.blade.php
    │       └── index.blade.php
    ├── auth
    │   └── login.blade.php
    ├── cashier
    │   ├── dashboard
    │   │   └── index.blade.php
    │   └── notes
    │       ├── index.blade.php
    │       ├── partials
    │       │   ├── add-rows-form.blade.php
    │       │   ├── billing-table.blade.php
    │       │   ├── correction-actions.blade.php
    │       │   ├── correction-history.blade.php
    │       │   ├── create-script.blade.php
    │       │   ├── filter-drawer.blade.php
    │       │   ├── note-overview.blade.php
    │       │   ├── note-revision-timeline.blade.php
    │       │   ├── note-rows-table.blade.php
    │       │   ├── payment-actions.blade.php
    │       │   ├── payment-form.blade.php
    │       │   ├── payment-modal.blade.php
    │       │   ├── refund-form.blade.php
    │       │   └── refund-modal.blade.php
    │       ├── show.blade.php
    │       └── workspace
    │           ├── create.blade.php
    │           └── partials
    │               ├── dropdown-layer-fix.blade.php
    │               ├── info-card.blade.php
    │               ├── item-type-menu.blade.php
    │               ├── payment-modal.blade.php
    │               ├── payment-modal-cash.blade.php
    │               ├── payment-modal-footer.blade.php
    │               ├── payment-modal-full.blade.php
    │               ├── payment-modal-left.blade.php
    │               ├── payment-modal-partial.blade.php
    │               ├── payment-modal-right.blade.php
    │               ├── payment-modal-summary.blade.php
    │               ├── refund-modal.blade.php
    │               ├── rincian-card.blade.php
    │               └── templates
    │                   ├── product.blade.php
    │                   ├── service.blade.php
    │                   ├── service-external.blade.php
    │                   └── service-store-stock.blade.php
    ├── errors
    │   ├── 403.blade.php
    │   ├── 404.blade.php
    │   ├── 419.blade.php
    │   ├── 429.blade.php
    │   ├── 500.blade.php
    │   └── 503.blade.php
    ├── layouts
    │   ├── app.blade.php
    │   ├── auth.blade.php
    │   ├── error.blade.php
    │   └── partials
    │       ├── alerts.blade.php
    │       ├── date-picker-assets.blade.php
    │       ├── footer.blade.php
    │       ├── pagination.blade.php
    │       ├── sidebar-admin.blade.php
    │       └── sidebar-cashier.blade.php
    └── shared
        └── notes
            ├── partials
            │   ├── header-summary.blade.php
            │   ├── line-workspace.blade.php
            │   ├── payment-summary-actions.blade.php
            │   └── versioning-compact.blade.php
            └── show.blade.php
routes
├── console_audit.php
├── console.php
├── web
│   ├── admin_audit_logs.php
│   ├── admin_employee_debts.php
│   ├── admin_employees.php
│   ├── admin_expenses.php
│   ├── admin_payrolls.php
│   ├── admin_procurement.php
│   ├── admin_products.php
│   ├── admin_reporting.php
│   ├── admin_suppliers.php
│   ├── auth.php
│   ├── dashboard.php
│   ├── health.php
│   ├── identity_access.php
│   ├── note.php
│   ├── procurement.php
│   ├── product_catalog.php
│   └── push_notifications.php
└── web.php
scripts
├── audit_ai_rules.sh
├── audit-blade-no-php.php
├── audit-hex.php
└── audit-line-count.php
tests
├── Arch
│   └── HexagonalDependencyTest.php
├── Feature
│   ├── Admin
│   │   ├── AdminDashboardPageFeatureTest.php
│   │   └── Product
│   │       └── ProductMasterValidationFeedbackTest.php
│   ├── AuditLog
│   │   ├── AdminAuditEventPageFeatureTest.php
│   │   ├── AdminAuditLogPageFeatureTest.php
│   │   └── AdminAuditLogUnifiedSourcePageFeatureTest.php
│   ├── Auth
│   │   ├── WebAuthenticationFeatureTest.php
│   │   └── WebPageAccessFeatureTest.php
│   ├── Database
│   │   ├── SupplierPaymentReversalFoundationMigrationTest.php
│   │   ├── SupplierReceiptReversalFoundationMigrationTest.php
│   │   ├── V2AuditFoundationMigrationTest.php
│   │   ├── V2ForeignKeysMigrationTest.php
│   │   ├── V2HotPathIndexesMigrationTest.php
│   │   ├── V2MasterSoftDeleteFoundationMigrationTest.php
│   │   ├── V2MasterVersioningFoundationMigrationTest.php
│   │   ├── V2NoteOperationalStateMigrationTest.php
│   │   └── V2ProductSearchNormalizationMigrationTest.php
│   ├── EmployeeFinance
│   │   ├── AdjustEmployeeDebtFeatureTest.php
│   │   ├── CreateEmployeeDebtPageFeatureTest.php
│   │   ├── CreateEmployeePageFeatureTest.php
│   │   ├── CreatePayrollPageFeatureTest.php
│   │   ├── DisbursePayrollFeatureTest.php
│   │   ├── EmployeeDebtDetailPageFeatureTest.php
│   │   ├── EmployeeDebtIndexPageFeatureTest.php
│   │   ├── EmployeeDebtInvariantFeatureTest.php
│   │   ├── EmployeeDebtPaymentReversalReadModelFeatureTest.php
│   │   ├── EmployeeDebtTableDataAccessFeatureTest.php
│   │   ├── EmployeeDebtTableDataQueryFeatureTest.php
│   │   ├── EmployeeDebtTableDataValidationFeatureTest.php
│   │   ├── EmployeeDetailPageFeatureTest.php
│   │   ├── EmployeeDetailVersionTimelineFeatureTest.php
│   │   ├── EmployeeEditPageFeatureTest.php
│   │   ├── EmployeeIndexPageFeatureTest.php
│   │   ├── EmployeeTableDataAccessFeatureTest.php
│   │   ├── EmployeeTableDataQueryFeatureTest.php
│   │   ├── EmployeeTableDataValidationFeatureTest.php
│   │   ├── PayEmployeeDebtFeatureTest.php
│   │   ├── PayrollIndexPageFeatureTest.php
│   │   ├── PayrollTableDataAccessFeatureTest.php
│   │   ├── PayrollTableDataQueryFeatureTest.php
│   │   ├── PayrollTableDataValidationFeatureTest.php
│   │   ├── RecordEmployeeDebtFeatureTest.php
│   │   ├── RegisterEmployeeFeatureTest.php
│   │   ├── ReverseEmployeeDebtPaymentFeatureTest.php
│   │   ├── ReversePayrollDisbursementFeatureTest.php
│   │   └── StorePayrollFeatureTest.php
│   ├── Expense
│   │   ├── ActivateExpenseCategoryFeatureTest.php
│   │   ├── ActivateExpenseCategoryHttpFeatureTest.php
│   │   ├── CreateExpenseCategoryFeatureTest.php
│   │   ├── CreateExpenseCategoryPageFeatureTest.php
│   │   ├── CreateExpensePageFeatureTest.php
│   │   ├── DeactivateExpenseCategoryFeatureTest.php
│   │   ├── DeactivateExpenseCategoryHttpFeatureTest.php
│   │   ├── ExpenseCategoryEditPageFeatureTest.php
│   │   ├── ExpenseCategoryIndexPageFeatureTest.php
│   │   ├── ExpenseCategoryTableDataAccessFeatureTest.php
│   │   ├── ExpenseCategoryTableDataQueryFeatureTest.php
│   │   ├── ExpenseCategoryTableDataValidationFeatureTest.php
│   │   ├── ExpenseIndexPageFeatureTest.php
│   │   ├── ExpenseTableDataAccessFeatureTest.php
│   │   ├── ExpenseTableDataQueryFeatureTest.php
│   │   ├── ExpenseTableDataValidationFeatureTest.php
│   │   ├── RecordOperationalExpenseFeatureTest.php
│   │   ├── SoftDeleteOperationalExpenseHttpFeatureTest.php
│   │   ├── StoreExpenseCategoryHttpFeatureTest.php
│   │   ├── StoreExpenseHttpFeatureTest.php
│   │   ├── UpdateExpenseCategoryFeatureTest.php
│   │   └── UpdateExpenseCategoryHttpFeatureTest.php
│   ├── Foundation
│   │   ├── ErrorJsonFallbackFeatureTest.php
│   │   └── ErrorPageHtmlFeatureTest.php
│   ├── Http
│   │   └── HealthCheckTest.php
│   ├── IdentityAccess
│   │   ├── DisableAdminTransactionCapabilityFeatureTest.php
│   │   ├── EnableAdminTransactionCapabilityFeatureTest.php
│   │   └── TransactionEntryMiddlewareFeatureTest.php
│   ├── Inventory
│   │   ├── IssueInventoryFeatureTest.php
│   │   ├── RebuildInventoryCostingProjectionFeatureTest.php
│   │   ├── RebuildInventoryCostingProjectionWithStockOutFeatureTest.php
│   │   ├── RebuildInventoryProjectionFeatureTest.php
│   │   ├── ReverseIssuedInventoryOperationFeatureTest.php
│   │   └── ReverseNoteStoreStockInventoryOperationFeatureTest.php
│   ├── Note
│   │   ├── AddExternalPurchaseWorkItemFeatureTest.php
│   │   ├── AddNoteRowsHttpFeatureTest.php
│   │   ├── AddServiceOnlyWorkItemFeatureTest.php
│   │   ├── AddServiceWithStoreStockPartWorkItemFeatureTest.php
│   │   ├── AddStoreStockSaleOnlyWorkItemFeatureTest.php
│   │   ├── AddWorkItemToPaidNoteFeatureTest.php
│   │   ├── AdminNoteDetailPageFeatureTest.php
│   │   ├── AdminNoteHistoryPageFeatureTest.php
│   │   ├── AdminNoteHistoryTableDataFeatureTest.php
│   │   ├── AdminNoteWorkspaceReplacementFeatureTest.php
│   │   ├── AdminReopenClosedNoteHttpFeatureTest.php
│   │   ├── CashierClosedNoteRefundViewFeatureTest.php
│   │   ├── CashierClosedNoteWorkspaceReplacementFeatureTest.php
│   │   ├── CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php
│   │   ├── CashierClosedReplacementOutstandingPaymentFeatureTest.php
│   │   ├── CashierDetailRenderedBillingRowsPaymentFeatureTest.php
│   │   ├── CashierEditPageUsesCurrentRevisionFeatureTest.php
│   │   ├── CashierHybridNoteDetailFeatureTest.php
│   │   ├── CashierHybridPaymentComponentSelectionFeatureTest.php
│   │   ├── CashierHybridPaymentDpPresetFeatureTest.php
│   │   ├── CashierHybridPaymentSettleIntentFeatureTest.php
│   │   ├── CashierNoteDetailAccessGuardFeatureTest.php
│   │   ├── CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php
│   │   ├── CashierNoteDetailPaymentActionPolicyFeatureTest.php
│   │   ├── CashierNoteDetailProductNameDisplayFeatureTest.php
│   │   ├── CashierNoteDetailServiceProductNameDisplayFeatureTest.php
│   │   ├── CashierNoteDetailSimplePaymentModalUxFeatureTest.php
│   │   ├── CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php
│   │   ├── CashierNoteHistoryLegacyLineSummaryFeatureTest.php
│   │   ├── CashierNoteHistoryPageFeatureTest.php
│   │   ├── CashierNoteHistoryTableClosurePolicyFeatureTest.php
│   │   ├── CashierNoteHistoryTableFeatureTest.php
│   │   ├── CashierNoteMutationHistoryViewFeatureTest.php
│   │   ├── CashierNoteRevisionCleanupFeatureTest.php
│   │   ├── CashierNoteRevisionSmokeTest.php
│   │   ├── CashierNoteRevisionSubmitFeatureTest.php
│   │   ├── CashierNoteVersioningLineSnapshotViewFeatureTest.php
│   │   ├── CashierOpenNoteRefundStandbyViewFeatureTest.php
│   │   ├── CashierProductReplacementBackdatedPriceFinanceFeatureTest.php
│   │   ├── CashierProtectedNoteRoutesAccessGuardFeatureTest.php
│   │   ├── CashierRefundedNoteDetailViewFeatureTest.php
│   │   ├── CashierRefundRejectsOpenLineFeatureTest.php
│   │   ├── CashierRefundSelectionFirstFeatureTest.php
│   │   ├── CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest.php
│   │   ├── ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php
│   │   ├── ClosedNoteFullRefundLifecycleFeatureTest.php
│   │   ├── ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest.php
│   │   ├── ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php
│   │   ├── CorrectPaidServiceOnlyWorkItemFeatureTest.php
│   │   ├── CorrectPaidServiceOnlyWorkItemHttpFeatureTest.php
│   │   ├── CorrectPaidServiceOnlyWritesMutationTimelineFeatureTest.php
│   │   ├── CorrectPaidServiceWithExternalPurchaseServiceFeeOnlyFeatureTest.php
│   │   ├── CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest.php
│   │   ├── CorrectPaidWorkItemStatusFeatureTest.php
│   │   ├── CorrectPaidWorkItemStatusHttpFeatureTest.php
│   │   ├── CreateNoteFeatureTest.php
│   │   ├── CreateNoteHttpFeatureTest.php
│   │   ├── CreateTransactionWorkspaceDefaultCustomerNameFeatureTest.php
│   │   ├── CreateTransactionWorkspaceFullCashFeatureTest.php
│   │   ├── CreateTransactionWorkspaceFullTransferFeatureTest.php
│   │   ├── CreateTransactionWorkspacePartialTransferFeatureTest.php
│   │   ├── CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php
│   │   ├── CreateTransactionWorkspaceServiceStoreStockFeatureTest.php
│   │   ├── CreateTransactionWorkspaceSkipFeatureTest.php
│   │   ├── CreateTransactionWorkspaceTemplateContractFeatureTest.php
│   │   ├── DatabaseDueNoteReminderReaderFeatureTest.php
│   │   ├── EditableWorkspaceNoteGuardFeatureTest.php
│   │   ├── EditTransactionWorkspacePageFeatureTest.php
│   │   ├── LegacyAllocatedNoteDetailFeatureTest.php
│   │   ├── NoteCorrectionHistoryBuilderFeatureTest.php
│   │   ├── NoteCorrectionHistoryPageFeatureTest.php
│   │   ├── NoteDetailEditEntryFeatureTest.php
│   │   ├── NoteDetailPageFeatureTest.php
│   │   ├── NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest.php
│   │   ├── NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php
│   │   ├── NoteOperationalStatePersistenceFeatureTest.php
│   │   ├── NotePrototypeRedirectFeatureTest.php
│   │   ├── ReadNoteMultiItemFeatureTest.php
│   │   ├── RecordClosedNoteRefundControllerFeatureTest.php
│   │   ├── RecordNotePaymentHttpFeatureTest.php
│   │   ├── ReopenClosedNoteFeatureTest.php
│   │   ├── RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php
│   │   ├── UpdateServiceWithExternalPurchaseServiceFeeOnlyWriterFeatureTest.php
│   │   ├── UpdateServiceWithStoreStockPartServiceFeeOnlyWriterFeatureTest.php
│   │   ├── UpdateTransactionWorkspaceFeatureTest.php
│   │   └── UpdateWorkItemStatusFeatureTest.php
│   ├── Payment
│   │   ├── AllocateCustomerPaymentFeatureTest.php
│   │   ├── AutoClosePaidNoteOnFullPaymentFeatureTest.php
│   │   ├── DatabasePaymentAllocationReaderAdapterFeatureTest.php
│   │   ├── RecordAndAllocateNotePaymentFeatureTest.php
│   │   ├── RecordCustomerPaymentFeatureTest.php
│   │   ├── RecordCustomerRefundFeatureTest.php
│   │   ├── RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php
│   │   ├── RecordSelectedRowsCustomerRefundFeatureTest.php
│   │   └── RecordSelectedRowsNotePaymentFeatureTest.php
│   ├── Procurement
│   │   ├── AttachSupplierPaymentProofFeatureTest.php
│   │   ├── CreateSupplierInvoiceDuplicateNumberValidationFeatureTest.php
│   │   ├── CreateSupplierInvoiceFeatureTest.php
│   │   ├── CreateSupplierInvoiceGrandTotalGuardFeatureTest.php
│   │   ├── CreateSupplierInvoicePageFeatureTest.php
│   │   ├── DatabaseSupplierPayableReminderReaderFeatureTest.php
│   │   ├── EditSupplierInvoicePageFeatureTest.php
│   │   ├── EditSupplierInvoiceRevisionContractFeatureTest.php
│   │   ├── ExtremeEditableProcurementHeaderPolicyMatrixFeatureTest.php
│   │   ├── ExtremeProcurementAdminGuardAndAttachmentFailureMatrixFeatureTest.php
│   │   ├── ExtremeProcurementDuplicateAndIsolationMatrixFeatureTest.php
│   │   ├── ExtremeProcurementReceivePaymentPrecisionMatrixFeatureTest.php
│   │   ├── ExtremeReceivedInvoiceRevisionMatrixFeatureTest.php
│   │   ├── ExtremeReceiveRequestValidationMatrixFeatureTest.php
│   │   ├── ExtremeSupplierPaymentProofMatrixFeatureTest.php
│   │   ├── ProcurementInvoiceCurrentLineReadModelFeatureTest.php
│   │   ├── ProcurementInvoiceDetailPageFeatureTest.php
│   │   ├── ProcurementInvoiceIndexPageFeatureTest.php
│   │   ├── ProcurementInvoicePaymentProofPageFeatureTest.php
│   │   ├── ProcurementInvoiceTableDataAccessFeatureTest.php
│   │   ├── ProcurementInvoiceTableDataQueryFeatureTest.php
│   │   ├── ProcurementInvoiceTableDataValidationFeatureTest.php
│   │   ├── ProcurementInvoiceVoidedDetailPageFeatureTest.php
│   │   ├── ProcurementInvoiceVoidedPaymentProofPageFeatureTest.php
│   │   ├── ProcurementInvoiceVoidedTableFilterFeatureTest.php
│   │   ├── ProductLookupFeatureTest.php
│   │   ├── ReceiveSupplierInvoiceFeatureTest.php
│   │   ├── RecordSupplierPaymentFeatureTest.php
│   │   ├── ReverseSupplierPaymentFeatureTest.php
│   │   ├── ReverseSupplierReceiptFeatureTest.php
│   │   ├── ReviseReceivedSupplierInvoiceDeltaFeatureTest.php
│   │   ├── ReviseReceivedSupplierInvoiceNegativeStockGuardFeatureTest.php
│   │   ├── ServeSupplierPaymentProofAttachmentFeatureTest.php
│   │   ├── SupplierEditPageFeatureTest.php
│   │   ├── SupplierIndexPageFeatureTest.php
│   │   ├── SupplierInvoiceDuplicateProductValidationFeatureTest.php
│   │   ├── SupplierListProjectionRuntimeSyncFeatureTest.php
│   │   ├── SupplierLookupFeatureTest.php
│   │   ├── SupplierTableDataAccessFeatureTest.php
│   │   ├── SupplierTableDataQueryFeatureTest.php
│   │   ├── SupplierTableDataValidationFeatureTest.php
│   │   ├── UpdateSupplierFeatureTest.php
│   │   ├── UpdateSupplierInvoiceFeatureTest.php
│   │   ├── VoidSupplierInvoiceFeatureTest.php
│   │   ├── VoidSupplierInvoiceIntegrityFeatureTest.php
│   │   └── VoidSupplierInvoiceMutationGuardFeatureTest.php
│   ├── ProductCatalog
│   │   ├── CreateProductFeatureTest.php
│   │   ├── CreateProductThresholdFeatureTest.php
│   │   ├── ExtremeProductLifecycleWithPayableHistoryMatrixFeatureTest.php
│   │   ├── ExtremeProductMasterMutationMatrixFeatureTest.php
│   │   ├── ExtremeProductMasterValidationMatrixFeatureTest.php
│   │   ├── ExtremeProductStockAdjustmentMatrixFeatureTest.php
│   │   ├── ProductCreatePageFeatureTest.php
│   │   ├── ProductDetailPageFeatureTest.php
│   │   ├── ProductEditPageFeatureTest.php
│   │   ├── ProductIndexPageFeatureTest.php
│   │   ├── ProductStockAdjustmentFeatureTest.php
│   │   ├── ProductTableDataAccessFeatureTest.php
│   │   ├── ProductTableDataQueryFeatureTest.php
│   │   ├── ProductTableDataValidationFeatureTest.php
│   │   ├── RestoreProductFeatureTest.php
│   │   ├── ReverseProductStockAdjustmentFeatureTest.php
│   │   ├── UpdateProductFeatureTest.php
│   │   └── UpdateProductThresholdFeatureTest.php
│   ├── PushNotification
│   │   ├── PushNotificationAssetFeatureTest.php
│   │   ├── PushNotificationRenderedConfigSafetyFeatureTest.php
│   │   ├── PushSubscriptionEndpointFeatureTest.php
│   │   ├── SendDueNoteReminderPushCommandFeatureTest.php
│   │   ├── SendSupplierPayableReminderPushCommandFeatureTest.php
│   │   ├── StorePushSubscriptionHandlerFeatureTest.php
│   │   └── WebPushConfigFeatureTest.php
│   ├── Reporting
│   │   ├── CashChangeDenominationDashboardDatasetFeatureTest.php
│   │   ├── DashboardTopSellingProductQueryFeatureTest.php
│   │   ├── EmployeeDebtReportPageFeatureTest.php
│   │   ├── EmployeeDebtSummaryHardeningFeatureTest.php
│   │   ├── ExtremeSupplierPayablePrecisionMatrixFeatureTest.php
│   │   ├── GetDashboardOperationalPerformanceDatasetFeatureTest.php
│   │   ├── GetEmployeeDebtReportDatasetFeatureTest.php
│   │   ├── GetEmployeeDebtSummaryFeatureTest.php
│   │   ├── GetInventoryMovementSummaryFeatureTest.php
│   │   ├── GetInventoryStockValueReportDatasetFeatureTest.php
│   │   ├── GetOperationalExpenseReportDatasetFeatureTest.php
│   │   ├── GetOperationalExpenseSummaryFeatureTest.php
│   │   ├── GetOperationalProfitSummaryFeatureTest.php
│   │   ├── GetPayrollReportDatasetFeatureTest.php
│   │   ├── GetSupplierPayableReportDatasetFeatureTest.php
│   │   ├── GetSupplierPayableSummaryFeatureTest.php
│   │   ├── GetTransactionCashLedgerPerNoteFeatureTest.php
│   │   ├── GetTransactionReportDatasetFeatureTest.php
│   │   ├── GetTransactionSummaryPerNoteFeatureTest.php
│   │   ├── InventoryMovementBucketSplitFeatureTest.php
│   │   ├── InventoryMovementSummaryHardeningFeatureTest.php
│   │   ├── InventoryStockValueReportPageFeatureTest.php
│   │   ├── OperationalExpenseReportPageFeatureTest.php
│   │   ├── OperationalExpenseSummaryHardeningFeatureTest.php
│   │   ├── OperationalProfitReportPageFeatureTest.php
│   │   ├── OperationalProfitSummaryHardeningFeatureTest.php
│   │   ├── PayrollReportPageFeatureTest.php
│   │   ├── RefundedNoteCashReportingFallbackFeatureTest.php
│   │   ├── ReportingReadModelContractFeatureTest.php
│   │   ├── SeederNewFinanceInvariantFeatureTest.php
│   │   ├── SupplierPayableReferenceDateStatusFeatureTest.php
│   │   ├── SupplierPayableReportPageFeatureTest.php
│   │   ├── SupplierPayableSummaryHardeningFeatureTest.php
│   │   ├── TransactionCashLedgerPageFeatureTest.php
│   │   ├── TransactionCashLedgerReportingQueryFeatureTest.php
│   │   ├── TransactionReportingReconciliationFeatureTest.php
│   │   ├── TransactionReportPageFeatureTest.php
│   │   ├── TransactionSummaryPerNoteHardeningFeatureTest.php
│   │   └── TransactionSummaryReportingQueryFeatureTest.php
│   └── ReportingExports
│       ├── EmployeeDebtReportExcelExportFeatureTest.php
│       ├── EmployeeDebtReportPdfExportFeatureTest.php
│       ├── InventoryStockValueReportExcelExportFeatureTest.php
│       ├── InventoryStockValueReportPdfExportFeatureTest.php
│       ├── OperationalExpenseReportExcelExportFeatureTest.php
│       ├── OperationalExpenseReportPdfExportFeatureTest.php
│       ├── OperationalProfitReportExcelExportFeatureTest.php
│       ├── OperationalProfitReportPdfExportFeatureTest.php
│       ├── PayrollReportExcelExportFeatureTest.php
│       ├── PayrollReportPdfExportFeatureTest.php
│       ├── SupplierPayableReportExcelExportFeatureTest.php
│       ├── SupplierPayableReportPdfExportFeatureTest.php
│       ├── TransactionCashLedgerExcelExportFeatureTest.php
│       ├── TransactionCashLedgerPdfExportFeatureTest.php
│       ├── TransactionReportExcelExportFeatureTest.php
│       └── TransactionReportPdfExportFeatureTest.php
├── Pest.php
├── Support
│   ├── Architecture
│   ├── Procurement
│   │   └── SupplierPayableReminderFixtures.php
│   ├── PushNotification
│   │   ├── ExpiringPushNotificationSender.php
│   │   └── RecordingPushNotificationSender.php
│   ├── SeedsEditableProcurementHeaderPolicyMatrixFixture.php
│   ├── SeedsMinimalInventoryProductFixture.php
│   ├── SeedsMinimalNotePaymentFixture.php
│   ├── SeedsMinimalProcurementFixture.php
│   ├── SeedsMinimalProductFixture.php
│   ├── SeedsProcurementDuplicateIsolationMatrixFixture.php
│   ├── SeedsProcurementReceivePaymentPrecisionMatrixFixture.php
│   ├── SeedsProductLifecyclePayableHistoryMatrixFixture.php
│   ├── SeedsReceivedSupplierInvoiceRevisionMatrixFixture.php
│   ├── SeedsSupplierPayablePrecisionMatrixFixture.php
│   └── SeedsSupplierPaymentProofMatrixFixture.php
├── TestCase.php
└── Unit
    ├── Adapters
    │   └── In
    │       └── Http
    │           ├── Middleware
    │           │   └── IdentityAccess
    │           │       └── EnsureTransactionEntryAllowedTest.php
    │           └── Presenters
    │               ├── JsonPresenterTest.php
    │               └── Response
    │                   └── JsonResultResponderTest.php
    ├── Application
    │   ├── IdentityAccess
    │   │   ├── Policies
    │   │   │   └── TransactionEntryPolicyTest.php
    │   │   └── UseCases
    │   │       ├── DisableAdminTransactionCapabilityHandlerTest.php
    │   │       └── EnableAdminTransactionCapabilityHandlerTest.php
    │   ├── Note
    │   │   ├── Policies
    │   │   │   └── NotePaidStatusPolicyTest.php
    │   │   └── Services
    │   │       ├── NoteBillingProjectionRowMapperTest.php
    │   │       ├── NoteDetailRowMapperTest.php
    │   │       ├── NoteOperationalRowSettlementProjectorTest.php
    │   │       ├── NoteOperationalStatusEvaluatorTest.php
    │   │       ├── NoteOperationalStatusResolverTest.php
    │   │       ├── NoteRefundPaymentOptionsBuilderTest.php
    │   │       ├── RefundImpactPayloadBuilderTest.php
    │   │       └── SelectedRowsRefundBucketsBuilderTest.php
    │   ├── Payment
    │   │   └── Services
    │   │       ├── AllocatePaymentAcrossComponentsTest.php
    │   │       ├── AllocateRefundAcrossComponentsTest.php
    │   │       └── ResolveNotePayableComponentsTest.php
    │   ├── Reporting
    │   │   └── Services
    │   │       ├── CashChangeDenominationCalculatorTest.php
    │   │       └── TransactionPaymentStatusLabelResolverTest.php
    │   └── Shared
    │       └── DTO
    │           └── ResultTest.php
    └── Core
        ├── Note
        │   ├── Mutation
        │   │   ├── NoteMutationEventTest.php
        │   │   └── NoteMutationSnapshotTest.php
        │   ├── NoteDueDateTest.php
        │   ├── NoteMutationTest.php
        │   ├── NoteOperationalStateTransitionsTest.php
        │   ├── NoteTest.php
        │   └── WorkItem
        │       ├── ExternalPurchaseLineTest.php
        │       ├── ServiceDetailTest.php
        │       ├── StoreStockLineTest.php
        │       └── WorkItemTest.php
        ├── Payment
        │   ├── CustomerPayment
        │   │   └── CustomerPaymentTest.php
        │   ├── CustomerRefund
        │   │   └── CustomerRefundTest.php
        │   ├── PaymentAllocation
        │   │   └── PaymentAllocationTest.php
        │   ├── PaymentComponentAllocation
        │   │   ├── PaymentComponentAllocationTest.php
        │   │   └── PaymentComponentTypeTest.php
        │   ├── Policies
        │   │   └── PaymentAllocationPolicyTest.php
        │   └── RefundComponentAllocation
        │       └── RefundComponentAllocationTest.php
        └── Shared
            └── ValueObjects
                └── MoneyTest.php
tmp
├── oai-a1agg01-backup
│   └── app
│       └── Application
│           └── Payment
│               ├── DTO
│               └── Services
├── oai-a1mut01-backup
│   └── app
│       └── Application
│           └── Note
│               └── Services
├── oai-a1wire01-backup
│   ├── app
│   │   └── Adapters
│   │       └── In
│   │           └── Http
│   │               ├── Controllers
│   │               │   └── Note
│   │               │       └── RecordClosedNoteRefundController.php
│   │               └── Requests
│   │                   └── Note
│   │                       └── RecordClosedNoteRefundRequest.php
│   └── tests
│       └── Feature
│           ├── Note
│           │   └── RecordClosedNoteRefundControllerFeatureTest.php
│           └── Payment
│               └── RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php
├── oai-batch01-backup
│   └── resources
│       └── views
│           └── shared
│               └── notes
│                   ├── partials
│                   │   ├── header-summary.blade.php
│                   │   ├── line-workspace.blade.php
│                   │   └── payment-summary-actions.blade.php
│                   └── show.blade.php
├── oai-batch02-backup
│   ├── app
│   │   └── Application
│   │       └── Note
│   │           └── Services
│   │               ├── NoteDetailRowMapper.php
│   │               └── NoteDetailRowPresentationSupport.php
│   └── resources
│       └── views
│           └── cashier
│               └── notes
│                   └── partials
│                       └── note-rows-table.blade.php
├── oai-batch03-backup
│   ├── app
│   │   └── Application
│   │       └── Note
│   │           └── Services
│   │               └── NoteDetailRevisionTimelineBuilder.php
│   └── resources
│       └── views
│           └── shared
│               └── notes
│                   └── partials
│                       └── versioning-compact.blade.php
├── oai-batch04-backup
│   └── resources
│       └── views
│           └── cashier
│               └── notes
│                   └── partials
│                       └── refund-modal.blade.php
├── oai-batch05-backup
│   └── public
│       └── assets
│           └── static
│               └── js
│                   └── pages
│                       └── cashier-note-refund.js
├── oai-batch07-backup
│   └── app
│       └── Application
│           └── Note
│               └── Services
│                   ├── NoteDetailRevisionTimelineBuilder.php
│                   ├── NoteDetailRowPresentationSupport.php
│                   ├── NoteRevisionTimelineSummaryBuilder.php
│                   └── RefundImpactPayloadBuilder.php
├── oai-finalize-refunded-backup
│   └── app
│       └── Application
│           ├── Note
│           │   └── Services
│           └── Payment
│               └── Services
│                   └── RecordSelectedRowsRefundPlanTransaction.php
├── oai-fixlines01-backup
│   └── app
│       ├── Adapters
│       │   └── Out
│       │       └── Note
│       │           └── DatabaseNoteReaderAdapter.php
│       └── Application
│           └── Payment
│               └── DTO
│                   └── SelectedRowsRefundPlan.php
├── oai-l1l2-backup
├── oai-stabilize01-backup
│   ├── app
│   │   └── Application
│   │       └── Note
│   │           └── Services
│   │               └── SelectedRowsRefundBucketsBuilder.php
│   └── tests
│       ├── Feature
│       │   └── Payment
│       │       └── AllocateCustomerPaymentFeatureTest.php
│       └── Unit
│           └── Application
│               └── Note
│                   └── Services
│                       ├── NoteDetailRowMapperTest.php
│                       └── RefundImpactPayloadBuilderTest.php
├── oai-t1hard01-backup
│   └── app
│       └── Adapters
│           └── Out
│               └── Note
│                   └── DatabaseNoteReaderAdapter.php
├── oai-t1hard-invariant-fix
│   └── app
│       └── Adapters
│           └── Out
│               └── Note
│                   └── Mappers
│                       └── NoteMapper.php
└── tree-file.md

469 directories, 1982 files
[asyraf@arch app]$ 
