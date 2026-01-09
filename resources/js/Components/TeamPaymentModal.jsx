import React, { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { X, CreditCard, CheckCircle2, Users, AlertTriangle } from 'lucide-react';
import Modal from '@/Components/Modal';

export default function TeamPaymentModal({ isOpen, onClose, team, raceId }) {
    const messages = usePage().props.translations?.messages || {};
    const { post, processing } = useForm();
    const [showConfirmation, setShowConfirmation] = useState(false);

    const handleConfirmPayment = () => {
        post(route('race.confirmTeamPayment', { race: raceId, team: team?.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setShowConfirmation(false);
                onClose();
                // Force page reload to update participant list
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Payment confirmation error:', errors);
                setShowConfirmation(false);
            }
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
                        {messages['modal.team_payment.title'] || 'Payment Validation'}
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
                                    {messages['modal.team_payment.team'] || 'Team'}
                                </p>
                                <h4 className="text-xl font-black text-blue-900 italic uppercase">
                                    {team.name}
                                </h4>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl p-4">
                            <p className="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">
                                {messages['modal.team_payment.members'] || 'Members'} ({team.members?.length || 0})
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
                                                    {messages['modal.team_payment.validated'] || 'Validated'}
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-black uppercase">
                                                    {messages['modal.team_payment.pending'] || 'Pending'}
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
                                            {messages['modal.team_payment.total_to_pay'] || 'Total to pay'}
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
                                            {messages['modal.team_payment.payment_done'] || 'Payment completed'}
                                        </p>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <CreditCard className="w-6 h-6 text-orange-600" />
                                    <div>
                                        <p className="text-sm font-black text-orange-900 uppercase tracking-wider">
                                            {messages['modal.team_payment.payment_pending'] || 'Payment pending'}
                                        </p>
                                        <p className="text-xs text-orange-700 font-medium">
                                            {messages['modal.team_payment.confirm_payment_prompt'] || 'Confirm payment to validate this team\'s registration.'}
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
                            {messages['close'] || 'Close'}
                        </button>
                        {!team.is_paid && (
                            <button
                                onClick={() => setShowConfirmation(true)}
                                disabled={processing}
                                className="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <CheckCircle2 className="w-4 h-4" />
                                {messages['modal.team_payment.confirm_payment'] || 'Confirm payment'}
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {/* Confirmation Modal */}
            {showConfirmation && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-2xl max-w-md w-full mx-4 shadow-2xl">
                        <div className="p-6 space-y-6">
                            {/* Warning Icon */}
                            <div className="flex justify-center">
                                <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                                    <AlertTriangle className="w-8 h-8 text-orange-600" />
                                </div>
                            </div>

                            {/* Title & Message */}
                            <div className="text-center space-y-2">
                                <h3 className="text-xl font-black text-gray-900 uppercase italic">
                                    {messages['modal.team_payment.confirm_title'] || 'Confirm payment?'}
                                </h3>
                                <p className="text-sm text-gray-600 font-medium leading-relaxed">
                                    {messages['modal.team_payment.confirm_description'] || 'This action will mark the team\'s registration as paid and validated.'}
                                </p>
                                <div className="bg-emerald-50 rounded-xl p-4 mt-4">
                                    <p className="text-xs text-emerald-900 font-black uppercase tracking-wider mb-2">
                                        {messages['modal.team_payment.total_amount'] || 'Total amount'}
                                    </p>
                                    <p className="text-3xl font-black text-emerald-600 italic">
                                        {totalPrice.toFixed(2)}€
                                    </p>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3">
                                <button
                                    onClick={() => setShowConfirmation(false)}
                                    disabled={processing}
                                    className="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50"
                                >
                                    {messages['cancel'] || 'Cancel'}
                                </button>
                                <button
                                    onClick={handleConfirmPayment}
                                    disabled={processing}
                                    className="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                    {processing ? (messages['loading'] || 'Loading...') : (messages['confirm_button'] || 'Confirm')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </Modal>
    );
}
