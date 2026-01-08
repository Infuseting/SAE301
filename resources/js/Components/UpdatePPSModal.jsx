import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X, FileText, Save, CheckCircle2, XCircle, Clock } from 'lucide-react';
import Modal from '@/Components/Modal';

/**
 * Modal component for managing participant PPS (Pass'Sport Santé) information.
 * Allows race managers to add/update PPS details and verify/reject submissions.
 * 
 * @param {boolean} isOpen - Controls modal visibility
 * @param {function} onClose - Callback to close the modal
 * @param {object} participant - Participant data including PPS information
 * @param {number} raceId - ID of the race for API routing
 */
export default function UpdatePPSModal({ isOpen, onClose, participant, raceId }) {
    const { data, setData, put, processing, errors } = useForm({
        pps_number: participant?.pps_number || '',
        pps_expiry: participant?.pps_expiry || '',
    });

    /**
     * Submits the PPS information update
     */
    const handleSubmit = (e) => {
        e.preventDefault();
        
        put(route('participants.update', { participant: participant.participant_id }), {
            onSuccess: () => {
                onClose();
            },
        });
    };

    /**
     * Verifies the participant's PPS (approves it)
     */
    const handleVerify = () => {
        put(route('participants.verifyPps', { participant: participant.participant_id }), {
            onSuccess: () => {
                onClose();
            },
        });
    };

    /**
     * Rejects the participant's PPS
     */
    const handleReject = () => {
        setData('pps_status', 'rejected');
        put(route('participants.update', { participant: participant.participant_id }), {
            data: { pps_status: 'rejected' },
            onSuccess: () => {
                onClose();
            },
        });
    };

    if (!participant) return null;

    const hasPPSData = participant.pps_number && participant.pps_expiry;
    const isPending = participant.pps_status === 'pending';
    const isVerified = participant.pps_status === 'verified';

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="lg">
            <div className="bg-white rounded-2xl overflow-hidden shadow-xl">
                {/* Header */}
                <div className="bg-blue-900 p-6 flex items-center justify-between">
                    <h3 className="text-xl font-black text-white italic uppercase tracking-wider flex items-center gap-3">
                        <FileText className="w-6 h-6 text-emerald-400" />
                        {hasPPSData ? 'Gérer le PPS' : 'Ajouter un PPS'}
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

                    {/* Current Status Badge */}
                    {hasPPSData && (
                        <div className="flex items-center justify-center">
                            {isPending && (
                                <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-50 text-orange-600">
                                    <Clock className="w-4 h-4" />
                                    <span className="text-xs font-black uppercase tracking-wider">En attente de vérification</span>
                                </div>
                            )}
                            {isVerified && (
                                <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 text-emerald-600">
                                    <CheckCircle2 className="w-4 h-4" />
                                    <span className="text-xs font-black uppercase tracking-wider">Vérifié</span>
                                </div>
                            )}
                            {participant.pps_status === 'rejected' && (
                                <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-50 text-red-600">
                                    <XCircle className="w-4 h-4" />
                                    <span className="text-xs font-black uppercase tracking-wider">Rejeté</span>
                                </div>
                            )}
                        </div>
                    )}

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
                    <div className="space-y-3 pt-4">
                        {/* Verification Actions (if PPS data exists and is pending) */}
                        {hasPPSData && isPending && (
                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={handleVerify}
                                    disabled={processing}
                                    className="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                    Approuver
                                </button>
                                <button
                                    type="button"
                                    onClick={handleReject}
                                    disabled={processing}
                                    className="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <XCircle className="w-4 h-4" />
                                    Rejeter
                                </button>
                            </div>
                        )}

                        {/* Save/Cancel Actions */}
                        <div className="flex gap-4">
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
                    </div>
                </form>
            </div>
        </Modal>
    );
}
