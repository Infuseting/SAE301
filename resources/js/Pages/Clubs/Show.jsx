import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClubMembersList from '@/Components/ClubMembersList';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import { useState } from 'react';

export default function Show({ club, isMember, isManager }) {
    const messages = usePage().props.translations?.messages || {};
    const { post, delete: destroy, processing } = useForm();
    const [confirmingClubDeletion, setConfirmingClubDeletion] = useState(false);

    const handleJoin = () => {
        post(route('clubs.join', club.club_id));
    };

    const handleLeave = () => {
        if (confirm(messages.confirm_leave_club || 'Are you sure you want to leave this club?')) {
            post(route('clubs.leave', club.club_id));
        }
    };

    const confirmClubDeletion = () => {
        setConfirmingClubDeletion(true);
    };

    const deleteClub = () => {
        destroy(route('clubs.destroy', club.club_id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => setConfirmingClubDeletion(false),
        });
    };

    const closeModal = () => {
        setConfirmingClubDeletion(false);
    };

    return (
        <AuthenticatedLayout>
            <Head title={club.club_name} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-5xl mx-auto px-6">
                    {/* Back Button */}
                    <Link href={route('clubs.index')} className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        {messages.back_to_clubs || 'Retour aux clubs'}
                    </Link>

                    {/* Header */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                        {/* Club Image or Default Icon */}
                        <div className="h-48 bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center overflow-hidden">
                            {club.club_image ? (
                                <img
                                    src={`/storage/${club.club_image}`}
                                    alt={club.club_name}
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1} stroke="currentColor" className="w-32 h-32 text-emerald-600">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            )}
                        </div>

                        {/* Club Info */}
                        <div className="p-8">
                            <div className="flex items-start justify-between mb-6">
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 mb-2">{club.club_name}</h1>
                                    <div className="flex items-center space-x-4 text-gray-600">
                                        <div className="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 mr-1">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            {club.club_street}, {club.club_city} {club.club_postal_code}
                                        </div>
                                        {club.members && (
                                            <div className="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 mr-1">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                </svg>
                                                {club.members.length} {messages.club_members}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex items-center space-x-2">
                                    {isManager ? (
                                        <>
                                            <Link href={route('clubs.edit', club.club_id)}>
                                                <SecondaryButton>{messages.edit_club}</SecondaryButton>
                                            </Link>
                                            <DangerButton onClick={confirmClubDeletion} disabled={processing}>
                                                {messages.delete_club || messages.delete || 'Delete Club'}
                                            </DangerButton>
                                        </>
                                    ) : isMember ? (
                                        <SecondaryButton onClick={handleLeave} disabled={processing}>
                                            {messages.leave_club}
                                        </SecondaryButton>
                                    ) : (
                                        <PrimaryButton onClick={handleJoin} disabled={processing}>
                                            {messages.join_club}
                                        </PrimaryButton>
                                    )}
                                </div>
                            </div>

                            {/* Club Details */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                {club.club_number && (
                                    <div>
                                        <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                            {messages.club_number}
                                        </h3>
                                        <p className="text-gray-900">{club.club_number}</p>
                                    </div>
                                )}
                                {club.ffso_id && (
                                    <div>
                                        <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                            {messages.ffso_id}
                                        </h3>
                                        <p className="text-gray-900">{club.ffso_id}</p>
                                    </div>
                                )}
                            </div>

                            {club.description && (
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                        {messages.club_description}
                                    </h3>
                                    <p className="text-gray-700 leading-relaxed">{club.description}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Raids Section */}
                    <div className="mb-8 p-8 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div className="flex items-center justify-between mb-6">
                            <h2 className="text-xl font-bold text-gray-900">Raids du club</h2>
                            {isManager && (
                                <Link href={route('raids.create', { clu_id: club.club_id })}>
                                    <PrimaryButton className="text-sm">+ Nouveau Raid</PrimaryButton>
                                </Link>
                            )}
                        </div>

                        {club.raids && club.raids.length > 0 ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {club.raids.map((raid) => (
                                    <div key={raid.raid_id} className="group relative bg-gray-50 hover:bg-white rounded-xl p-5 border border-gray-200 transition-all duration-300 hover:shadow-md">
                                        <div className="flex justify-between items-start mb-3">
                                            <h3 className="font-bold text-gray-900 group-hover:text-emerald-600 transition-colors">
                                                {raid.raid_name}
                                            </h3>
                                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${raid.is_finished ? 'bg-gray-200 text-gray-500' :
                                                    raid.is_open ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' :
                                                        'bg-indigo-100 text-indigo-700 border border-indigo-200'
                                                }`}>
                                                {raid.is_finished ? 'Terminé' : raid.is_open ? 'Inscription Ouverte' : 'À venir'}
                                            </span>
                                        </div>

                                        <div className="flex flex-col space-y-2 mb-4">
                                            <div className="flex items-center text-xs text-gray-500">
                                                <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                {new Date(raid.raid_date_start).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })}
                                            </div>
                                            <div className="flex items-center text-xs text-gray-500">
                                                <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                {raid.races ? raid.races.length : 0} épreuves disponibles
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Link href={route('raids.show', raid.raid_id)} className="flex-1">
                                                <SecondaryButton className="w-full justify-center py-1.5 text-xs">
                                                    Détails & Inscription
                                                </SecondaryButton>
                                            </Link>
                                            {isManager && (
                                                <Link href={route('raids.edit', raid.raid_id)}>
                                                    <div className="p-2 bg-white border border-gray-200 rounded-lg text-gray-400 hover:text-emerald-600 hover:border-emerald-200 transition-all cursor-pointer">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                    </div>
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="bg-gray-50 border border-gray-100 rounded-xl p-10 text-center">
                                <svg className="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p className="text-gray-400 text-sm">Aucun raid n'est actuellement programmé.</p>
                            </div>
                        )}
                    </div>

                    {/* Members Section (Only visible to members) */}
                    {isMember && club.members && (
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                            <ClubMembersList
                                members={club.members}
                                pendingRequests={club.pending_requests || []}
                                isManager={isManager}
                                clubId={club.club_id}
                            />
                        </div>
                    )}

                    {/* Privacy Notice for Non-Members */}
                    {!isMember && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-12 h-12 mx-auto text-blue-600 mb-3">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <p className="text-blue-900 font-medium">
                                {messages.members_only_notice || 'Join this club to see members and participate in club activities'}
                            </p>
                        </div>
                    )}

                    <Modal show={confirmingClubDeletion} onClose={closeModal}>
                        <div className="p-6">
                            <h2 className="text-lg font-medium text-gray-900">
                                {messages.delete_club_title || 'Delete Club'}
                            </h2>

                            <p className="mt-1 text-sm text-gray-600">
                                {messages.delete_club_confirmation || 'Are you sure you want to delete this club? This action cannot be undone.'}
                            </p>

                            <div className="mt-6 flex justify-end">
                                <SecondaryButton onClick={closeModal}>
                                    {messages.cancel || 'Cancel'}
                                </SecondaryButton>

                                <DangerButton className="ms-3" onClick={deleteClub} disabled={processing}>
                                    {messages.delete_club || messages.delete || 'Delete Club'}
                                </DangerButton>
                            </div>
                        </div>
                    </Modal>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
