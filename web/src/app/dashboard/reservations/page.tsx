'use client';

import { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';

export default function ReservationsPage() {
  const [reservations, setReservations] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    async function loadReservations() {
      try {
        const res = await fetchApi('/reservations');
        setReservations(res.data?.data || []);
      } catch (err) {
        console.error('Error cargando reservas:', err);
      } finally {
        setLoading(false);
      }
    }
    loadReservations();
  }, []);

  const filtered = reservations.filter((r) =>
    r.code.toLowerCase().includes(search.toLowerCase()) ||
    r.client?.name?.toLowerCase().includes(search.toLowerCase())
  );

    const openVoucher = (code: string) => {
    window.open(
      `https://turismobk.intelectalab.com/api/v1/public/reservations/${code}/voucher-view?tenant=demo-travel`,
      '_blank'
    );
  };

  return (
    <div className="space-y-6">
      <header className="border-b border-slate-800 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold text-amber-400">Gestión de Reservas</h1>
          <p className="text-sm text-slate-400 mt-1">
            Control de reservas comerciales, pasajeros y emisión de vouchers
          </p>
        </div>

        <input
          type="text"
          placeholder="Buscar por código o cliente..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white focus:border-amber-400 focus:outline-none w-full md:w-72"
        />
      </header>

      {loading ? (
        <div className="text-slate-400 py-12 text-center">Cargando reservas...</div>
      ) : filtered.length === 0 ? (
        <div className="rounded-xl bg-slate-900 border border-slate-800 p-8 text-center text-slate-400">
          No se encontraron reservas registradas.
        </div>
      ) : (
        <div className="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase text-xs">
                <tr>
                  <th className="px-6 py-4">Código</th>
                  <th className="px-6 py-4">Cliente / Titular</th>
                  <th className="px-6 py-4">Fecha Inicio</th>
                  <th className="px-6 py-4">Pax</th>
                  <th className="px-6 py-4">Total</th>
                  <th className="px-6 py-4">Pagado</th>
                  <th className="px-6 py-4">Saldo</th>
                  <th className="px-6 py-4">Estado Pago</th>
                  <th className="px-6 py-4 text-right">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {filtered.map((res) => {
                  const balanceNum = parseFloat(res.balance);
                  const isSettled = balanceNum <= 0;

                  return (
                    <tr key={res.id} className="hover:bg-slate-800/40 transition">
                      <td className="px-6 py-4 font-mono font-bold text-amber-400">
                        {res.code}
                      </td>
                      <td className="px-6 py-4">
                        <div className="font-semibold text-white">{res.client?.name}</div>
                        <div className="text-xs text-slate-500">{res.client?.code}</div>
                      </td>
                      <td className="px-6 py-4 text-slate-300">
                        {new Date(res.travel_date_start).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 text-slate-300 font-semibold">{res.pax}</td>
                      <td className="px-6 py-4 text-slate-200">
                        {res.currency} {parseFloat(res.total).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 text-emerald-400">
                        {res.currency} {parseFloat(res.paid_amount).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 text-rose-400">
                        {res.currency} {balanceNum.toFixed(2)}
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className={`inline-block px-2.5 py-1 rounded-full text-xs font-bold ${
                            isSettled
                              ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                              : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
                          }`}
                        >
                          {isSettled ? 'PAGADO' : 'PENDIENTE'}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <button
                          onClick={() => openVoucher(res.code)}
                          className="px-3 py-1.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/30 hover:bg-amber-500 hover:text-slate-950 text-xs font-semibold transition"
                        >
                          Ver Voucher
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}