'use client';

import { Suspense, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';

function ProviderPortalContent() {
  const router = useRouter();
  const [provider, setProvider] = useState<any>(null);
  const [services, setServices] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Modal para confirmar
  const [selectedService, setSelectedService] = useState<any | null>(null);
  const [confirmationCode, setConfirmationCode] = useState('');
  const [notes, setNotes] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function loadServices() {
    setLoading(true);
    try {
      const res = await fetchApi('/portal/provider/services');
      setProvider(res.provider);
      setServices(res.data || []);
    } catch (err: any) {
      console.error('Error cargando extranet:', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadServices();
  }, []);

  const handleConfirmService = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedService) return;

    setSubmitting(true);
    try {
      await fetchApi(`/portal/provider/services/${selectedService.id}/confirm`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          confirmation_code: confirmationCode,
          notes,
        }),
      });

      setSelectedService(null);
      setConfirmationCode('');
      setNotes('');
      await loadServices();
    } catch (err: any) {
      alert(err.message || 'Error confirmando el servicio');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-10 px-6">
      <div className="max-w-5xl mx-auto space-y-8">
        {/* Cabecera */}
        <header className="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-800 pb-5">
          <div>
            <span className="text-xs uppercase font-bold tracking-widest text-amber-400">
              Extranet para Proveedores
            </span>
            <h1 className="text-3xl font-black text-white mt-1">
              {provider ? provider.name : 'Portal de Operaciones'}
            </h1>
            <p className="text-xs text-slate-400">
              Ref: {provider?.ref || 'PRV'} · Confirmación de despachos y reservas
            </p>
          </div>

          <button
            onClick={() => router.push('/dashboard')}
            className="px-4 py-2 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-xs font-semibold rounded-lg w-fit"
          >
            ← Volver al Backoffice
          </button>
        </header>

        {loading ? (
          <div className="text-center py-16 text-slate-400">Cargando servicios asignados...</div>
        ) : services.length === 0 ? (
          <div className="rounded-2xl bg-slate-900 border border-slate-800 p-8 text-center text-slate-400">
            No tienes servicios asignados pendientes.
          </div>
        ) : (
          <div className="space-y-4">
            {services.map((svc) => (
              <div
                key={svc.id}
                className="rounded-2xl bg-slate-900 border border-slate-800 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-slate-700 transition"
              >
                <div className="space-y-1">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-bold text-white">{svc.name}</span>
                    <span className="font-mono text-xs text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded">
                      {svc.voucher_code || 'S/V'}
                    </span>
                  </div>
                  <div className="text-xs text-slate-400">
                    Fecha: <strong className="text-slate-200">{new Date(svc.date).toLocaleDateString()}</strong> · 
                    Hora: <strong className="text-slate-200">{svc.time ? svc.time.substring(0, 5) : '--:--'}</strong> · 
                    Pax: <strong className="text-slate-200">{svc.pax}</strong>
                  </div>
                  <div className="text-xs text-slate-400">
                    Punto de encuentro: <span className="text-slate-300">{svc.meeting_point || 'Por definir'}</span>
                  </div>

                  {svc.provider_confirmation_code && (
                    <div className="pt-2 text-xs">
                      <span className="text-slate-500">Confirmación registrada: </span>
                      <strong className="text-emerald-400 font-mono">{svc.provider_confirmation_code}</strong>
                    </div>
                  )}
                </div>

                <div className="flex items-center gap-3 w-full md:w-auto justify-end">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-bold uppercase ${
                      svc.status === 'confirmado'
                        ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                        : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
                    }`}
                  >
                    {svc.status}
                  </span>

                  {svc.status !== 'confirmado' && (
                    <button
                      onClick={() => {
                        setSelectedService(svc);
                        setConfirmationCode('');
                      }}
                      className="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs rounded-xl transition"
                    >
                      Confirmar Cupo
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Modal de confirmación */}
        {selectedService && (
          <div className="fixed inset-0 bg-black/70 flex items-center justify-center p-4 z-50">
            <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4">
              <div className="flex justify-between items-center border-b border-slate-800 pb-3">
                <h3 className="text-lg font-bold text-white">Confirmar Disponibilidad</h3>
                <button
                  onClick={() => setSelectedService(null)}
                  className="text-slate-400 hover:text-white"
                >
                  ✕
                </button>
              </div>

              <form onSubmit={handleConfirmService} className="space-y-4 text-sm">
                <div>
                  <span className="text-slate-400 text-xs uppercase block">Servicio:</span>
                  <p className="font-semibold text-white">{selectedService.name}</p>
                </div>

                <div>
                  <label className="block text-slate-300 font-medium mb-1">
                    Código de Confirmación / Localizador
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Ej. PR-EXP-99215"
                    value={confirmationCode}
                    onChange={(e) => setConfirmationCode(e.target.value)}
                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-slate-300 font-medium mb-1">
                    Notas adicionales (Vagón, Asientos, Conductor)
                  </label>
                  <textarea
                    rows={3}
                    placeholder="Ej. Vagón A, Asientos 12 y 14 asignados"
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                  />
                </div>

                <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                  <button
                    type="button"
                    onClick={() => setSelectedService(null)}
                    className="px-4 py-2 text-slate-400 hover:text-white text-xs font-semibold"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs rounded-xl transition disabled:opacity-50"
                  >
                    {submitting ? 'Guardando...' : 'Confirmar Servicio'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default function ProviderPortalPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-slate-950 text-slate-400 flex items-center justify-center">
          Cargando portal...
        </div>
      }
    >
      <ProviderPortalContent />
    </Suspense>
  );
}