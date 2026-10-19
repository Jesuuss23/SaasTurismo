<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Catalog\DestinationController;
use App\Http\Controllers\Api\Catalog\ProviderController;
use App\Http\Controllers\Api\Catalog\ServiceController;
use App\Http\Controllers\Api\Catalog\ProductController;
use App\Http\Controllers\Api\Crm\LeadController;
use App\Http\Middleware\SetPostgresTenant;
use App\Http\Controllers\Api\Crm\OpportunityController;
use App\Http\Controllers\Api\Quotes\QuoteController;
use App\Http\Controllers\Api\Bookings\ReservationController;
use App\Http\Controllers\Api\Operations\OperationsController;
use App\Http\Controllers\Api\Finance\FinanceController;
use App\Http\Controllers\Api\Crm\ClaimController;
use App\Http\Controllers\Api\Collaboration\ApprovalController;
use App\Http\Controllers\Api\Catalog\AllotmentController;
use App\Http\Controllers\Api\Channels\B2bAgencyController;
use App\Http\Controllers\Api\Providers\ProviderPortalController;
use App\Http\Controllers\Api\Public\PublicCatalogController;
use App\Http\Controllers\Api\Public\PaymentWebhookController;
use App\Http\Controllers\Api\Bookings\TravelDocumentController;
use App\Http\Controllers\Api\Finance\PayableController;
use App\Http\Controllers\Api\Messaging\MessagingController;
use App\Http\Controllers\Api\Analytics\AnalyticsController;
use App\Http\Controllers\Api\Portal\B2bPortalController;
use App\Models\Rate;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {


    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::prefix('public')->middleware([SetPostgresTenant::class])->group(function () {
        Route::get('/products', [PublicCatalogController::class, 'getProducts']);
        Route::get('/products/{idOrSlug}', [PublicCatalogController::class, 'getProductDetail']);
        Route::post('/checkout', [PublicCatalogController::class, 'checkout']);

        Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);

        Route::get('/reservations/{code}/voucher', [TravelDocumentController::class, 'getVoucherData']);
        Route::get('/reservations/{code}/voucher-view', [TravelDocumentController::class, 'renderVoucherHtml']);
    });
    
    Route::middleware(['auth:sanctum', SetPostgresTenant::class])->group(function () {


        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // =========================
        // CATALOG
        // =========================

        Route::prefix('catalog')->group(function () {

            // Destinos
            Route::get('/destinations', [DestinationController::class, 'index']);
            Route::post('/destinations', [DestinationController::class, 'store']);
            Route::get('/destinations/{id}', [DestinationController::class, 'show']);

            // Proveedores
            Route::get('/providers', [ProviderController::class, 'index']);
            Route::post('/providers', [ProviderController::class, 'store']);
            Route::get('/providers/{id}', [ProviderController::class, 'show']);

            // Servicios
            Route::get('/services', [ServiceController::class, 'index']);
            Route::post('/services', [ServiceController::class, 'store']);

            // Productos
            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::get('/products/{id}', [ProductController::class, 'show']);

            // Allotments
            Route::get('/allotments', [AllotmentController::class, 'index']);
            Route::post('/allotments', [AllotmentController::class, 'store']);
            Route::get('/allotments/{id}', [AllotmentController::class, 'show']);

            Route::get('/products/{id}/rates', function ($id) {
                return response()->json([
                    'data' => Rate::where('product_id', $id)
                        ->with('season')
                        ->get()
                ]);
            });
        });

        Route::prefix('crm')->group(function () {

            Route::get('/leads', [LeadController::class, 'index']);
            Route::post('/leads', [LeadController::class, 'store']);
            Route::get('/leads/{id}', [LeadController::class, 'show']);

            Route::get('/opportunities', [OpportunityController::class, 'index']);
            Route::post('/opportunities', [OpportunityController::class, 'store']);
            Route::get('/opportunities/{id}', [OpportunityController::class, 'show']);

            Route::post('/claims/public', [ClaimController::class, 'storePublic'])->middleware(App\Http\Middleware\SetPostgresTenant::class);
            Route::middleware(['auth:sanctum', App\Http\Middleware\SetPostgresTenant::class])->group(function () {
                Route::get('/claims', [ClaimController::class, 'index']);
            });

        });

        Route::prefix('quotes')->group(function () {
            Route::get('/', [QuoteController::class, 'index']);
            Route::post('/', [QuoteController::class, 'store']);
            Route::get('/{id}', [QuoteController::class, 'show']);
        });
        Route::prefix('reservations')->group(function () {
            Route::get('/', [ReservationController::class, 'index']);
            Route::post('/from-quote', [ReservationController::class, 'storeFromQuote']);
            Route::get('/{id}', [ReservationController::class, 'show']);

            Route::get('/{idOrCode}/voucher-data', [TravelDocumentController::class, 'getVoucherData']);
            Route::get('/{idOrCode}/voucher-html', [TravelDocumentController::class, 'renderVoucherHtml']);
        });
        Route::prefix('operations')->group(function () {
            Route::get('/orders', [OperationsController::class, 'index']);
            Route::get('/orders/{id}', [OperationsController::class, 'show']);
            Route::post('/services/{id}/voucher', [OperationsController::class, 'generateVoucher']);
        });
        Route::prefix('finance')->group(function () {
            Route::get('/receivables', [FinanceController::class, 'getReceivables']);
            Route::post('/payments', [FinanceController::class, 'registerPayment']);
            Route::get('/payments', [FinanceController::class, 'getPayments']);

            Route::get('/payables', [PayableController::class, 'index']);
            Route::post('/payables/from-service/{serviceId}', [PayableController::class, 'storeFromService']);
            Route::post('/payables/{payableId}/disbursements', [PayableController::class, 'registerDisbursement']);
        });
        Route::prefix('approvals')->group(function () {
            Route::get('/', [ApprovalController::class, 'index']);
            Route::post('/{id}/resolve', [ApprovalController::class, 'resolve']);
        });
        Route::prefix('channels/b2b')->group(function () {
            Route::get('/agencies', [B2bAgencyController::class, 'index']);
            Route::post('/agencies', [B2bAgencyController::class, 'store']);
            Route::get('/agencies/{id}', [B2bAgencyController::class, 'show']);
        });
        Route::prefix('providers/portal')->group(function () {
            Route::get('/{providerId}/services', [ProviderPortalController::class, 'index']);
            Route::post('/services/{serviceId}/confirm', [ProviderPortalController::class, 'confirmService']);
            Route::post('/services/{serviceId}/reject', [ProviderPortalController::class, 'rejectService']);
        });

        Route::prefix('messaging')->group(function () {
            Route::get('/templates', [MessagingController::class, 'getTemplates']);
            Route::get('/logs', [MessagingController::class, 'getMessages']);
            Route::post('/dispatch', [MessagingController::class, 'dispatchMessage']);
        });
        
        Route::prefix('analytics')->group(function () {
            Route::get('/summary', [AnalyticsController::class, 'getExecutiveSummary']);
        });
        Route::prefix('portal/provider')->group(function () {
            Route::get('/services', [ProviderPortalController::class, 'getAssignedServices']);
            Route::post('/services/{id}/confirm', [ProviderPortalController::class, 'confirmService']);
            Route::post('/services/{id}/reject', [ProviderPortalController::class, 'rejectService']);
        });

        Route::prefix('portal/b2b')->group(function () {
            Route::get('/profile', [B2bPortalController::class, 'getAgencyProfile']);
            Route::get('/catalog', [B2bPortalController::class, 'getCatalog']);
            Route::post('/book', [B2bPortalController::class, 'book']);
        });




    });

});