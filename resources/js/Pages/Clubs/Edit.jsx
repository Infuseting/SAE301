import { Head, Link, usePage, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClubForm from '@/Components/ClubForm';
import ClubMembersList from '@/Components/ClubMembersList';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';

export default function Edit({ club }) {
    const messages = usePage().props.translations?.messages || {};
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const handleDelete = () => {
        router.delete(route('clubs.destroy', club.club_id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
    };

    const closeDeleteModal = () => {
        setShowDeleteModal(false);
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${messages.edit_club} - ${club.club_name}`} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-5xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <Link href={route('clubs.show', club.club_id)} className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                            {messages.back || 'Retour'}
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            {messages.edit_club}: {club.club_name}
                        </h1>
                        <p className="text-gray-600">
                            {messages.edit_club_subtitle}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Club Details Form */}
                        <div className="lg:col-span-2">
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                                <h2 className="text-xl font-bold text-gray-900 mb-6">{messages.club_details}</h2>
                                <ClubForm
                                    club={club}
                                    submitRoute="clubs.update"
                                    submitLabel={messages.save}
                                />
                            </div>
                        </div>

                        {/* Member Management */}
                        <div className="lg:col-span-1 space-y-6">
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                <h2 className="text-xl font-bold text-gray-900 mb-6">{messages.manage_members}</h2>
                                <ClubMembersList
                                    members={club.members || []}
                                    pendingRequests={club.pending_requests || []}
                                    isManager={true}
                                    clubId={club.club_id}
                                    compact={true}
                                />
                            </div>

                            {/* Danger Zone */}
                            <div className="bg-white rounded-2xl shadow-sm border-2 border-red-200 p-6">
                                <h2 className="text-lg font-semibold text-red-600 mb-4">
                                    Zone de danger
                                </h2>
                                <p className="text-sm text-gray-600 mb-4">
                                    La suppression du club est irréversible. Tous les membres et données associées seront supprimés.
                                </p>
                                <DangerButton type="button" onClick={() => setShowDeleteModal(true)}>
                                    {messages.delete_club || 'Supprimer le club'}
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            <Modal show={showDeleteModal} onClose={closeDeleteModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        {messages.delete_club_title || 'Êtes-vous sûr de vouloir supprimer ce club ?'}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        {messages.delete_club_confirmation || 'La suppression du club est irréversible. Tous les membres et données associées seront supprimés.'}
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton type="button" onClick={closeDeleteModal}>
                            {messages.cancel || 'Annuler'}
                        </SecondaryButton>

                        <DangerButton type="button" className="ms-3" onClick={handleDelete}>
                            {messages.delete_club || 'Supprimer le club'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
