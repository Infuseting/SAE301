import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import ImageUpload from '@/Components/ImageUpload';

export default function CreateTeam() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        image: null,
        teammates: [],
        join_team: true, // Le créateur participe-t-il à l'équipe ?
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
        
        // Vérifier qu'il y a au moins un participant
        if (!data.join_team && data.teammates.length === 0) {
            alert('L\'équipe doit avoir au moins un participant. Cochez "Je participe" ou ajoutez des coéquipiers.');
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

    const addTeammate = (userId, name, email) => {
        if (currentUser && userId === currentUser.id) {
            alert('Vous ne pouvez pas vous ajouter en tant que coéquipier. Vous êtes déjà le chef de l\'équipe.');
            setShowTeammateDropdown(false);
            setTeammateSearch('');
            return;
        }

        const teammate = { id: userId, name, email };
        if (!data.teammates.some(t => t.id === userId)) {
            setData('teammates', [...data.teammates, teammate]);
        }
        setShowTeammateDropdown(false);
        setTeammateSearch('');
    };

    const removeTeammate = (userId) => {
        setData('teammates', data.teammates.filter(t => t.id !== userId));
    };

    /**
     * Searches for users to add as team members.
     * Makes a debounced API call to fetch matching users.
     * @param {string} searchTerm - The search query (name or email)
     */
    const performTeammateSearch = async (searchTerm) => {
        if (!searchTerm.trim()) {
            setTeammateSearchResults([]);
            return;
        }
        try {
            const response = await fetch(`/api/users/search?q=${encodeURIComponent(searchTerm)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin'
            });
            if (!response.ok) {
                console.error(`Search failed with status ${response.status}`);
                setTeammateSearchResults([]);
                return;
            }
            const users = await response.json();
            setTeammateSearchResults(users);
        } catch (error) {
            console.error('Error searching users:', error);
            setTeammateSearchResults([]);
        }
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
                            <p className="text-xs text-gray-500 mt-1">{data.name.length}/32 caractères</p>
                            <InputError message={errors.name} className="mt-1 text-sm" />
                        </div>
                        {/* Image Upload Field */}
                        <ImageUpload
                            label={messages.team_image || "Logo de l'équipe"}
                            name="image"
                            onChange={(file) => setData('image', file)}
                            error={errors.image}
                            helperText="PNG, JPG jusqu'à 10MB"
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
                                <InputLabel htmlFor="join_team" value="Je participe à cette équipe" />
                                <p className="text-sm text-gray-600 mt-1">
                                    En tant que créateur, vous êtes automatiquement le chef de cette équipe. Cochez cette case si vous souhaitez également participer activement à l'équipe.
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
                                        Ajoutez des coéquipiers pour former votre équipe complète
                                    </p>
                                </div>
                            </div>
                            {/* Teammate Search and Selection */}
                            <div className="relative dropdown-container">
                                <div className="flex flex-col sm:flex-row gap-2">
                                    <div className="flex-1 relative">
                                        <TextInput
                                            value={teammateSearch}
                                            onChange={(e) => setTeammateSearch(e.target.value)}
                                            onFocus={() => setShowTeammateDropdown(true)}
                                            placeholder="Rechercher un coéquipier..."
                                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-transparent transition"
                                        />
                                        {showTeammateDropdown && (
                                            <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                                {teammateSearchResults.length > 0 ? (
                                                    teammateSearchResults.map((person) => (
                                                        <div
                                                            key={person.id}
                                                            className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                                            onClick={() => addTeammate(person.id, person.name, person.email)}
                                                        >
                                                            <div className="font-medium text-gray-900">{person.name}</div>
                                                            <div className="text-sm text-gray-600">{person.email}</div>
                                                        </div>
                                                    ))
                                                ) : teammateSearch ? (
                                                    <div className="px-4 py-3 text-gray-500 text-center">
                                                        Aucun résultat trouvé pour "{teammateSearch}"
                                                    </div>
                                                ) : (
                                                    <div className="px-4 py-3 text-gray-500 text-center">
                                                        Tapez pour rechercher...
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
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
                                Annuler
                            </Link>
                            <PrimaryButton 
                                disabled={processing}
                                className="px-8 py-3"
                                style={{backgroundColor: 'rgb(4, 120, 87)'}}
                            >
                                {processing ? 'Création en cours...' : (messages.create_team || 'Créer l\'équipe')}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
