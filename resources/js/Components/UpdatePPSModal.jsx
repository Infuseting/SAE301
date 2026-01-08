import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X, FileText, Save } from 'lucide-react';
import Modal from '@/Components/Modal';

export default function UpdatePPSModal({ isOpen, onClose, participant, raceId }) {
    const { data, setData, put, processing, errors } = useForm({
        pps_number: participant?.pps_number || '',
        pps_expiry: participant?.pps_expiry || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        put(route('race.updatePPS', { race: raceId, user: participant.user_id }), {
            onSuccess: () => {
                onClose();
            },
        });
    };

    if (!participant) return null;

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="lg">
            <div className="bg-white rounded-2xl overflow-hidden shadow-xl">
                {/* Header */}
                <div className="bg-blue-900 p-6 flex items-center justify-between">
                    <h3 className="text-xl font-black text-white italic uppercase tracking-wider flex items-center gap-3">
                        <FileText className="w-6 h-6 text-emerald-400" />
                        Ajouter un PPS
                    </h3>
                    <button onClick={onClose} className="text-blue-200 hover:text-white transition-colors">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-8 space-y-6">
                    {/* Participant Info */}
                    <div className="bg-gray-50 rounded-xl p-4">
                        <p className="text-xs font-black text-blue-400 uppercase tracking-widest mb-2">
                            Participant
                        </p>
                        <p className="text-lg font-black text-blue-900 uppercase italic">
                            {participant.first_name} {participant.last_name}
                        </p>
                        <p className="text-sm text-emerald-600 font-bold uppercase tracking-wider">
                            {participant.equ_name}
                        </p>
                    </div>

                    {/* PPS Number */}
                    <div>
                        <label className="block text-xs font-black text-blue-900 uppercase tracking-wider mb-2">
                            Numéro PPS *
                        </label>
                        <input
                            type="text"
                            value={data.pps_number}
                            onChange={(e) => setData('pps_number', e.target.value)}
                            className="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-blue-500 font-medium"
                            placeholder="Ex: 1234567890"
                            required
                        />
                        {errors.pps_number && (
                            <p className="mt-2 text-sm text-red-600 font-medium">{errors.pps_number}</p>
                        )}
                    </div>

                    {/* PPS Expiry */}
                    <div>
                        <label className="block text-xs font-black text-blue-900 uppercase tracking-wider mb-2">
                            Date d'expiration *
                        </label>
                        <input
                            type="date"
                            value={data.pps_expiry}
                            onChange={(e) => setData('pps_expiry', e.target.value)}
                            className="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-blue-500 font-medium"
                            required
                        />
                        {errors.pps_expiry && (
                            <p className="mt-2 text-sm text-red-600 font-medium">{errors.pps_expiry}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-4 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <Save className="w-4 h-4" />
                            {processing ? 'Enregistrement...' : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
