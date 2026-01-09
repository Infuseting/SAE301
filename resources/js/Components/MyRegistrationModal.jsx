import React, { useEffect, useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { X, Users, AlertTriangle, CheckCircle2, Trash2 } from 'lucide-react';
import Modal from '@/Components/Modal';

export default function MyRegistrationModal({ isOpen, onClose, registeredTeam, raceId }) {
    const { delete: destroy, processing } = useForm();
    const [showConfirmModal, setShowConfirmModal] = useState(false);

    // Block body scroll when modal is open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    const handleCancelRegistration = () => {
        destroy(route('race.cancelRegistration', { race: raceId, team: registeredTeam.id }), {
            onSuccess: () => {
                setShowConfirmModal(false);
                onClose();
                router.reload();
            },
        });
    };

    if (!registeredTeam) return null;

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="2xl">
            <div className="bg-white rounded-2xl shadow-xl transform transition-all flex flex-col max-h-[calc(100vh-4rem)]">
                {/* Header - Fixed */}
                <div className="bg-blue-900 p-6 flex items-center justify-between flex-shrink-0">
                    <h3 className="text-xl font-black text-white italic uppercase tracking-wider flex items-center gap-3">
                        <CheckCircle2 className="w-6 h-6 text-emerald-400" />
                        Mon Inscription
                    </h3>
                    <button onClick={onClose} className="text-blue-200 hover:text-white transition-colors">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Content - Scrollable */}
                <div className="p-8 space-y-8 overflow-y-auto flex-grow">
                    {/* Warning Message - Shown when pending validation */}
                    {!registeredTeam.validated && (
                        <div className="bg-orange-50 border-2 border-orange-200 rounded-2xl p-6">
                            <div className="flex items-start gap-4">
                                <div className="bg-orange-500 rounded-full p-2">
                                    <AlertTriangle className="w-5 h-5 text-white" />
                                </div>
                                <div className="flex-1">
                                    <h4 className="text-sm font-black text-orange-900 uppercase tracking-wider mb-2">
                                        En attente de validation
                                    </h4>
                                    <p className="text-sm text-orange-700 font-medium">
                                        Votre inscription sera validée par l'organisateur après vérification de vos documents.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Success Message - Shown when validated */}
                    {registeredTeam.validated && (
                        <div className="bg-emerald-50 border-2 border-emerald-200 rounded-2xl p-6">
                            <div className="flex items-start gap-4">
                                <div className="bg-emerald-500 rounded-full p-2">
                                    <CheckCircle2 className="w-5 h-5 text-white" />
                                </div>
                                <div className="flex-1">
                                    <h4 className="text-sm font-black text-emerald-900 uppercase tracking-wider mb-2">
                                        Inscription confirmée
                                    </h4>
                                    <p className="text-sm text-emerald-700 font-medium">
                                        Vous êtes inscrit à cette course avec l'équipe suivante.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Team Details */}
                    <div className="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                        <div className="flex items-center gap-4 mb-6">
                            <div className="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                                <Users className="w-8 h-8 text-white" />
                            </div>
                            <div className="flex-1">
                                <p className="text-xs font-black text-blue-400 uppercase tracking-widest mb-1">
                                    Votre Équipe
                                </p>
                                <h4 className="text-2xl font-black text-blue-900 italic uppercase">
                                    {registeredTeam.name}
                                </h4>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div className="bg-white rounded-xl p-4 text-center">
                                <p className="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">
                                    Membres
                                </p>
                                <p className="text-3xl font-black text-blue-900 italic">
                                    {registeredTeam.members_count}
                                </p>
                            </div>
                            <div className="bg-white rounded-xl p-4 text-center">
                                <p className="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">
                                    Statut
                                </p>
                                <div className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black uppercase ${registeredTeam.validated ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'}`}>
                                    {registeredTeam.validated ? 'Validé' : 'En attente'}
                                </div>
                            </div>
                        </div>

                        {registeredTeam.members && registeredTeam.members.length > 0 && (
                            <div className="mt-6 pt-6 border-t border-gray-200">
                                <p className="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
                                    Membres de l'équipe
                                </p>
                                <div className="space-y-2">
                                    {registeredTeam.members.map((member, idx) => (
                                        <div key={idx} className="flex items-center justify-between bg-white rounded-xl p-3">
                                            <div className="flex items-center gap-3 flex-1">
                                                <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center font-black text-blue-600 text-xs uppercase italic">
                                                    {member.first_name?.[0]}{member.last_name?.[0]}
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm font-bold text-gray-900">
                                                        {member.first_name} {member.last_name}
                                                    </p>
                                                    {member.price_category && (
                                                        <p className="text-xs text-gray-500 font-medium">
                                                            {member.price_category}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {member.price !== undefined && (
                                                    <span className="text-sm font-black text-blue-600">
                                                        {member.price}€
                                                    </span>
                                                )}
                                                {member.is_leader && (
                                                    <span className="text-xs font-black text-emerald-600 uppercase bg-emerald-50 px-2 py-1 rounded">
                                                        Chef
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                    
                                    {/* Total Price */}
                                    {registeredTeam.total_price !== undefined && (
                                        <div className="bg-blue-50 rounded-xl p-4 mt-4 border-2 border-blue-200">
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-black text-blue-900 uppercase tracking-wider">
                                                    Total à payer
                                                </span>
                                                <span className="text-2xl font-black text-blue-600 italic">
                                                    {registeredTeam.total_price.toFixed(2)}€
                                                </span>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Footer - Fixed */}
                <div className="p-6 bg-gray-50 border-t border-gray-200 flex gap-4 flex-shrink-0">
                    <button
                        onClick={onClose}
                        className="flex-1 px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl transition-colors"
                    >
                        Fermer
                    </button>
                    <button
                        onClick={() => setShowConfirmModal(true)}
                        disabled={processing}
                        className="flex-1 px-6 py-4 bg-red-500 hover:bg-red-600 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <Trash2 className="w-4 h-4" />
                        {processing ? 'Annulation...' : 'Annuler l\'inscription'}
                    </button>
                </div>
            </div>

            {/* Confirmation Modal */}
            <Modal show={showConfirmModal} onClose={() => setShowConfirmModal(false)} maxWidth="md">
                <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                    {/* Header */}
                    <div className="bg-red-600 p-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <AlertTriangle className="w-6 h-6 text-white" />
                            </div>
                            <h3 className="text-xl font-black text-white italic uppercase tracking-wider">
                                Confirmer l'annulation
                            </h3>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-8 space-y-4">
                        <p className="text-base text-gray-700 font-semibold">
                            Êtes-vous sûr de vouloir annuler votre inscription ?
                        </p>
                        <div className="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                            <p className="text-sm text-red-700 font-medium">
                                ⚠️ Cette action est <span className="font-black">irréversible</span>. Vous devrez vous réinscrire si vous changez d'avis.
                            </p>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-6 bg-gray-50 border-t border-gray-200 flex gap-4">
                        <button
                            onClick={() => setShowConfirmModal(false)}
                            disabled={processing}
                            className="flex-1 px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50"
                        >
                            Non, garder mon inscription
                        </button>
                        <button
                            onClick={handleCancelRegistration}
                            disabled={processing}
                            className="flex-1 px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <Trash2 className="w-4 h-4" />
                            {processing ? 'Annulation...' : 'Oui, annuler'}
                        </button>
                    </div>
                </div>
            </Modal>
        </Modal>
    );
}
