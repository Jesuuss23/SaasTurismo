'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';

export default function DashboardPage() {
  const router = useRouter();
  const [summary, setSummary] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    async function loadData() {
      try {
        const res = await fetchApi('/analytics/summary');
        setSummary(res.data);
      } catch (err) {
        console.error('Error cargando analítica:', err);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [router]);

  return (
    <div className="space-y-8">
      {/* Header */}
      <header className="border-b border-slate-800 pb-5">
        <h1 className="text-3xl font-extrabold text-amber-400">Panel de Control Ejecutivo</h1>
        <p className="text-sm text-slate-400 mt-1">
          Métricas consolidadas de ventas, tesorería y conversión en tiempo real
        </p>
      </header>

      {loading ? (
        <div className="text-slate-400 py-12 text-center">Cargando métricas consolidadas...</div>
      ) : summary ? (
        <>
          {/* Tarjetas Principales */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <span className="text-xs uppercase tracking-wider text-slate-400 font-bold">Ventas Totales</span>
              <p className="text-3xl font-black text-amber-400 mt-2">
                ${summary.commercial.total_sales.toFixed(2)}
              </p>
              <span className="text-xs text-slate-500 mt-1 block">
                {summary.commercial.reservations_count} reservas registradas
              </span>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <span className="text-xs uppercase tracking-wider text-slate-400 font-bold">Cobrado (Inflow)</span>
              <p className="text-3xl font-black text-emerald-400 mt-2">
                ${summary.commercial.total_collected.toFixed(2)}
              </p>
              <span className="text-xs text-slate-500 mt-1 block">
                Margen comercial: {summary.commercial.margin_pct}%
              </span>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <span className="text-xs uppercase tracking-wider text-slate-400 font-bold">Por Cobrar (CxC)</span>
              <p className="text-3xl font-black text-sky-400 mt-2">
                ${summary.cashflow.cxc_pending.toFixed(2)}
              </p>
              <span className="text-xs text-slate-500 mt-1 block">Saldo pendiente clientes</span>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <span className="text-xs uppercase tracking-wider text-slate-400 font-bold">Por Pagar (CxP)</span>
              <p className="text-3xl font-black text-rose-400 mt-2">
                ${summary.cashflow.cxp_pending.toFixed(2)}
              </p>
              <span className="text-xs text-slate-500 mt-1 block">Obligaciones proveedores</span>
            </div>
          </div>

          {/* Balances */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
              <h3 className="text-lg font-bold text-white border-b border-slate-800 pb-2">
                Posición Financiera Neta
              </h3>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Total Ingresos Recaudados:</span>
                <span className="font-semibold text-emerald-400">${summary.cashflow.total_inflow.toFixed(2)}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Utilidad Bruta Estimada:</span>
                <span className="font-semibold text-white">${summary.commercial.gross_profit.toFixed(2)}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Flujo Neto por Percibir (CxC - CxP):</span>
                <span className="font-semibold text-amber-400">${summary.cashflow.net_receivable.toFixed(2)}</span>
              </div>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
              <h3 className="text-lg font-bold text-white border-b border-slate-800 pb-2">
                Embudo Comercial (CRM)
              </h3>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Leads Registrados:</span>
                <span className="font-semibold text-white">{summary.funnel.leads_count}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Cotizaciones Emitidas:</span>
                <span className="font-semibold text-white">{summary.funnel.quotes_count}</span>
              </div>
              <div className="flex justify-between items-center text-sm">
                <span className="text-slate-400">Clientes Totales:</span>
                <span className="font-semibold text-white">{summary.funnel.clients_count}</span>
              </div>
            </div>
          </div>
        </>
      ) : (
        <div className="text-rose-400 py-12 text-center">No se pudieron cargar las métricas.</div>
      )}
    </div>
  );
}