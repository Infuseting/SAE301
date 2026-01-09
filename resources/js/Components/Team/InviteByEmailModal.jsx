import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Modal for inviting users by email to a team.
 * Supports two modes:
 * - Team mode (teamId provided): sends invite via API
 * - Create mode (onEmailAdd provided): calls callback with email
 */
export default function InviteByEmailModal({ isOpen, onClose, teamId, onEmailAdd }) {
    const { data, setData, post, processing, errors, reset } = useForm({ email: '' });
    const { flash } = usePage().props;
    const messages = usePage().props.translations?.messages || {};
    const [localEmail, setLocalEmail] = useState('');
    const [localError, setLocalError] = useState('');

    const isCreateMode = !!onEmailAdd;

    useEffect(() => {
        if (flash?.success && !isCreateMode) {
            reset();
            onClose();
        }
    }, [flash?.success, reset, onClose, isCreateMode]);

    const handleSend = (e) => {
        e.preventDefault();
        
        if (isCreateMode) {
            const email = localEmail.trim().toLowerCase();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                setLocalError(messages['modal.invite_email.invalid_email'] || 'Please enter a valid email address.');
                return;
            }
            onEmailAdd(email);
            setLocalEmail('');
            setLocalError('');
            onClose();
        } else {
            post(`/teams/${teamId}/invite-email`, {
                preserveScroll: true
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
                {/* Modal Header */}
                <div className="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 className="text-2xl font-bold text-gray-900">{messages['modal.invite_email.title'] || 'Invite by email'}</h3>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                    >
                        ×
                    </button>
                </div>

                {/* Modal Body */}
                <form onSubmit={handleSend} className="p-6">
                    {isCreateMode && (
                        <p className="text-sm text-gray-600 mb-4">
                            {messages['modal.invite_email.create_mode_info'] || 'This person will receive an email invitation after the team is created.'}
                        </p>
                    )}
                    <input
                        type="email"
                        placeholder={messages['modal.invite_email.placeholder'] || 'Enter email...'}
                        value={isCreateMode ? localEmail : data.email}
                        onChange={(e) => isCreateMode ? setLocalEmail(e.target.value) : setData('email', e.target.value)}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
                        disabled={processing}
                        required
                    />
                    {(isCreateMode ? localError : errors.email) && (
                        <p className="text-red-500 text-sm mb-4">{isCreateMode ? localError : errors.email}</p>
                    )}

                    {/* Modal Footer */}
                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-4 mt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            {messages['cancel'] || 'Cancel'}
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? (messages['modal.invite_email.sending'] || 'Sending...') : (isCreateMode ? (messages['modal.invite_email.add'] || 'Add') : (messages['modal.invite_email.send'] || 'Send'))}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
