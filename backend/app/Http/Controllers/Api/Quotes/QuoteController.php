<?php

namespace App\Http\Controllers\Api\Quotes;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Quote::with(['client:id,name,code', 'owner:id,name,email', 'opportunity:id,code,title']);

        if ($user->hasRole('sales')) {
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|uuid|exists:crm_clients,id',
            'opportunity_id' => 'nullable|uuid|exists:crm_opportunities,id',
            'title' => 'required|string|max:200',
            'valid_until' => 'required|date|after_or_equal:today',
            'currency' => 'required|string|in:PEN,USD',
            'pax' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|uuid|exists:catalog_products,id',
            'items.*.rate_id' => 'nullable|uuid|exists:catalog_rates,id',
            'items.*.concept' => 'required|string|max:200',
            'items.*.service_date' => 'nullable|date',
            'items.*.pax' => 'required|integer|min:1',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            $ref = SequenceService::nextCode($tenantId, 'quote');

            $subtotal = 0.00;
            $costTotal = 0.00;

            foreach ($validated['items'] as $item) {
                $linePrice = $item['unit_price'] * $item['quantity'] * $item['pax'];
                $lineCost = $item['unit_cost'] * $item['quantity'] * $item['pax'];
                $subtotal += $linePrice;
                $costTotal += $lineCost;
            }

            $discount = 0.00;
            $tax = 0.00; // En tours receptivos el IGV suele ser 0% si aplica exportación
            $total = $subtotal - $discount + $tax;
            $profit = $total - $costTotal;
            $marginPct = $total > 0 ? ($profit / $total) * 100 : 0;

            $quote = Quote::create([
                'tenant_id' => $tenantId,
                'code' => $ref,
                'client_id' => $validated['client_id'],
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'owner_id' => $request->user()->id,
                'title' => $validated['title'],
                'status' => 'borrador',
                'currency' => $validated['currency'],
                'valid_until' => $validated['valid_until'],
                'pax' => $validated['pax'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'cost_total' => $costTotal,
                'profit' => $profit,
                'margin_pct' => $marginPct,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $linePrice = $item['unit_price'] * $item['quantity'] * $item['pax'];
                $lineCost = $item['unit_cost'] * $item['quantity'] * $item['pax'];

                $snapshot = null;
                if (!empty($item['product_id'])) {
                    $prod = Product::find($item['product_id']);
                    $rate = !empty($item['rate_id']) ? Rate::find($item['rate_id']) : null;
                    $snapshot = [
                        'product_name' => $prod?->name,
                        'product_sku' => $prod?->sku,
                        'rate_type' => $rate?->rate_type,
                        'captured_at' => now()->toIso8601String(),
                    ];
                }

                QuoteItem::create([
                    'tenant_id' => $tenantId,
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'rate_id' => $item['rate_id'] ?? null,
                    'concept' => $item['concept'],
                    'service_date' => $item['service_date'] ?? null,
                    'pax' => $item['pax'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'unit_price' => $item['unit_price'],
                    'total_cost' => $lineCost,
                    'total_price' => $linePrice,
                    'snapshot' => $snapshot,
                    'sort_order' => $index + 1,
                ]);
            }

            // Si proviene de una oportunidad, sincronizar etapa a 'cotizacion'
            if (!empty($validated['opportunity_id'])) {
                $opp = Opportunity::find($validated['opportunity_id']);
                if ($opp && in_array($opp->stage, ['calificacion', 'descubrimiento'])) {
                    $opp->update([
                        'stage' => 'cotizacion',
                        'probability' => 55,
                    ]);
                    AuditLogger::log('Oportunidad', $opp->code, 'cambio_etapa', 'descubrimiento', 'cotizacion');
                }
            }

            AuditLogger::log('Cotizacion', $quote->code, 'crear', null, $quote->title);

            return response()->json(['data' => $quote->load('items')], 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $quote = Quote::with(['client', 'opportunity', 'owner', 'items'])->findOrFail($id);
        return response()->json(['data' => $quote]);
    }
}