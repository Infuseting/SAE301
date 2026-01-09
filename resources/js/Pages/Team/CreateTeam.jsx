import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InviteUserModal from '@/Components/Team/InviteUserModal';
import InviteByEmailModal from '@/Components/Team/InviteByEmailModal';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import ImageUpload from '@/Components/ImageUpload';

export default function CreateTeam() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        image: null,
        teammates: [],
        emailInvites: [],
        join_team: true,
    });
    const [selectedLeader, setSelectedLeader] = useState(null);
    const [showLeaderDropdown, setShowLeaderDropdown] = useState(false);
    const [showTeammateDropdown, setShowTeammateDropdown] = useState(false);
    const [teammateSearch, setTeammateSearch] = useState('');
    const [teammateSearchResults, setTeammateSearchResults] = useState([]);
    const { auth, translations } = usePage().props;
    const currentUser = auth?.user;
    const messages = translations?.messages || {};
    const [redirectUri, setRedirectUri] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [showInviteModal, setShowInviteModal] = useState(false);
    const [showEmailModal, setShowEmailModal] = useState(false);

    // evite trop d'appels (300ms)
    useEffect(() => {
        if (!teammateSearch.trim()) {
            setTeammateSearchResults([]);
            return;
        }
        const timeoutId = setTimeout(() => {
            performTeammateSearch(teammateSearch);
        }, 300); 
        return () => clearTimeout(timeoutId);
    }, [teammateSearch]);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (!event.target.closest('.dropdown-container')) {
                setShowTeammateDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    // Read optional redirect_uri query parameter so we can redirect
    // back to the race/course flow after creating a team.
    useEffect(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const uri = params.get('redirect_uri');
            if (uri) setRedirectUri(uri);
        } catch (e) {
            // ignore malformed URLSearchParams in older browsers
        }
    }, []);

    const submit = (e) => {
        e.preventDefault();
        if (!data.join_team && data.teammates.length === 0 && data.emailInvites.length === 0) {
            alert(messages['team.create.at_least_one_participant'] || 'L\'équipe doit avoir au moins un participant.');
            return;
        }
        
        post(route('team.store'), {
            onSuccess: () => {
                if (redirectUri) {
                    // If a redirect URI was provided, go back to that flow.
                    window.location.href = redirectUri;
                }
            },
        });
    };

    const handleImageChange = (e) => {
        const Label = document.getElementById('download_label');
        const file = e.target.files[0];
        if (file) {
            Label.textContent = messages['team.create.click_change_image'] || "Cliquez pour changer l'image";
            setData('image', file);
            const reader = new FileReader();
            reader.onload = (event) => {
                setImagePreview(event.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const addTeammate = (user) => {
        if (currentUser && user.id === currentUser.id) {
            alert(messages['team.create.cannot_add_yourself'] || 'Vous ne pouvez pas vous ajouter en tant que coéquipier.');
            return;
        }
        if (!data.teammates.some(t => t.id === user.id)) {
            setData('teammates', [...data.teammates, { id: user.id, name: user.name, email: user.email }]);
        }
    };

    const removeTeammate = (userId) => {
        setData('teammates', data.teammates.filter(t => t.id !== userId));
    };

    const addEmailInvite = (email) => {
        if (!data.emailInvites.includes(email)) {
            setData('emailInvites', [...data.emailInvites, email]);
        }
    };

    const removeEmailInvite = (email) => {
        setData('emailInvites', data.emailInvites.filter(e => e !== email));
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.create_team || 'Créer une équipe'} />
            {/* Hero Section */}
            <div className="py-12 sm:py-16" style={{backgroundColor: 'rgb(4, 120, 87)'}}>
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl sm:text-4xl font-bold text-white">
                        {messages.create_team || 'Créer une équipe'}
                    </h1>
                    <p className="mt-2 sm:mt-4 text-base sm:text-lg" style={{color: 'rgba(255, 255, 255, 0.9)'}}>
                        {messages.create_team_subtext || 'Créez une nouvelle équipe pour participer aux événements d\'orientation.'}
                    </p>
                </div>
            </div>
            {/* Main Content */}
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
                <div className="bg-white rounded-xl shadow-md overflow-hidden">
                    <form onSubmit={submit} className="p-6 sm:p-8 space-y-8">
                        {/* Team Name Field */}
                        <div className="space-y-2">
                            <InputLabel htmlFor="name" value={messages.team_name || 'Nom de l\'équipe'} />
                            <TextInput
                                id="name"
                                type="text"
                                name="name"
                                value={data.name}
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-transparent transition"
                                placeholder="Ex: Les Aventuriers"
                                style={{focusRing: 'rgb(4, 120, 87)'}}
                                isFocused={true}
                                onChange={(e) => setData('name', e.target.value)}
                                maxLength={32}
                                required
                            />
                            <p className="text-xs text-gray-500 mt-1">{data.name.length}/32 {messages['characters'] || 'caractères'}</p>
                            <InputError message={errors.name} className="mt-1 text-sm" />
                        </div>
                        {/* Image Upload Field */}
                        <ImageUpload
                            label={messages.team_image || "Logo de l'équipe"}
                            name="image"
                            onChange={(file) => setData('image', file)}
                            error={errors.image}
                            helperText={messages['team.create.image_format'] || "PNG, JPG jusqu'à 10MB"}
                        />

                        {/* Join Team Checkbox */}
                        <div className="flex items-start space-x-3 p-4 rounded-lg" style={{backgroundColor: 'rgba(4, 120, 87, 0.05)'}}>
                            <Checkbox
                                id="join_team"
                                name="join_team"
                                checked={data.join_team}
                                onChange={(e) => setData('join_team', e.target.checked)}
                            />
                            <div>
                                <InputLabel htmlFor="join_team" value={messages['team.create.i_participate'] || "Je participe à cette équipe"} />
                                <p className="text-sm text-gray-600 mt-1">
                                    {messages['team.create.creator_leader_hint'] || "En tant que créateur, vous êtes automatiquement le chef de cette équipe. Cochez cette case si vous souhaitez également participer activement à l'équipe."}
                                </p>
                            </div>
                        </div>
                        {/* Teammates Section */}
                        <div className="space-y-4 border-t border-gray-200 pt-8">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        {messages.teammates || 'Coéquipiers'}
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">
                                        {messages['team.create.add_teammates_hint'] || "Ajoutez des coéquipiers pour former votre équipe complète"}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setShowInviteModal(true)}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    {messages['team.create.add_members'] || "Ajouter des membres"}
                                </button>
                            </div>
                            {/* Teammates List */}
                            <div className="space-y-2">
                                {data.teammates.length === 0 ? (
                                    <div className="px-4 py-8 bg-gray-50 rounded-lg text-center">
                                        <p className="text-gray-500">
                                            {messages.no_teammates_added || 'Aucun coéquipier ajouté pour le moment'}
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {data.teammates.map((teammate, index) => (
                                            <div
                                                key={teammate.id}
                                                className="flex items-center justify-between p-4 border rounded-lg hover:opacity-90 transition"
                                                style={{backgroundColor: 'rgba(4, 120, 87, 0.08)', borderColor: 'rgba(4, 120, 87, 0.3)'}}
                                            >
                                                <div className="flex-1">
                                                    <p className="font-medium text-gray-900">{teammate.name}</p>
                                                    <p className="text-sm text-gray-600">{teammate.email}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeTeammate(teammate.id)}
                                                    className="ml-4 px-3 py-1 text-sm rounded transition"
                                                    style={{color: 'rgb(220, 38, 38)', backgroundColor: 'rgba(220, 38, 38, 0.1)'}}
                                                    onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'rgba(220, 38, 38, 0.2)'}
                                                    onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'rgba(220, 38, 38, 0.1)'}
                                                >
                                                    {messages.remove_teammate || 'Supprimer'}
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                            <InputError message={errors.teammates} className="mt-2" />

                            {/* Email Invites List */}
                            {data.emailInvites.length > 0 && (
                                <div className="space-y-2 mt-4">
                                    <h4 className="text-sm font-medium text-gray-700">{messages['team.create.email_invites'] || "Invitations par email"}</h4>
                                    {data.emailInvites.map((email, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between p-4 border rounded-lg"
                                            style={{backgroundColor: 'rgba(34, 197, 94, 0.08)', borderColor: 'rgba(34, 197, 94, 0.3)'}}
                                        >
                                            <div className="flex items-center gap-3">
                                                <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span className="text-gray-900">{email}</span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeEmailInvite(email)}
                                                className="px-3 py-1 text-sm rounded transition"
                                                style={{color: 'rgb(220, 38, 38)', backgroundColor: 'rgba(220, 38, 38, 0.1)'}}
                                            >
                                                {messages['remove'] || "Retirer"}
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <InputError message={errors.emailInvites} className="mt-2" />
                        </div>
                        {/* Submit Button */}
                        <div className="pt-6 border-t border-gray-200 flex gap-3 justify-end">
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium transition"
                                style={{}} 
                                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f3f4f6'}
                                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                            >
                                {messages['cancel'] || "Annuler"}
                            </Link>
                            <PrimaryButton 
                                disabled={processing}
                                className="px-8 py-3"
                                style={{backgroundColor: 'rgb(4, 120, 87)'}}
                            >
                                {processing ? (messages['team.create.creating'] || 'Création en cours...') : (messages.create_team || 'Créer l\'équipe')}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            {/* Invite User Modal */}
            <InviteUserModal
                isOpen={showInviteModal}
                onClose={() => setShowInviteModal(false)}
                teamMembers={data.teammates}
                auth={{ user: currentUser }}
                onEmailInviteOpen={() => {
                    setShowInviteModal(false);
                    setShowEmailModal(true);
                }}
                onSelect={addTeammate}
            />

            {/* Invite by Email Modal */}
            <InviteByEmailModal
                isOpen={showEmailModal}
                onClose={() => setShowEmailModal(false)}
                onEmailAdd={addEmailInvite}
            />
        </AuthenticatedLayout>
    );
}
