'use client';

import { Suspense, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { fetchApi } from '@/lib/api';

function CheckoutContent() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const productId = searchParams.get('product_id') || '';
  const rateId = searchParams.get('rate_id') || '';
  const travelDate = searchParams.get('travel_date') || '2026-11-25';
  const initialPax = parseInt(searchParams.get('pax') || '1', 10);

  // Datos del contacto / titular
  const [contactName, setContactName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [docType, setDocType] = useState('DNI');
  const [docNumber, setDocNumber] = useState('');

  // Pasajeros
  const [passengers, setPassengers] = useState(
    Array.from({ length: initialPax }, () => ({
      first_name: '',
      last_name: '',
      doc_type: 'DNI',
      doc_number: '',
    }))
  );

  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const updatePassenger = (index: number, field: string, value: string) => {
    const updated = [...passengers];
    updated[index] = { ...updated[index], [field]: value };
    setPassengers(updated);
  };

  const handleSubmitCheckout = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setErrorMsg('');

    try {
      const payload = {
        product_id: productId,
        rate_id: rateId,
        travel_date: travelDate,
        contact_name: contactName,
        email,
        phone,
        doc_type: docType,
        doc_number: docNumber,
        passengers: passengers.map((p, idx) => ({
          first_name: p.first_name || (idx === 0 ? contactName.split(' ')[0] : 'Pasajero'),
          last_name: p.last_name || (idx === 0 ? (contactName.split(' ')[1] || 'Viajero') : `${idx + 1}`),
          doc_type: p.doc_type,
          doc_number: p.doc_number || docNumber,
        })),
      };

      const res = await fetchApi('/public/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Tenant-Slug': 'demo-travel',
        },
        body: JSON.stringify(payload),
      });

      const resCode = res.data?.reservation_code;
      if (resCode) {
        window.location.href = `https://turismobk.intelectalab.com/api/v1/public/reservations/${resCode}/voucher-view?tenant=demo-travel`;
      } else {
        alert('Reserva creada con éxito.');
        router.push('/tours');
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Error procesando la reserva.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-12 px-6">
      <div className="max-w-3xl mx-auto space-y-8">
        <header className="border-b border-slate-800 pb-5">
          <span className="text-xs font-bold uppercase tracking-wider text-amber-400">Paso Final</span>
          <h1 className="text-3xl font-extrabold text-white mt-1">Finaliza tu Reserva</h1>
          <p className="text-sm text-slate-400 mt-1">
            Fecha de salida: <strong className="text-white">{travelDate}</strong> · Pasajeros: <strong className="text-white">{passengers.length}</strong>
          </p>
        </header>

        {errorMsg && (
          <div className="p-4 rounded-xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-sm">
            {errorMsg}
          </div>
        )}

        <form onSubmit={handleSubmitCheckout} className="space-y-8">
          {/* Datos del Titular */}
          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <h3 className="text-lg font-bold text-white border-b border-slate-800 pb-3">
              Datos del Contacto Principal
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <label className="block text-slate-300 font-medium mb-1">Nombre Completo</label>
                <input
                  type="text"
                  required
                  value={contactName}
                  onChange={(e) => setContactName(e.target.value)}
                  placeholder="Ej. Valeria Navarro"
                  className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">Correo Electrónico</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="valeria.navarro@gmail.com"
                  className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">Teléfono / WhatsApp</label>
                <input
                  type="text"
                  required
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="+51977112233"
                  className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-slate-300 font-medium mb-1">Documento</label>
                  <select
                    value={docType}
                    onChange={(e) => setDocType(e.target.value)}
                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                  >
                    <option value="DNI">DNI</option>
                    <option value="PASAPORTE">Pasaporte</option>
                    <option value="CE">CE</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-300 font-medium mb-1">Número</label>
                  <input
                    type="text"
                    required
                    value={docNumber}
                    onChange={(e) => setDocNumber(e.target.value)}
                    placeholder="72819203"
                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-white focus:border-amber-400 focus:outline-none"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Pasajeros Adicionales */}
          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <h3 className="text-lg font-bold text-white border-b border-slate-800 pb-3">
              Acreditación de Pasajeros ({passengers.length})
            </h3>
            {passengers.map((pax, index) => (
              <div key={index} className="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-3">
                <span className="text-xs font-bold uppercase text-amber-400">Pasajero #{index + 1}</span>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                  <input
                    type="text"
                    placeholder="Nombres"
                    required
                    value={pax.first_name}
                    onChange={(e) => updatePassenger(index, 'first_name', e.target.value)}
                    className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                  />
                  <input
                    type="text"
                    placeholder="Apellidos"
                    required
                    value={pax.last_name}
                    onChange={(e) => updatePassenger(index, 'last_name', e.target.value)}
                    className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                  />
                  <input
                    type="text"
                    placeholder="N° Documento"
                    required
                    value={pax.doc_number}
                    onChange={(e) => updatePassenger(index, 'doc_number', e.target.value)}
                    className="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:border-amber-400 focus:outline-none"
                  />
                </div>
              </div>
            ))}
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-4 rounded-2xl text-lg transition shadow-xl disabled:opacity-50"
          >
            {submitting ? 'Confirmando Reserva...' : 'Confirmar y Obtener Voucher'}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function CheckoutPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-slate-950 text-slate-400 p-8 text-center">Cargando pasarela de compra...</div>}>
      <CheckoutContent />
    </Suspense>
  );
}