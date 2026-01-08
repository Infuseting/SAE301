import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function CreateTeam() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        image: null,
        leader_id: '',
        teammates: [],
        join_team: false,
    });
    const [imagePreview, setImagePreview] = useState(null);
    const [selectedLeader, setSelectedLeader] = useState(null);
    const [showLeaderDropdown, setShowLeaderDropdown] = useState(false);
    const [showTeammateDropdown, setShowTeammateDropdown] = useState(false);
    const [leaderSearch, setLeaderSearch] = useState('');
    const [teammateSearch, setTeammateSearch] = useState('');
    const [leaderSearchResults, setLeaderSearchResults] = useState([]);
    const [teammateSearchResults, setTeammateSearchResults] = useState([]);
    const messages = usePage().props.translations?.messages || {};

    // Debounced search effects
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            performLeaderSearch(leaderSearch);
        }, 300); // 300ms debounce

        return () => clearTimeout(timeoutId);
    }, [leaderSearch]);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            performTeammateSearch(teammateSearch);
        }, 300); // 300ms debounce

        return () => clearTimeout(timeoutId);
    }, [teammateSearch]);

    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (!event.target.closest('.dropdown-container')) {
                setShowLeaderDropdown(false);
                setShowTeammateDropdown(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('team.store'));
    };

    const handleImageChange = (e) => {
        const Label = document.getElementById('download_label');
        const file = e.target.files[0];
        if (file) {
            Label.textContent = "Cliquez pour changer l'image";
            setData('image', file);
            const reader = new FileReader();
            reader.onload = (event) => {
                setImagePreview(event.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const addTeammate = (userId, name, email) => {
        const teammate = { id: userId, name, email };
        if (!data.teammates.some(t => t.id === userId)) {
            setData('teammates', [...data.teammates, teammate]);
        }
        setShowTeammateDropdown(false);
        setTeammateSearch('');
    };

    const selectLeader = (userId, name, email) => {
        const leader = { id: userId, name, email };
        setSelectedLeader(leader);
        setData('leader_id', userId);
        setShowLeaderDropdown(false);
        setLeaderSearch('');
    };

    const removeTeammate = (userId) => {
        setData('teammates', data.teammates.filter(t => t.id !== userId));
    };

    const removeLeader = () => {
        setSelectedLeader(null);
        setData('leader_id', '');
        setLeaderSearch('');
        setShowLeaderDropdown(false);
    };

    /**
     * Searches for users to assign as team leader.
     * Makes a debounced API call to fetch matching users.
     * 
     * @param {string} searchTerm - The search query (name or email)
     */
    const performLeaderSearch = async (searchTerm) => {
        if (!searchTerm.trim()) {
            setLeaderSearchResults([]);
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
                setLeaderSearchResults([]);
                return;
            }

            const users = await response.json();
            setLeaderSearchResults(users);
        } catch (error) {
            console.error('Error searching users:', error);
            setLeaderSearchResults([]);
        }
    };

    /**
     * Searches for users to add as team members.
     * Makes a debounced API call to fetch matching users.
     * 
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
                                required
                            />
                            <InputError message={errors.name} className="mt-1 text-sm" />
                        </div>

                        {/* Image Upload Field */}
                        <div className="space-y-3">
                            <InputLabel htmlFor="image" value={messages.team_image || 'Logo de l\'équipe'} />
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                {/* Upload Area */}
                                <div className="sm:col-span-2">
                                    <div className="relative">
                                        <input
                                            type="file"
                                            id="image"
                                            name="image"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            className="hidden"
                                        />
                                        <label
                                            htmlFor="image"
                                            className="flex flex-col items-center justify-center w-full px-6 py-8 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer transition"
                                            style={{borderColor: 'rgb(4, 120, 87)'}}
                                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'rgba(4, 120, 87, 0.05)'}
                                            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                                        >
                                            <svg className="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span className="text-sm font-medium text-gray-700" id="download_label">
                                                Cliquez pour télécharger
                                            </span>
                                            <span className="text-xs text-gray-500 mt-1">
                                                PNG, JPG jusqu'à 10MB
                                            </span>
                                        </label>
                                    </div>
                                    <InputError message={errors.image} className="mt-2" />
                                </div>

                                {/* Image Preview */}
                                {imagePreview && (
                                    <div className="flex items-center justify-center">
                                        <div className="relative w-full aspect-square">
                                            <img
                                                src={imagePreview}
                                                alt="Aperçu"
                                                className="w-full h-full object-cover rounded-lg"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Checkbox Field */}
                        <div className="flex items-start space-x-3 p-4 rounded-lg" style={{backgroundColor: 'rgba(4, 120, 87, 0.05)'}}>
                            <Checkbox
                                id="join_team"
                                name="join_team"
                                checked={data.join_team}
                                onChange={(e) => setData('join_team', e.target.checked)}
                            />
                            <div>
                                <InputLabel htmlFor="join_team" value="Voulez-vous être membre de cette équipe ?" />
                                <p className="text-sm text-gray-600 mt-1">
                                    Cochez cette case pour rejoindre automatiquement l'équipe que vous créez.
                                </p>
                            </div>
                        </div>

                        {/* Team Leader Selection */}
                        <div className="space-y-4 border-t border-gray-200 pt-8">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        {messages.team_leader || 'Chef de l\'équipe'}
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">
                                        {messages.team_leader_description || 'Sélectionnez le chef de votre équipe'}
                                    </p>
                                </div>
                            </div>

                            {/* Leader Search and Selection */}
                            {!selectedLeader && (
                                <div className="relative dropdown-container">
                                    <div className="flex flex-col sm:flex-row gap-2">
                                        <div className="flex-1 relative">
                                            <TextInput
                                                value={leaderSearch}
                                                onChange={(e) => setLeaderSearch(e.target.value)}
                                                onFocus={() => setShowLeaderDropdown(true)}
                                                placeholder="Rechercher un chef d'équipe..."
                                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-transparent transition"
                                            />
                                            {showLeaderDropdown && (
                                                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                                    {leaderSearchResults.length > 0 ? (
                                                        leaderSearchResults.map((person) => (
                                                            <div
                                                                key={person.id}
                                                                className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                                                onClick={() => selectLeader(person.id, person.name, person.email)}
                                                            >
                                                                <div className="font-medium text-gray-900">{person.name}</div>
                                                                <div className="text-sm text-gray-600">{person.email}</div>
                                                            </div>
                                                        ))
                                                    ) : leaderSearch ? (
                                                        <div className="px-4 py-3 text-gray-500 text-center">
                                                            Aucun résultat trouvé pour "{leaderSearch}"
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
                            )}

                            {/* Leader Display */}
                            <div className="space-y-2">
                                {selectedLeader ? (
                                    <div
                                        className="flex items-center justify-between p-4 border rounded-lg hover:opacity-90 transition"
                                        style={{backgroundColor: 'rgba(4, 120, 87, 0.08)', borderColor: 'rgba(4, 120, 87, 0.3)'}}
                                    >
                                        <div className="flex-1">
                                            <p className="font-medium text-gray-900">{selectedLeader.name}</p>
                                            <p className="text-sm text-gray-600">{selectedLeader.email}</p>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Chef d'équipe
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={removeLeader}
                                            className="ml-4 px-3 py-1 text-sm rounded transition"
                                            style={{color: 'rgb(220, 38, 38)', backgroundColor: 'rgba(220, 38, 38, 0.1)'}}
                                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'rgba(220, 38, 38, 0.2)'}
                                            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'rgba(220, 38, 38, 0.1)'}
                                        >
                                            {messages.remove_leader || 'Supprimer'}
                                        </button>
                                    </div>
                                ) : (
                                    <div className="px-4 py-8 bg-gray-50 rounded-lg text-center">
                                        <p className="text-gray-500">
                                            {messages.no_leader_selected || 'Aucun chef sélectionné'}
                                        </p>
                                    </div>
                                )}
                            </div>
                            <InputError message={errors.leader_id} className="mt-2" />
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
