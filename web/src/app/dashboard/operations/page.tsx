'use client';

import { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';

export default function OperationsPage() {
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    async function loadOrders() {
      try {
        const res = await fetchApi('/operations/orders');
        setOrders(res.data?.data || []);
      } catch (err) {
        console.error('Error cargando órdenes de operación:', err);
      } finally {
        setLoading(false);
      }
    }
    loadOrders();
  }, []);

  const filtered = orders.filter((o) =>
    o.code?.toLowerCase().includes(search.toLowerCase()) ||
    o.reservation?.code?.toLowerCase().includes(search.toLowerCase()) ||
    o.services?.some((s: any) => s.name?.toLowerCase().includes(search.toLowerCase()))
  );

  return (
    <div className="space-y-6">
      <header className="border-b border-slate-800 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold text-amber-400">Control Operativo</h1>
          <p className="text-sm text-slate-400 mt-1">
            Despacho diario, servicios contratados y confirmaciones de proveedores
          </p>
        </div>

        <input
          type="text"
          placeholder="Buscar orden, reserva o servicio..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white focus:border-amber-400 focus:outline-none w-full md:w-80"
        />
      </header>

      {loading ? (
        <div className="text-slate-400 py-12 text-center">Cargando órdenes operativas...</div>
      ) : filtered.length === 0 ? (
        <div className="rounded-xl bg-slate-900 border border-slate-800 p-8 text-center text-slate-400">
          No hay órdenes operativas registradas para los criterios seleccionados.
        </div>
      ) : (
        <div className="space-y-4">
          {filtered.map((order) => (
            <div
              key={order.id}
              className="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4 hover:border-slate-700 transition"
            >
              {/* Cabecera de la orden */}
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-slate-800 pb-4">
                <div className="flex items-center gap-3">
                  <span className="font-mono text-lg font-bold text-amber-400">{order.code}</span>
                  <span className="text-xs text-slate-500">|</span>
                  <span className="text-sm text-slate-300">
                    Reserva: <strong className="text-white">{order.reservation?.code}</strong>
                  </span>
                  <span className="text-xs text-slate-500">|</span>
                  <span className="text-sm text-slate-300">
                    Fecha Salida: {new Date(order.execution_date).toLocaleDateString()}
                  </span>
                </div>

                <span className="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase bg-sky-950/60 text-sky-400 border border-sky-800/40 w-fit">
                  {order.status}
                </span>
              </div>

              {/* Lista de servicios operativos asignados */}
              <div className="space-y-3">
                <h4 className="text-xs uppercase tracking-wider text-slate-400 font-bold">
                  Servicios y Proveedores Asignados
                </h4>

                <div className="overflow-x-auto">
                  <table className="w-full text-left text-sm">
                    <thead className="bg-slate-950/70 text-slate-400 text-xs uppercase border-b border-slate-800">
                      <tr>
                        <th className="px-4 py-2.5">Servicio</th>
                        <th className="px-4 py-2.5">Proveedor</th>
                        <th className="px-4 py-2.5">Hora / Encuentro</th>
                        <th className="px-4 py-2.5">Pax</th>
                        <th className="px-4 py-2.5">Voucher</th>
                        <th className="px-4 py-2.5">Confirmación Proveedor</th>
                        <th className="px-4 py-2.5">Estado</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800/60">
                      {order.services?.map((svc: any) => (
                        <tr key={svc.id} className="hover:bg-slate-800/20">
                          <td className="px-4 py-3 font-medium text-white">{svc.name}</td>
                          <td className="px-4 py-3 text-slate-300">
                            {svc.provider?.name || 'Por asignar'}
                          </td>
                          <td className="px-4 py-3 text-slate-400">
                            {svc.time ? svc.time.substring(0, 5) : '--:--'} · {svc.meeting_point || 'Sin punto definido'}
                          </td>
                          <td className="px-4 py-3 text-slate-300 font-semibold">{svc.pax}</td>
                          <td className="px-4 py-3 font-mono text-xs text-amber-400">
                            {svc.voucher_code || 'Sin emitir'}
                          </td>
                          <td className="px-4 py-3 text-xs">
                            {svc.provider_confirmation_code ? (
                              <div>
                                <span className="font-mono text-emerald-400 font-semibold">
                                  {svc.provider_confirmation_code}
                                </span>
                                {svc.provider_notes && (
                                  <div className="text-[11px] text-slate-400 truncate max-w-xs">
                                    {svc.provider_notes}
                                  </div>
                                )}
                              </div>
                            ) : (
                              <span className="text-slate-500 italic">Pendiente</span>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            <span
                              className={`inline-block px-2 py-0.5 rounded text-[11px] font-bold uppercase ${
                                svc.status === 'confirmado'
                                  ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                                  : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
                              }`}
                            >
                              {svc.status}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}