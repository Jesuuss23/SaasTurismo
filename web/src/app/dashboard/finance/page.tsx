'use client';

import { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';

export default function FinancePage() {
  const [tab, setTab] = useState<'receivables' | 'payables'>('receivables');
  const [receivables, setReceivables] = useState<any[]>([]);
  const [payables, setPayables] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Estados para modal de desembolso (CxP)
  const [selectedPayable, setSelectedPayable] = useState<any | null>(null);
  const [disbursementAmount, setDisbursementAmount] = useState('');
  const [disbursementMethod, setDisbursementMethod] = useState('Transferencia');
  const [bankRef, setBankRef] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function loadData() {
    setLoading(true);
    try {
      const [resCxc, resCxp] = await Promise.all([
        fetchApi('/finance/receivables'),
        fetchApi('/finance/payables'),
      ]);
      setReceivables(resCxc.data?.data || []);
      setPayables(resCxp.data?.data || []);
    } catch (err) {
      console.error('Error cargando finanzas:', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  const handleRegisterDisbursement = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPayable) return;

    setSubmitting(true);
    try {
      await fetchApi(`/finance/payables/${selectedPayable.id}/disbursements`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          amount: parseFloat(disbursementAmount),
          method: disbursementMethod,
          bank_reference: bankRef,
          payment_date: new Date().toISOString().split('T')[0],
          notes: 'Liquidación registrada desde panel web',
        }),
      });

      setSelectedPayable(null);
      setDisbursementAmount('');
      setBankRef('');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Error al procesar el desembolso');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <header className="border-b border-slate-800 pb-5">
        <h1 className="text-3xl font-extrabold text-amber-400">Tesorería y Finanzas</h1>
        <p className="text-sm text-slate-400 mt-1">
          Gestión de cobros a clientes (CxC) y liquidaciones a proveedores (CxP)
        </p>

        {/* Pestañas */}
        <div className="flex gap-4 mt-6">
          <button
            onClick={() => setTab('receivables')}
            className={`px-4 py-2 rounded-lg text-sm font-semibold transition ${
              tab === 'receivables'
                ? 'bg-amber-500 text-slate-950 shadow'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
            }`}
          >
            Cuentas por Cobrar (Clientes)
          </button>
          <button
            onClick={() => setTab('payables')}
            className={`px-4 py-2 rounded-lg text-sm font-semibold transition ${
              tab === 'payables'
                ? 'bg-amber-500 text-slate-950 shadow'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
            }`}
          >
            Cuentas por Pagar (Proveedores)
          </button>
        </div>
      </header>

      {loading ? (
        <div className="text-slate-400 py-12 text-center">Cargando registros financieros...</div>
      ) : tab === 'receivables' ? (
        /* TABLA CUENTAS POR COBRAR */
        <div className="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-950 text-slate-400 uppercase text-xs border-b border-slate-800">
                <tr>
                  <th className="px-6 py-4">Reserva</th>
                  <th className="px-6 py-4">Cliente</th>
                  <th className="px-6 py-4">Concepto</th>
                  <th className="px-6 py-4">Vencimiento</th>
                  <th className="px-6 py-4">Total</th>
                  <th className="px-6 py-4">Cobrado</th>
                  <th className="px-6 py-4">Saldo Pendiente</th>
                  <th className="px-6 py-4">Estado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {receivables.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-6 py-8 text-center text-slate-500">
                      No hay cuentas por cobrar registradas.
                    </td>
                  </tr>
                ) : (
                  receivables.map((cxc) => (
                    <tr key={cxc.id} className="hover:bg-slate-800/30 transition">
                      <td className="px-6 py-4 font-mono font-bold text-amber-400">
                        {cxc.reservation?.code || 'N/A'}
                      </td>
                      <td className="px-6 py-4 text-white font-medium">
                        {cxc.client?.name || 'Cliente Directo'}
                      </td>
                      <td className="px-6 py-4 text-slate-400">{cxc.concept}</td>
                      <td className="px-6 py-4 text-slate-300">
                        {new Date(cxc.due_date).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 text-slate-200">
                        {cxc.currency} {parseFloat(cxc.amount).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 text-emerald-400">
                        {cxc.currency} {parseFloat(cxc.paid_amount).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 text-rose-400 font-semibold">
                        {cxc.currency} {parseFloat(cxc.balance).toFixed(2)}
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold uppercase ${
                            cxc.status === 'pagada'
                              ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                              : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
                          }`}
                        >
                          {cxc.status}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        /* TABLA CUENTAS POR PAGAR */
        <div className="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-950 text-slate-400 uppercase text-xs border-b border-slate-800">
                <tr>
                  <th className="px-6 py-4">Código CxP</th>
                  <th className="px-6 py-4">Proveedor</th>
                  <th className="px-6 py-4">Servicio / Voucher</th>
                  <th className="px-6 py-4">Vencimiento</th>
                  <th className="px-6 py-4">Total</th>
                  <th className="px-6 py-4">Liquidado</th>
                  <th className="px-6 py-4">Saldo por Pagar</th>
                  <th className="px-6 py-4">Estado</th>
                  <th className="px-6 py-4 text-right">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {payables.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="px-6 py-8 text-center text-slate-500">
                      No hay cuentas por pagar registradas.
                    </td>
                  </tr>
                ) : (
                  payables.map((cxp) => {
                    const balanceNum = parseFloat(cxp.balance);
                    const isSettled = balanceNum <= 0;

                    return (
                      <tr key={cxp.id} className="hover:bg-slate-800/30 transition">
                        <td className="px-6 py-4 font-mono font-bold text-amber-400">
                          {cxp.code}
                        </td>
                        <td className="px-6 py-4 text-white font-medium">
                          {cxp.provider?.name}
                        </td>
                        <td className="px-6 py-4 text-slate-300">
                          <div>{cxp.ops_service?.name}</div>
                          <span className="text-xs font-mono text-slate-500">
                            {cxp.ops_service?.voucher_code}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-slate-400">
                          {new Date(cxp.due_date).toLocaleDateString()}
                        </td>
                        <td className="px-6 py-4 text-slate-200">
                          {cxp.currency} {parseFloat(cxp.amount).toFixed(2)}
                        </td>
                        <td className="px-6 py-4 text-emerald-400">
                          {cxp.currency} {parseFloat(cxp.paid_amount).toFixed(2)}
                        </td>
                        <td className="px-6 py-4 text-rose-400 font-semibold">
                          {cxp.currency} {balanceNum.toFixed(2)}
                        </td>
                        <td className="px-6 py-4">
                          <span
                            className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold uppercase ${
                              isSettled
                                ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                                : 'bg-rose-950/60 text-rose-400 border border-rose-800/40'
                            }`}
                          >
                            {cxp.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-right">
                          {!isSettled && (
                            <button
                              onClick={() => {
                                setSelectedPayable(cxp);
                                setDisbursementAmount(balanceNum.toString());
                              }}
                              className="px-3 py-1.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500 hover:text-slate-950 text-xs font-semibold transition"
                            >
                              Pagar / Liquidar
                            </button>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* MODAL PARA REGISTRAR DESEMBOLSO */}
      {selectedPayable && (
        <div className="fixed inset-0 bg-black/70 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
            <div className="flex justify-between items-center border-b border-slate-800 pb-3">
              <h3 className="text-lg font-bold text-amber-400">
                Registrar Desembolso a Proveedor
              </h3>
              <button
                onClick={() => setSelectedPayable(null)}
                className="text-slate-400 hover:text-white text-sm"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleRegisterDisbursement} className="space-y-4 text-sm">
              <div>
                <span className="text-slate-400">Proveedor:</span>
                <p className="font-semibold text-white">{selectedPayable.provider?.name}</p>
              </div>

              <div>
                <span className="text-slate-400">Saldo pendiente:</span>
                <p className="font-mono text-rose-400 font-bold">
                  {selectedPayable.currency} {parseFloat(selectedPayable.balance).toFixed(2)}
                </p>
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">Monto a Desembolsar</label>
                <input
                  type="number"
                  step="0.01"
                  max={selectedPayable.balance}
                  required
                  value={disbursementAmount}
                  onChange={(e) => setDisbursementAmount(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">Método de Pago</label>
                <select
                  value={disbursementMethod}
                  onChange={(e) => setDisbursementMethod(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                >
                  <option value="Transferencia">Transferencia Bancaria</option>
                  <option value="Cheque">Cheque</option>
                  <option value="Efectivo">Efectivo</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">
                  N° Operación / Referencia Bancaria
                </label>
                <input
                  type="text"
                  placeholder="Ej. BCP-OP-98124"
                  value={bankRef}
                  onChange={(e) => setBankRef(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setSelectedPayable(null)}
                  className="px-4 py-2 rounded-lg text-slate-400 hover:text-white transition"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-semibold hover:bg-emerald-400 transition disabled:opacity-50"
                >
                  {submitting ? 'Registrando...' : 'Confirmar Pago'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}