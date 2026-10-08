'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';

export default function DashboardPage() {
  const router = useRouter();
  const [stats, setStats] = useState<any>({
    leads: 0,
    products: 0,
    reservations: 0,
    receivables: 0
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');

    if (!token) {
      router.push('/login');
      return;
    }

    async function loadData() {
      try {
        const [leads, prods, res, recs] = await Promise.all([
          fetchApi('/crm/leads'),
          fetchApi('/catalog/products'),
          fetchApi('/reservations'),
          fetchApi('/finance/receivables'),
        ]);

        setStats({
          leads: leads.data?.total || leads.data?.data?.length || 0,
          products: prods.data?.total || prods.data?.data?.length || 0,
          reservations: res.data?.total || res.data?.data?.length || 0,
          receivables: recs.data?.total || recs.data?.data?.length || 0,
        });
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [router]);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-8">
      <div className="max-w-6xl mx-auto">
        <header className="flex justify-between items-center mb-8 border-b border-slate-800 pb-4">
          <div>
            <h1 className="text-3xl font-bold text-amber-400">
              Panel de Control
            </h1>

            <p className="text-sm text-slate-400">
              Qhapaq Travel Suite · Panel de Control General
            </p>
          </div>

          <button
            onClick={() => {
              localStorage.clear();
              router.push('/login');
            }}
            className="rounded-lg bg-red-600/80 px-4 py-2 text-sm font-semibold hover:bg-red-500 transition"
          >
            Cerrar sesión
          </button>
        </header>

        {loading ? (
          <p className="text-slate-400">
            Cargando información...
          </p>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <h3 className="text-sm font-medium text-slate-400">
                Leads (CRM)
              </h3>

              <p className="text-3xl font-bold text-white mt-2">
                {stats.leads}
              </p>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <h3 className="text-sm font-medium text-slate-400">
                Productos
              </h3>

              <p className="text-3xl font-bold text-white mt-2">
                {stats.products}
              </p>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <h3 className="text-sm font-medium text-slate-400">
                Reservas
              </h3>

              <p className="text-3xl font-bold text-white mt-2">
                {stats.reservations}
              </p>
            </div>

            <div className="rounded-xl bg-slate-900 border border-slate-800 p-6">
              <h3 className="text-sm font-medium text-slate-400">
                Cuentas por Cobrar (CxC)
              </h3>

              <p className="text-3xl font-bold text-white mt-2">
                {stats.receivables}
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}