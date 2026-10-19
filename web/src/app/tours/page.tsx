'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { fetchApi } from '@/lib/api';

export default function ToursCatalogPage() {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadCatalog() {
      try {
        const res = await fetchApi('/public/products', {
          headers: { 'X-Tenant-Slug': 'demo-travel' }
        });
        setProducts(res.data || []);
      } catch (err) {
        console.error('Error cargando catálogo:', err);
      } finally {
        setLoading(false);
      }
    }
    loadCatalog();
  }, []);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-12 px-6">
      <div className="max-w-6xl mx-auto space-y-10">
        <header className="text-center space-y-3">
          <span className="text-xs font-bold uppercase tracking-widest text-amber-400">
            Experiencias Inolvidables
          </span>
          <h1 className="text-4xl md:text-5xl font-black text-white">
            Explora Nuestros Paquetes & Tours
          </h1>
          <p className="text-slate-400 max-w-2xl mx-auto text-sm md:text-base">
            Descubre los mejores destinos arqueológicos y naturales del Perú con reservas inmediatas garantizadas.
          </p>
        </header>

        {loading ? (
          <div className="text-center py-20 text-slate-400">Cargando tours disponibles...</div>
        ) : products.length === 0 ? (
          <div className="text-center py-16 bg-slate-900 border border-slate-800 rounded-2xl text-slate-400">
            No hay paquetes publicados en este momento.
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {products.map((item) => {
              const basePrice = item.rates && item.rates.length > 0 
                ? parseFloat(item.rates[0].price).toFixed(2)
                : '0.00';
              const currency = item.rates && item.rates.length > 0 
                ? item.rates[0].currency 
                : 'USD';

              return (
                <div
                  key={item.id}
                  className="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden flex flex-col justify-between hover:border-amber-400/50 transition duration-300 group shadow-lg"
                >
                  <div className="p-6 space-y-4">
                    <div className="flex justify-between items-start gap-2">
                      <span className="text-xs font-mono uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2.5 py-1 rounded-md font-semibold">
                        {item.type || 'Paquete'}
                      </span>
                      <span className="text-xs text-slate-500 font-medium">
                        Ref: {item.ref}
                      </span>
                    </div>

                    <h3 className="text-xl font-bold text-white group-hover:text-amber-400 transition">
                      {item.name}
                    </h3>

                    <p className="text-slate-400 text-sm line-clamp-3">
                      {item.description || 'Disfruta de una experiencia completa con transporte, boletos de ingreso y guía profesional.'}
                    </p>
                  </div>

                  <div className="p-6 pt-0 border-t border-slate-800/80 mt-4 flex items-center justify-between">
                    <div>
                      <span className="text-xs text-slate-500 block">Desde</span>
                      <span className="text-2xl font-black text-amber-400">
                        {currency} ${basePrice}
                      </span>
                    </div>

                    <Link
                      href={`/tours/${item.id}`}
                      className="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm transition shadow-md"
                    >
                      Ver Detalle
                    </Link>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}