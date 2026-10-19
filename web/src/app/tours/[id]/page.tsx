'use client';

import { Suspense, useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { fetchApi } from '@/lib/api';

function TourDetailContent() {
  const router = useRouter();
  const params = useParams();
  const tourId = params?.id as string;

  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [selectedRateId, setSelectedRateId] = useState<string>('');
  const [travelDate, setTravelDate] = useState<string>('2026-11-25');
  const [paxCount, setPaxCount] = useState<number>(1);

  useEffect(() => {
    if (!tourId) return;

    async function loadDetail() {
      try {
        const res = await fetchApi(`/public/products/${tourId}`, {
          headers: { 'X-Tenant-Slug': 'demo-travel' },
        });
        
        const productData = res.data;
        const availableRates = productData?.rates || res.rates || [];
        
        // Asignamos las tarifas dentro del objeto producto
        if (productData) {
          productData.rates = availableRates;
        }

        setProduct(productData);

        if (availableRates.length > 0) {
          setSelectedRateId(availableRates[0].id);
        }
      } catch (err) {
        console.error('Error cargando detalle:', err);
      } finally {
        setLoading(false);
      }
    }

    loadDetail();
  }, [tourId]);

  const handleProceedToCheckout = () => {
    if (!selectedRateId || !product) return;
    router.push(
      `/checkout?product_id=${product.id}&rate_id=${selectedRateId}&travel_date=${travelDate}&pax=${paxCount}`
    );
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-950 text-slate-400 flex items-center justify-center">
        Cargando detalles del tour...
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-slate-950 text-rose-400 flex items-center justify-center">
        Tour no encontrado.
      </div>
    );
  }

  const activeRate = product.rates?.find((r: any) => r.id === selectedRateId);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-12 px-6">
      <div className="max-w-5xl mx-auto space-y-10">
        <div className="space-y-3">
          <div className="flex items-center gap-3">
            <span className="text-xs font-mono uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-1 rounded-md font-semibold">
              {product.type || 'Experiencia Turística'}
            </span>
            <span className="text-xs text-slate-500 font-mono">Ref: {product.ref}</span>
          </div>
          <h1 className="text-4xl md:text-5xl font-black text-white">{product.name}</h1>
          <p className="text-slate-400 text-base max-w-3xl leading-relaxed">
            {product.description || 'Itinerario detallado y servicios garantizados de alta calidad.'}
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
              <h3 className="text-lg font-bold text-white border-b border-slate-800 pb-3">
                Servicios Operativos Incluidos
              </h3>
              {product.services && product.services.length > 0 ? (
                <ul className="space-y-3 text-sm text-slate-300">
                  {product.services.map((svc: any) => (
                    <li key={svc.id} className="flex items-start gap-3">
                      <span className="text-emerald-400 font-bold">✓</span>
                      <div>
                        <strong className="text-white">{svc.name}</strong>
                        {svc.provider && (
                          <span className="text-xs text-slate-500 block">
                            Operado por: {svc.provider.name}
                          </span>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-sm text-slate-500">Servicios estándar incluidos en paquete.</p>
              )}
            </div>
          </div>

          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 h-fit shadow-xl">
            <h3 className="text-xl font-bold text-amber-400 border-b border-slate-800 pb-3">
              Configura tu Reserva
            </h3>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase text-slate-400">Tipo de Tarifa</label>
              <select
                value={selectedRateId}
                onChange={(e) => setSelectedRateId(e.target.value)}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:border-amber-400 focus:outline-none"
              >
                {product.rates?.map((rate: any) => (
                  <option key={rate.id} value={rate.id}>
                    {rate.name || 'Tarifa Estándar'} — {rate.currency} ${parseFloat(rate.price).toFixed(2)}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase text-slate-400">Fecha de Viaje</label>
              <input
                type="date"
                value={travelDate}
                onChange={(e) => setTravelDate(e.target.value)}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:border-amber-400 focus:outline-none"
              />
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold uppercase text-slate-400">Número de Pasajeros</label>
              <input
                type="number"
                min="1"
                max="20"
                value={paxCount}
                onChange={(e) => setPaxCount(Math.max(1, parseInt(e.target.value) || 1))}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:border-amber-400 focus:outline-none"
              />
            </div>

            {activeRate && (
              <div className="pt-4 border-t border-slate-800 flex justify-between items-center">
                <span className="text-sm text-slate-400">Inversión Total:</span>
                <span className="text-2xl font-black text-emerald-400">
                  {activeRate.currency} ${(parseFloat(activeRate.price) * paxCount).toFixed(2)}
                </span>
              </div>
            )}

            <button
              onClick={handleProceedToCheckout}
              disabled={!selectedRateId}
              className="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold py-3.5 rounded-xl text-base transition shadow-lg disabled:opacity-50"
            >
              Completar Reserva
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function TourDetailPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-slate-950 text-slate-400 flex items-center justify-center">
          Cargando detalles del tour...
        </div>
      }
    >
      <TourDetailContent />
    </Suspense>
  );
}