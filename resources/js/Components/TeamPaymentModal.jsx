import React from 'react';
import { useForm } from '@inertiajs/react';
import { X, CreditCard, CheckCircle2, Users } from 'lucide-react';
import Modal from '@/Components/Modal';

export default function TeamPaymentModal({ isOpen, onClose, team, raceId }) {
    const { post, processing } = useForm();

    const handleConfirmPayment = () => {
        if (!confirm('Confirmer que cette équipe a payé ? Tous les membres seront validés.')) {
            return;
        }

        post(route('race.confirmTeamPayment', { race: raceId, team: team.id }), {
            onSuccess: () => {
                onClose();
            },
        });
    };

    if (!team) return null;

    // Calculate total price for the team
    const totalPrice = team.members?.reduce((sum, member) => sum + (member.price || 0), 0) || 0;

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="lg">
            <div className="bg-white rounded-2xl overflow-hidden shadow-xl">
                {/* Header */}
                <div className="bg-blue-900 p-6 flex items-center justify-between">
                    <h3 className="text-xl font-black text-white italic uppercase tracking-wider flex items-center gap-3">
                        <CreditCard className="w-6 h-6 text-emerald-400" />
                        Validation du paiement
                    </h3>
                    <button onClick={onClose} className="text-blue-200 hover:text-white transition-colors">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-8 space-y-6">
                    {/* Team Info */}
                    <div className="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                        <div className="flex items-center gap-4 mb-4">
                            <div className="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                                <Users className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <p className="text-xs font-black text-blue-400 uppercase tracking-widest">
                                    Équipe
                                </p>
                                <h4 className="text-xl font-black text-blue-900 italic uppercase">
                                    {team.name}
                                </h4>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl p-4">
                            <p className="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">
                                Membres ({team.members?.length || 0})
                            </p>
                            <div className="space-y-2">
                                {team.members?.map((member, idx) => (
                                    <div key={idx} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                        <div className="flex-1">
                                            <span className="text-sm font-medium text-gray-900 block">
                                                {member.first_name} {member.last_name}
                                            </span>
                                            {member.price_category && (
                                                <span className="text-xs text-gray-500 font-medium">
                                                    {member.price_category}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3">
                                            {member.price !== undefined && (
                                                <span className="text-sm font-black text-blue-600">
                                                    {member.price}€
                                                </span>
                                            )}
                                            {member.validated ? (
                                                <span className="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-black uppercase">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    Validé
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-black uppercase">
                                                    En attente
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            
                            {/* Total Price */}
                            {totalPrice > 0 && (
                                <div className="mt-4 pt-4 border-t-2 border-gray-200">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-black text-gray-900 uppercase tracking-wider">
                                            Total à payer
                                        </span>
                                        <span className="text-2xl font-black text-blue-600 italic">
                                            {totalPrice.toFixed(2)}€
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Payment Status */}
                    <div className={`rounded-xl p-6 border-2 ${team.is_paid ? 'bg-emerald-50 border-emerald-300' : 'bg-orange-50 border-orange-300'}`}>
                        <div className="flex items-center gap-3">
                            {team.is_paid ? (
                                <>
                                    <CheckCircle2 className="w-6 h-6 text-emerald-600" />
                                    <div>
                                        <p className="text-sm font-black text-emerald-900 uppercase tracking-wider">
                                            Paiement effectué
                                        </p>
                                        <p className="text-xs text-emerald-700 font-medium">
                                            L'équipe a déjà été marquée comme payée
                                        </p>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <CreditCard className="w-6 h-6 text-orange-600" />
                                    <div>
                                        <p className="text-sm font-black text-orange-900 uppercase tracking-wider">
                                            Paiement en attente
                                        </p>
                                        <p className="text-xs text-orange-700 font-medium">
                                            Confirmez le paiement pour valider tous les membres
                                        </p>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-4">
                        <button
                            onClick={onClose}
                            className="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl transition-colors"
                        >
                            Fermer
                        </button>
                        {!team.is_paid && (
                            <button
                                onClick={handleConfirmPayment}
                                disabled={processing}
                                className="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <CheckCircle2 className="w-4 h-4" />
                                {processing ? 'Validation...' : 'Confirmer le paiement'}
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </Modal>
    );
}
