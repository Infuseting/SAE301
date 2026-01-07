import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';

export default function ClubApproval({ pendingClubs }) {
    const messages = usePage().props.translations?.messages || {};
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedClub, setSelectedClub] = useState(null);

    const { post, data, setData, processing } = useForm({
        reason: '',
    });

    const handleApprove = (clubId) => {
        post(route('admin.clubs.approve', clubId));
    };

    const openRejectModal = (club) => {
        setSelectedClub(club);
        setShowRejectModal(true);
    };

    const handleReject = () => {
        if (selectedClub) {
            post(route('admin.clubs.reject', selectedClub.club_id), {
                onSuccess: () => {
                    setShowRejectModal(false);
                    setSelectedClub(null);
                    setData('reason', '');
                },
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.approve_pending_clubs} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-7xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">{messages.pending_clubs}</h1>
                        <p className="text-gray-600">
                            {messages.pending_clubs_subtitle || 'Review and approve clubs waiting for validation'}
                        </p>
                    </div>

                    {/* Pending Clubs List */}
                    {pendingClubs.data.length === 0 ? (
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-16 h-16 mx-auto text-gray-400 mb-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">{messages.no_pending_clubs}</h3>
                            <p className="text-gray-500">
                                {messages.no_pending_clubs_description || 'All clubs have been reviewed'}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {pendingClubs.data.map((club) => (
                                <div key={club.club_id} className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div className="p-8">
                                        <div className="flex items-start justify-between mb-6">
                                            <div className="flex-1">
                                                <h2 className="text-2xl font-bold text-gray-900 mb-2">{club.club_name}</h2>
                                                <div className="space-y-1 text-gray-600">
                                                    <div className="flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 mr-2">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                        </svg>
                                                        {club.club_street}, {club.club_city} {club.club_postal_code}
                                                    </div>
                                                    <div className="flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 mr-2">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                        </svg>
                                                        {messages.created_by}: {club.creator?.name}
                                                    </div>
                                                </div>
                                            </div>
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                {messages.pending_approval}
                                            </span>
                                        </div>

                                        {/* Club Details Grid */}
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 p-6 bg-gray-50 rounded-lg">
                                            {club.club_number && (
                                                <div>
                                                    <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                                        {messages.club_number}
                                                    </h4>
                                                    <p className="text-gray-900">{club.club_number}</p>
                                                </div>
                                            )}
                                            {club.ffso_id && (
                                                <div>
                                                    <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                                        {messages.ffso_id}
                                                    </h4>
                                                    <p className="text-gray-900">{club.ffso_id}</p>
                                                </div>
                                            )}
                                            <div>
                                                <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                                    {messages.created_at || 'Created'}
                                                </h4>
                                                <p className="text-gray-900">
                                                    {new Date(club.created_at).toLocaleDateString()}
                                                </p>
                                            </div>
                                        </div>

                                        {club.description && (
                                            <div className="mb-6">
                                                <h4 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                                    {messages.club_description}
                                                </h4>
                                                <p className="text-gray-700 leading-relaxed">{club.description}</p>
                                            </div>
                                        )}

                                        {/* Action Buttons */}
                                        <div className="flex items-center space-x-3 pt-6 border-t border-gray-200">
                                            <PrimaryButton
                                                onClick={() => handleApprove(club.club_id)}
                                                disabled={processing}
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5 mr-2">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                {messages.approve_club}
                                            </PrimaryButton>
                                            <SecondaryButton
                                                onClick={() => openRejectModal(club)}
                                                disabled={processing}
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5 mr-2">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                {messages.reject_club}
                                            </SecondaryButton>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Pagination */}
                            {pendingClubs.last_page > 1 && (
                                <div className="flex justify-center mt-8">
                                    <nav className="flex items-center space-x-2">
                                        {pendingClubs.links.map((link, index) => (
                                            <a
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-4 py-2 rounded-lg font-medium transition ${link.active
                                                        ? 'bg-emerald-600 text-white'
                                                        : link.url
                                                            ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Reject Modal */}
            <Modal show={showRejectModal} onClose={() => setShowRejectModal(false)}>
                <div className="p-6">
                    <h2 className="text-xl font-bold text-gray-900 mb-4">
                        {messages.reject_club}: {selectedClub?.club_name}
                    </h2>
                    <p className="text-gray-600 mb-4">
                        {messages.reject_reason_prompt || 'Please provide a reason for rejecting this club (optional)'}
                    </p>
                    <TextInput
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        className="w-full mb-4"
                        placeholder={messages.rejection_reason}
                    />
                    <div className="flex items-center justify-end space-x-3">
                        <SecondaryButton onClick={() => setShowRejectModal(false)}>
                            {messages.cancel}
                        </SecondaryButton>
                        <PrimaryButton onClick={handleReject} disabled={processing}>
                            {messages.reject_club}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
