<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Domain\Shared\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessagingController extends Controller
{
    public function getTemplates(Request $request): JsonResponse
    {
        $query = MessageTemplate::query();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('name')->get()
        ]);
    }

    public function getMessages(Request $request): JsonResponse
    {
        $query = MessageLog::with('template:id,code,name');

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    /**
     * Despacho manual o simulado utilizando plantilla y parámetros
     */
    public function dispatchMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_code'  => 'required|string|exists:msg_templates,code',
            'recipient_to'   => 'required|string|max:160',
            'recipient_name' => 'nullable|string|max:160',
            'params'         => 'required|array',
        ]);

        $tenantId = app('current_tenant_id');
        $template = MessageTemplate::where('tenant_id', $tenantId)
            ->where('code', $validated['template_code'])
            ->firstOrFail();

        // Reemplazo dinámico de variables en cuerpo y asunto
        $content = $template->body_template;
        $subject = $template->subject ?? '';

        foreach ($validated['params'] as $key => $val) {
            $content = str_replace("{{" . $key . "}}", (string)$val, $content);
            if ($subject) {
                $subject = str_replace("{{" . $key . "}}", (string)$val, $subject);
            }
        }

        // Simulación de envío con ID de tracking
        $externalId = 'MSG-' . strtoupper(Str::random(12));

        $msg = MessageLog::create([
            'tenant_id'      => $tenantId,
            'template_id'    => $template->id,
            'channel'        => $template->channel,
            'recipient_to'   => $validated['recipient_to'],
            'recipient_name' => $validated['recipient_name'] ?? null,
            'subject'        => $subject ?: null,
            'content'        => $content,
            'status'         => 'enviado',
            'external_id'    => $externalId,
            'sent_at'        => now(),
        ]);

        AuditLogger::log('Mensajeria', $msg->id, 'despachar_mensaje', null, "Canal {$msg->channel} hacia {$msg->recipient_to}", true);

        return response()->json([
            'message' => 'Mensaje despachado correctamente.',
            'data' => $msg
        ], 201);
    }
}