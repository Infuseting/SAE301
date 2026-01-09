import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import InviteUserModal from '@/Components/Team/InviteUserModal';
import InviteByEmailModal from '@/Components/Team/InviteByEmailModal';
import UserAvatar from '@/Components/UserAvatar';

/**
 * Display team details including members and information.
 */ 
export default function Show({ team, auth, users }) {
    const [showInviteModal, setShowInviteModal] = useState(false);
    const [showEmailModal, setShowEmailModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const isCreator = auth?.user?.id === team?.creator_id;

    /**
     * Handle team deletion with confirmation.
     */
    const handleDeleteTeam = () => {
        setIsDeleting(true);
        router.delete(route('teams.destroy', team.id), {
            onFinish: () => {
                setIsDeleting(false);
                setShowDeleteModal(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={team.name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Team Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row gap-6">
                                {/* Team Image */}
                                <div className="flex-shrink-0">
                                    <UserAvatar
                                        user={{ name: team.name, profile_photo_url: team.image ? `/storage/${team.image}` : null }}
                                        type="team"
                                        size="xl"
                                        className="h-48 w-48"
                                    />
                                </div>

                                {/* Team Info */}
                                <div className="flex-1">
                                    <div className="flex justify-between items-start mb-4">
                                        <h1 className="text-4xl font-bold text-gray-900">
                                            {team.name}
                                        </h1>
                                        {isCreator && (
                                            <div className="flex gap-2">
                                                <button 
                                                    onClick={() => setShowInviteModal(true)}
                                                    className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                                >
                                                    Inviter
                                                </button>
                                                <button 
                                                    onClick={() => setShowDeleteModal(true)}
                                                    className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                                >
                                                    Supprimer
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                    <div className="text-gray-600">
                                        <p className="mb-2">
                                            <span className="font-semibold">Membres:</span> {team.members?.length || 0}
                                        </p>
                                        <p>
                                            <span className="font-semibold">Créée le:</span> {team.created_at}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Team Members */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="text-2xl font-bold text-gray-900 mb-6">
                                Membres de l'équipe
                            </h2>

                            {team.members && team.members.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {team.members.map((member) => (
                                        <div
                                            key={member.id}
                                            className="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                                        >
                                            <UserAvatar
                                                user={member}
                                                size="lg"
                                            />
                                            <div>
                                                <p className="font-semibold text-gray-900">
                                                    {member.name}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <p className="text-gray-500">
                                        Aucun membre dans cette équipe
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modals */}
            <InviteUserModal
                isOpen={showInviteModal}
                onClose={() => setShowInviteModal(false)}
                users={users}
                teamMembers={team.members}
                auth={auth}
                teamId={team.id}
                onEmailInviteOpen={() => {
                    setShowInviteModal(false);
                    setShowEmailModal(true);
                }}
            />
            
            <InviteByEmailModal
                isOpen={showEmailModal}
                onClose={() => setShowEmailModal(false)}
                teamId={team.id}
            />

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-xl font-bold text-gray-900 mb-4">
                            Supprimer l'équipe
                        </h3>
                        <p className="text-gray-600 mb-6">
                            Êtes-vous sûr de vouloir supprimer l'équipe <strong>{team.name}</strong> ? 
                            Cette action est irréversible.
                        </p>
                        <div className="flex justify-end gap-3">
                            <button
                                onClick={() => setShowDeleteModal(false)}
                                className="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                                disabled={isDeleting}
                            >
                                Annuler
                            </button>
                            <button
                                onClick={handleDeleteTeam}
                                disabled={isDeleting}
                                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                            >
                                {isDeleting ? 'Suppression...' : 'Supprimer'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
