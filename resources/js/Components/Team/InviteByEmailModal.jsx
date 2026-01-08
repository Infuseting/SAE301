import { useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Modal for inviting users by email to a team.
 */
export default function InviteByEmailModal({ isOpen, onClose, teamId }) {
    const { data, setData, post, processing, errors, reset } = useForm({ email: '' });
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            reset();
            onClose();
        }
    }, [flash?.success, reset, onClose]);

    const handleSend = (e) => {
        e.preventDefault();
        post(`/teams/${teamId}/invite-email`, {
            preserveScroll: true
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
                {/* Modal Header */}
                <div className="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 className="text-2xl font-bold text-gray-900">Inviter par email</h3>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                    >
                        ×
                    </button>
                </div>

                {/* Modal Body */}
                <form onSubmit={handleSend} className="p-6">
                    <input
                        type="email"
                        placeholder="Entrez l'email..."
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
                        disabled={processing}
                        required
                    />
                    {errors.email && (
                        <p className="text-red-500 text-sm mb-4">{errors.email}</p>
                    )}

                    {/* Modal Footer */}
                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-4 mt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Envoi...' : 'Envoyer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
