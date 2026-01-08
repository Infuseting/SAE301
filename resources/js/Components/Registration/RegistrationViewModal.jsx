import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { X, Users, Calendar, Check, Clock, Mail, AlertTriangle, Trash2 } from 'lucide-react';

/**
 * Modal displaying user's race registration with option to cancel.
 * 
 * @param {boolean} isOpen - Whether modal is visible
 * @param {function} onClose - Close modal handler
 * @param {object} race - Race data
 * @param {object} registration - User's registration data
 */
export default function RegistrationViewModal({ isOpen, onClose, race, registration }) {
    const [loading, setLoading] = useState(false);
    const [showConfirmCancel, setShowConfirmCancel] = useState(false);

    if (!isOpen || !registration) return null;

    const handleCancel = async () => {
        setLoading(true);

        // Get CSRF token from cookie
        const getCsrfToken = () => {
            const name = 'XSRF-TOKEN=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookies = decodedCookie.split(';');
            for (let cookie of cookies) {
                cookie = cookie.trim();
                if (cookie.indexOf(name) === 0) {
                    return cookie.substring(name.length);
                }
            }
            return '';
        };

        try {
            const response = await fetch(route('race.registration.cancel', registration.id), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                onClose();
                router.reload();
            }
        } catch (err) {
            console.error('Cancel error:', err);
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'confirmed':
                return { color: 'bg-emerald-100 text-emerald-700', icon: Check, label: 'Confirmée' };
            case 'pending':
                return { color: 'bg-amber-100 text-amber-700', icon: Clock, label: 'En attente' };
            case 'cancelled':
                return { color: 'bg-red-100 text-red-700', icon: X, label: 'Annulée' };
            default:
                return { color: 'bg-slate-100 text-slate-700', icon: Clock, label: status };
        }
    };

    const statusBadge = getStatusBadge(registration.status);
    const StatusIcon = statusBadge.icon;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-center justify-center p-4">
                {/* Backdrop */}
                <div
                    className="fixed inset-0 bg-blue-900/60 backdrop-blur-sm transition-opacity"
                    onClick={onClose}
                />

                {/* Modal */}
                <div className="relative w-full max-w-lg transform overflow-hidden rounded-[2.5rem] bg-white shadow-2xl">
                    {/* Header */}
                    <div className="relative bg-gradient-to-r from-emerald-600 to-emerald-500 px-8 py-6">
                        <button
                            onClick={onClose}
                            className="absolute right-6 top-6 text-white/60 hover:text-white transition-colors"
                        >
                            <X className="h-6 w-6" />
                        </button>

                        <h2 className="text-2xl font-black text-white italic uppercase tracking-tight">
                            Mon Inscription
                        </h2>
                        <p className="text-emerald-100/80 text-sm font-medium mt-1">
                            {race?.title || race?.race_name}
                        </p>

                        {/* Status badge */}
                        <div className={`inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-full text-sm font-bold ${statusBadge.color}`}>
                            <StatusIcon className="h-4 w-4" />
                            {statusBadge.label}
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-8 space-y-6">
                        {/* Registration date */}
                        <div className="flex items-center gap-3 text-slate-600">
                            <Calendar className="h-5 w-5 text-slate-400" />
                            <span>Inscrit le {new Date(registration.created_at).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            })}</span>
                        </div>

                        {/* Team info */}
                        <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div className="flex items-center gap-2 mb-3">
                                <Users className="h-5 w-5 text-slate-400" />
                                <h4 className="font-bold text-slate-700">
                                    {registration.is_temporary_team ? 'Équipe temporaire' : registration.team?.name || 'Mon équipe'}
                                </h4>
                            </div>

                            {/* Team members */}
                            <div className="space-y-2">
                                {registration.team_members?.map((member, idx) => {
                                    const getMemberStatusIcon = () => {
                                        switch (member.status) {
                                            case 'confirmed':
                                                return <Check className="h-3 w-3 text-emerald-500" />;
                                            case 'pending':
                                                return <Clock className="h-3 w-3 text-amber-500" />;
                                            case 'pending_account':
                                                return <Mail className="h-3 w-3 text-blue-500" />;
                                            default:
                                                return null;
                                        }
                                    };

                                    return (
                                        <div key={idx} className="flex items-center gap-3 py-2">
                                            <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-500">
                                                {member.name?.charAt(0)?.toUpperCase() || '?'}
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-medium text-slate-700">{member.name}</p>
                                                <p className="text-xs text-slate-400">{member.email}</p>
                                            </div>
                                            {getMemberStatusIcon()}
                                        </div>
                                    );
                                })}

                                {(!registration.team_members || registration.team_members.length === 0) && (
                                    <p className="text-sm text-slate-400 italic">Aucun membre</p>
                                )}
                            </div>
                        </div>

                        {/* Cancel section */}
                        {registration.status !== 'cancelled' && (
                            <div className="pt-4 border-t border-slate-100">
                                {!showConfirmCancel ? (
                                    <button
                                        onClick={() => setShowConfirmCancel(true)}
                                        className="w-full py-3 text-red-600 font-bold text-sm uppercase tracking-wider hover:bg-red-50 rounded-xl transition-colors flex items-center justify-center gap-2"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        Annuler mon inscription
                                    </button>
                                ) : (
                                    <div className="p-4 bg-red-50 border border-red-100 rounded-2xl space-y-4">
                                        <div className="flex items-start gap-3">
                                            <AlertTriangle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                                            <div>
                                                <p className="font-bold text-red-800">Confirmer l'annulation ?</p>
                                                <p className="text-sm text-red-600 mt-1">
                                                    Cette action est irréversible. Vous devrez vous réinscrire si vous changez d'avis.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex gap-3">
                                            <button
                                                onClick={() => setShowConfirmCancel(false)}
                                                disabled={loading}
                                                className="flex-1 py-3 bg-white border border-slate-200 text-slate-700 font-bold text-sm uppercase tracking-wider rounded-xl hover:bg-slate-50 transition-colors"
                                            >
                                                Non, garder
                                            </button>
                                            <button
                                                onClick={handleCancel}
                                                disabled={loading}
                                                className="flex-1 py-3 bg-red-600 text-white font-bold text-sm uppercase tracking-wider rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50"
                                            >
                                                {loading ? 'Annulation...' : 'Oui, annuler'}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
