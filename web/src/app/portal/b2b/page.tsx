'use client';

import { Suspense, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';

function B2bPortalContent() {
  const router = useRouter();
  const [profile, setProfile] = useState<any>(null);
  const [catalog, setCatalog] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Formulario de emisión rápida B2B
  const [selectedProduct, setSelectedProduct] = useState<any | null>(null);
  const [selectedRateId, setSelectedRateId] = useState('');
  const [travelDate, setTravelDate] = useState('2026-11-25');
  const [agencyRef, setAgencyRef] = useState('');
  const [paxName, setPaxName] = useState('');
  const [paxDoc, setPaxDoc] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function loadB2bData() {
    setLoading(true);
    try {
      const [resProfile, resCatalog] = await Promise.all([
        fetchApi('/portal/b2b/profile'),
        fetchApi('/portal/b2b/catalog'),
      ]);
      setProfile(resProfile);
      setCatalog(resCatalog.data || []);
      if (resCatalog.data && resCatalog.data.length > 0) {
        setSelectedProduct(resCatalog.data[0]);
        if (resCatalog.data[0].rates?.length > 0) {
          setSelectedRateId(resCatalog.data[0].rates[0].id);
        }
      }
    } catch (err) {
      console.error('Error cargando portal B2B:', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadB2bData();
  }, []);

  const handleBookB2b = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct || !selectedRateId) return;

    setSubmitting(true);
    try {
      const res = await fetchApi('/portal/b2b/book', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          product_id: selectedProduct.id,
          rate_id: selectedRateId,
          travel_date: travelDate,
          agency_reference: agencyRef,
          passengers: [
            {
              first_name: paxName.split(' ')[0] || 'Pasajero',
              last_name: paxName.split(' ')[1] || 'B2B',
              doc_type: 'PASAPORTE',
              doc_number: paxDoc || 'P9928123',
            },
          ],
        }),
      });

      alert(`Reserva B2B creada con éxito: ${res.data.reservation_code}`);
      setPaxName('');
      setPaxDoc('');
      setAgencyRef('');
      await loadB2bData();
    } catch (err: any) {
      alert(err.message || 'Error emitiendo reserva B2B');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-10 px-6">
      <div className="max-w-6xl mx-auto space-y-8">
        <header className="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-800 pb-5">
          <div>
            <span className="text-xs uppercase font-bold tracking-widest text-amber-400">
              Canal B2B · Agencias Aliadas
            </span>
            <h1 className="text-3xl font-black text-white mt-1">
              {profile?.agency ? profile.agency.name : 'Portal Mayorista'}
            </h1>
            <p className="text-xs text-slate-400">
              Código: <strong className="text-slate-200">{profile?.agency?.code || 'AG-B2B'}</strong> · RUC/ID: {profile?.agency?.tax_id || '--'}
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
          <div className="text-center py-16 text-slate-400">Cargando datos corporativos...</div>
        ) : (
          <>
            {/* Tarjetas de Crédito B2B */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <span className="text-xs uppercase font-bold text-slate-400">Línea de Crédito Total</span>
                <p className="text-3xl font-black text-white mt-2">
                  ${profile?.agency?.credit_limit?.toFixed(2) || '0.00'}
                </p>
                <span className="text-xs text-slate-500 mt-1 block">Término: 15 días fecha factura</span>
              </div>

              <div className="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <span className="text-xs uppercase font-bold text-slate-400">Crédito Consumido (CxC)</span>
                <p className="text-3xl font-black text-rose-400 mt-2">
                  ${profile?.agency?.credit_used?.toFixed(2) || '0.00'}
                </p>
                <span className="text-xs text-slate-500 mt-1 block">Saldos pendientes de pago</span>
              </div>

              <div className="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <span className="text-xs uppercase font-bold text-slate-400">Crédito Disponible</span>
                <p className="text-3xl font-black text-emerald-400 mt-2">
                  \${profile?.agency?.credit_available?.toFixed(2) || '0.00'}
                </p>
                <span className="text-xs text-slate-500 mt-1 block">Cupo disponible para emisión inmediata</span>
              </div>
            </div>

            {/* Módulo de Emisión Rápida */}
            <div className="rounded-2xl bg-slate-900 border border-slate-800 p-6 space-y-6">
              <h3 className="text-xl font-bold text-white border-b border-slate-800 pb-3">
                Emisión de Reserva Mayorista
              </h3>

              <form onSubmit={handleBookB2b} className="space-y-4 text-sm">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Paquete / Tour</label>
                    <select
                      value={selectedProduct?.id || ''}
                      onChange={(e) => {
                        const prod = catalog.find((p) => p.id === e.target.value);
                        setSelectedProduct(prod);
                        if (prod?.rates?.length > 0) setSelectedRateId(prod.rates[0].id);
                      }}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    >
                      {catalog.map((item) => (
                        <option key={item.id} value={item.id}>{item.name}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Tarifa Mayorista</label>
                    <select
                      value={selectedRateId}
                      onChange={(e) => setSelectedRateId(e.target.value)}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    >
                      {selectedProduct?.rates?.map((r: any) => (
                        <option key={r.id} value={r.id}>
                          {r.name || 'B2B'} — {r.currency} ${parseFloat(r.price).toFixed(2)}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Fecha de Salida</label>
                    <input
                      type="date"
                      value={travelDate}
                      onChange={(e) => setTravelDate(e.target.value)}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Localizador Interno de Agencia</label>
                    <input
                      type="text"
                      placeholder="Ej. FILE-AG-9921"
                      value={agencyRef}
                      onChange={(e) => setAgencyRef(e.target.value)}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Nombre Pasajero Titular</label>
                    <input
                      type="text"
                      required
                      placeholder="Ej. John Smith"
                      value={paxName}
                      onChange={(e) => setPaxName(e.target.value)}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-300 font-medium mb-1">Pasaporte / Documento</label>
                    <input
                      type="text"
                      required
                      placeholder="Ej. US-88219401"
                      value={paxDoc}
                      onChange={(e) => setPaxDoc(e.target.value)}
                      className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                    />
                  </div>
                </div>

                <div className="flex justify-end pt-3">
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm transition shadow-lg disabled:opacity-50"
                  >
                    {submitting ? 'Emitiendo Reserva...' : 'Emitir contra Crédito B2B'}
                  </button>
                </div>
              </form>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default function B2bPortalPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-slate-950 text-slate-400 p-8 text-center">Cargando canal B2B...</div>}>
      <B2bPortalContent />
    </Suspense>
  );
}