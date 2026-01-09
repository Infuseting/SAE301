import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import ImageUpload from '@/Components/ImageUpload';
import SelectResponsableModal from '@/Components/SelectResponsableModal';
import { FaEdit, FaTrash, FaUsers, FaUserMinus, FaChevronDown, FaChevronUp, FaUserPlus } from 'react-icons/fa';

/**
 * Team Management Page Component
 * 
 * Displays and manages teams based on user role:
 * - Team Leaders: Can view and manage their own teams
 * - Administrators: Can view and manage all teams
 */
export default function TeamManagement({ teams, isAdmin }) {
    const { translations } = usePage().props;
    const messages = translations?.messages || {};
    
    const [expandedTeams, setExpandedTeams] = useState({});
    const [editingTeam, setEditingTeam] = useState(null);
    const [deletingTeam, setDeletingTeam] = useState(null);
    const [removingMember, setRemovingMember] = useState(null);
    const [availableAdherents, setAvailableAdherents] = useState([]);
    const [showMemberModal, setShowMemberModal] = useState(false);

    const { data: editData, setData: setEditData, post: postEdit, processing: editProcessing, errors: editErrors, reset: resetEdit } = useForm({
        name: '',
        image: null,
        members: [],
        add_members: [],
        remove_members: [],
    });

    const { data: deleteData, delete: deleteTeam, processing: deleteProcessing } = useForm();
    const { data: removeMemberData, setData: setRemoveMemberData, post: postRemoveMember, processing: removeMemberProcessing } = useForm({
        user_id: null,
    });

    /**
     * Load available adherents on component mount
     */
    useEffect(() => {
        const loadAdherents = async () => {
            try {
                const response = await fetch('/api/users/adherents', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    const data = await response.json();
                    setAvailableAdherents(data);
                }
            } catch (error) {
                console.error('Error loading adherents:', error);
            }
        };
        loadAdherents();
    }, []);

    /**
     * Toggle team details expansion
     */
    const toggleTeamExpansion = (teamId) => {
        setExpandedTeams(prev => ({
            ...prev,
            [teamId]: !prev[teamId]
        }));
    };

    /**
     * Open edit modal for a team
     */
    const openEditModal = (team) => {
        setEditingTeam(team);
        setEditData({
            name: team.name,
            image: null,
            members: team.members || [],
            add_members: [],
            remove_members: [],
        });
    };

    /**
     * Close edit modal
     */
    const closeEditModal = () => {
        setEditingTeam(null);
        setShowMemberModal(false);
        resetEdit();
    };

    /**
     * Add a member to the team
     */
    const addMemberToTeam = (user) => {
        // Check if already in current members or added members
        const alreadyExists = editData.members.some(m => m.id === user.id) || 
                              editData.add_members.some(m => m.id === user.id);
        
        if (alreadyExists) {
            alert('Ce membre fait déjà partie de l\'équipe.');
            setShowMemberModal(false);
            return;
        }

        // Check if user is the team leader
        if (editingTeam && editingTeam.leader.id === user.id) {
            alert('Le chef d\'équipe ne peut pas être ajouté comme membre.');
            setShowMemberModal(false);
            return;
        }

        setEditData('add_members', [...editData.add_members, user]);
        setShowMemberModal(false);
    };

    /**
     * Remove a member from the team (existing member)
     */
    const removeMemberFromTeam = (userId) => {
        // Add to remove list
        setEditData('remove_members', [...editData.remove_members, userId]);
        // Remove from current members display
        setEditData('members', editData.members.filter(m => m.id !== userId));
    };

    /**
     * Cancel adding a new member (remove from add list)
     */
    const cancelAddMember = (userId) => {
        setEditData('add_members', editData.add_members.filter(m => m.id !== userId));
    };

    /**
     * Submit team update
     */
    const handleEditSubmit = (e) => {
        e.preventDefault();
        
        // Prepare data with transformed add_members
        const submitData = {
            name: editData.name,
            image: editData.image,
            add_members: editData.add_members.map(m => typeof m === 'object' ? m.id : m),
            remove_members: editData.remove_members,
        };
        
        router.post(route('teams.update', editingTeam.id), submitData, {
            onSuccess: () => {
                closeEditModal();
            },
            onError: (errors) => {
                console.error('Erreur lors de la mise à jour:', errors);
            },
            preserveScroll: true,
        });
    };

    /**
     * Open delete confirmation modal
     */
    const openDeleteModal = (team) => {
        setDeletingTeam(team);
    };

    /**
     * Close delete modal
     */
    const closeDeleteModal = () => {
        setDeletingTeam(null);
    };

    /**
     * Confirm team deletion
     */
    const handleDeleteConfirm = () => {
        deleteTeam(route('teams.destroy', deletingTeam.id), {
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    };

    /**
     * Open remove member confirmation
     */
    const openRemoveMemberModal = (team, userId) => {
        setRemovingMember({ team, userId });
        setRemoveMemberData({ user_id: userId });
    };

    /**
     * Close remove member modal
     */
    const closeRemoveMemberModal = () => {
        setRemovingMember(null);
    };

    /**
     * Confirm member removal
     */
    const handleRemoveMemberConfirm = () => {
        postRemoveMember(route('teams.removeMember', removingMember.team.id), {
            onSuccess: () => {
                closeRemoveMemberModal();
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {messages.team_management || 'Gestion des équipes'}
                </h2>
            }
        >
            <Head title={messages.team_management || 'Gestion des équipes'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Info banner for team leaders */}
                    {!isAdmin && (
                        <div className="mb-6 rounded-lg bg-blue-50 p-4 border border-blue-200">
                            <p className="text-sm text-blue-700">
                                {messages.team_leader_info || 'Vous gérez vos équipes. Vous pouvez modifier les équipes dont vous êtes le chef.'}
                            </p>
                        </div>
                    )}

                    {/* Admin banner */}
                    {isAdmin && (
                        <div className="mb-6 rounded-lg bg-purple-50 p-4 border border-purple-200">
                            <p className="text-sm text-purple-700">
                                {messages.admin_team_info || 'En tant qu\'administrateur, vous pouvez gérer toutes les équipes.'}
                            </p>
                        </div>
                    )}

                    {/* Teams list */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {teams.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <FaUsers className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        {messages.no_teams || 'Aucune équipe'}
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {messages.no_teams_description || 'Vous n\'avez pas encore créé d\'équipe.'}
                                    </p>
                                    <div className="mt-6">
                                        <Link href={route('teams.create')}>
                                            <PrimaryButton>
                                                {messages.create_team || 'Créer une équipe'}
                                            </PrimaryButton>
                                        </Link>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {teams.data.map((team) => (
                                        <div key={team.id} className="border rounded-lg overflow-hidden">
                                            {/* Team Header */}
                                            <div className="bg-gray-50 p-4 flex items-center justify-between">
                                                <div className="flex items-center space-x-4">
                                                    {team.image && (
                                                        <img
                                                            src={`/storage/${team.image}`}
                                                            alt={team.name}
                                                            className="h-12 w-12 rounded-full object-cover"
                                                        />
                                                    )}
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-gray-900">
                                                            {team.name}
                                                        </h3>
                                                        <p className="text-sm text-gray-600">
                                                            {messages.leader || 'Chef'}: {team.leader.name}
                                                        </p>
                                                        <div className="flex items-center space-x-4 text-sm text-gray-500 mt-1">
                                                            <span>
                                                                <FaUsers className="inline mr-1" />
                                                                {team.members_count} {messages.members || 'membres'}
                                                            </span>
                                                            <span>
                                                                {team.registrations_count} {messages.registrations || 'inscriptions'}
                                                            </span>
                                                            <span>
                                                                {messages.created_on || 'Créée le'}: {team.created_at}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <button
                                                        onClick={() => openEditModal(team)}
                                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition"
                                                        title={messages.edit || 'Modifier'}
                                                    >
                                                        <FaEdit />
                                                    </button>
                                                    <button
                                                        onClick={() => openDeleteModal(team)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-full transition"
                                                        title={messages.delete || 'Supprimer'}
                                                    >
                                                        <FaTrash />
                                                    </button>
                                                    <button
                                                        onClick={() => toggleTeamExpansion(team.id)}
                                                        className="p-2 text-gray-600 hover:bg-gray-100 rounded-full transition"
                                                    >
                                                        {expandedTeams[team.id] ? <FaChevronUp /> : <FaChevronDown />}
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Team Details (Expandable) */}
                                            {expandedTeams[team.id] && (
                                                <div className="p-4 border-t">
                                                    {/* Registrations */}
                                                    {team.registrations.length > 0 && (
                                                        <div className="mb-4">
                                                            <h4 className="font-semibold text-gray-900 mb-2">
                                                                {messages.race_registrations || 'Inscriptions aux courses'}
                                                            </h4>
                                                            <ul className="space-y-2">
                                                                {team.registrations.map((registration) => (
                                                                    <li key={registration.id} className="text-sm text-gray-700 bg-gray-50 p-2 rounded">
                                                                        <strong>{registration.race.raid.name}</strong> - {registration.race.name}
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    )}

                                                    {/* Members would go here if you want to display them */}
                                                    <div className="text-sm text-gray-500">
                                                        {messages.view_team_details || 'Voir les détails de l\'équipe'}: 
                                                        <Link 
                                                            href={route('teams.show', team.id)} 
                                                            className="ml-2 text-blue-600 hover:underline"
                                                        >
                                                            {messages.view_full_details || 'Détails complets'}
                                                        </Link>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}

                                    {/* Pagination */}
                                    {teams.last_page > 1 && (
                                        <div className="mt-6 flex justify-center space-x-2">
                                            {Array.from({ length: teams.last_page }, (_, i) => i + 1).map((page) => (
                                                <button
                                                    key={page}
                                                    onClick={() => router.get(route('teams.management', { page }))}
                                                    className={`px-4 py-2 rounded ${
                                                        page === teams.current_page
                                                            ? 'bg-blue-600 text-white'
                                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                    }`}
                                                >
                                                    {page}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Edit Team Modal */}
            <Modal 
                show={editingTeam !== null} 
                onClose={closeEditModal} 
                maxWidth="2xl"
                closeable={!showMemberModal}
            >
                <form onSubmit={handleEditSubmit} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                        {messages.edit_team || 'Modifier l\'équipe'}
                    </h2>

                    <div className="mb-4">
                        <InputLabel htmlFor="name" value={messages.team_name || 'Nom de l\'équipe'} />
                        <TextInput
                            id="name"
                            type="text"
                            value={editData.name}
                            onChange={(e) => setEditData('name', e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                        <InputError message={editErrors.name} className="mt-2" />
                    </div>

                    <div className="mb-4">
                        <ImageUpload
                            label={messages.team_image || 'Image de l\'équipe'}
                            name="image"
                            currentImage={editingTeam?.image ? `/storage/${editingTeam.image}` : null}
                            onChange={(file) => setEditData('image', file)}
                            error={editErrors.image}
                            helperText={messages.team_image_helper || 'Image de l\'équipe (optionnelle, max 2MB)'}
                            maxSize={2}
                        />
                    </div>

                    {/* Members Management Section */}
                    <div className="mb-4 border-t pt-4">
                        <InputLabel value={messages.team_members || 'Membres de l\'équipe'} />
                        
                        {/* Current Members */}
                        <div className="mt-2 space-y-2">
                            {editData.members.filter(m => !editData.remove_members.includes(m.id)).map((member) => (
                                <div key={member.id} className="flex items-center justify-between bg-gray-50 p-2 rounded">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{member.name}</p>
                                        <p className="text-xs text-gray-500">{member.email}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => removeMemberFromTeam(member.id)}
                                        className="text-red-600 hover:text-red-800 transition"
                                        title={messages.remove_member || 'Retirer'}
                                    >
                                        <FaUserMinus />
                                    </button>
                                </div>
                            ))}

                            {/* Members to be added */}
                            {editData.add_members.map((member) => (
                                <div key={`add-${member.id}`} className="flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{member.name}</p>
                                        <p className="text-xs text-gray-500">{member.email}</p>
                                        <span className="text-xs text-green-600">{messages.will_be_added || 'Sera ajouté'}</span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => cancelAddMember(member.id)}
                                        className="text-red-600 hover:text-red-800 transition"
                                        title={messages.cancel || 'Annuler'}
                                    >
                                        <FaUserMinus />
                                    </button>
                                </div>
                            ))}
                        </div>

                        {/* Add Member Button */}
                        <div className="mt-4">
                            <PrimaryButton
                                type="button"
                                onClick={() => setShowMemberModal(true)}
                                className="w-full flex items-center justify-center space-x-2"
                            >
                                <FaUserPlus />
                                <span>{messages.add_member || 'Ajouter un membre'}</span>
                            </PrimaryButton>
                        </div>
                    </div>

                    <div className="flex items-center justify-end space-x-2 mt-6">
                        <button
                            type="button"
                            onClick={closeEditModal}
                            className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                        >
                            {messages.cancel || 'Annuler'}
                        </button>
                        <PrimaryButton disabled={editProcessing}>
                            {messages.save || 'Enregistrer'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Member Selection Modal */}
            <SelectResponsableModal
                isOpen={showMemberModal}
                onClose={() => setShowMemberModal(false)}
                users={availableAdherents}
                onSelect={addMemberToTeam}
                title={messages.add_member || 'Ajouter un membre'}
            />

            {/* Delete Team Modal */}
            <Modal show={deletingTeam !== null} onClose={closeDeleteModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                        {messages.delete_team || 'Supprimer l\'équipe'}
                    </h2>
                    <p className="text-sm text-gray-600 mb-4">
                        {messages.delete_team_confirmation || 'Êtes-vous sûr de vouloir supprimer cette équipe ? Cette action est irréversible.'}
                    </p>
                    {deletingTeam?.registrations_count > 0 && (
                        <p className="text-sm text-red-600 mb-4">
                            {messages.cannot_delete_team_with_registrations || 'Cette équipe a des inscriptions actives et ne peut pas être supprimée.'}
                        </p>
                    )}
                    <div className="flex items-center justify-end space-x-2">
                        <button
                            type="button"
                            onClick={closeDeleteModal}
                            className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                        >
                            {messages.cancel || 'Annuler'}
                        </button>
                        <DangerButton
                            onClick={handleDeleteConfirm}
                            disabled={deleteProcessing || deletingTeam?.registrations_count > 0}
                        >
                            {messages.delete || 'Supprimer'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
