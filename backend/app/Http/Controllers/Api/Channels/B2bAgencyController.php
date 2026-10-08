<?php

namespace App\Http\Controllers\Api\Channels;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\B2bAgency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class B2bAgencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = B2bAgency::with('accountManager:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('tax_id', 'like', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('name')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'commercial_name' => 'nullable|string|max:160',
            'tax_id' => 'required|string|max:30',
            'country' => 'required|string|max:80',
            'city' => 'nullable|string|max:80',
            'contact_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'commission_pct' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|in:USD,PEN',
            'credit_limit' => 'required|numeric|min:0',
            'credit_days' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');
        $code = SequenceService::nextCode($tenantId, 'b2b_agency');

        $agency = B2bAgency::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'code' => $code,
            'credit_used' => 0.00,
            'status' => 'activo',
            'account_manager_id' => $request->user()->id,
        ]);

        AuditLogger::log('AgenciaB2B', $agency->code, 'crear', null, $agency->name, true);

        return response()->json(['data' => $agency], 201);
    }

    public function show(string $id): JsonResponse
    {
        $agency = B2bAgency::with(['accountManager', 'users'])->findOrFail($id);
        return response()->json([
            'data' => $agency,
            'available_credit' => $agency->available_credit,
        ]);
    }
}