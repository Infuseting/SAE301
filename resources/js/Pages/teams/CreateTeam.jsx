import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CreateTeam() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        image: null,
        leader_id: '',
    });

    const [showModal, setShowModal] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [selectedLeader, setSelectedLeader] = useState(null);

    const messages = usePage().props.translations?.messages || {};

    const submit = (e) => {
        e.preventDefault();
        post(route('team.store'));
    };

    const handleSearch = async () => {
        if (!searchQuery.trim()) return;
        try {
            const response = await fetch(`/api/users/search?q=${encodeURIComponent(searchQuery)}`, {
                headers: {
                    'Authorization': `Bearer ${window.Laravel.apiToken}`, // Assuming token is set
                    'Accept': 'application/json',
                },
            });
            const results = await response.json();
            setSearchResults(results);
        } catch (error) {
            console.error('Search failed:', error);
        }
    };

    const selectLeader = (user) => {
        setSelectedLeader(user);
        setData('leader_id', user.adh_id);
        setShowModal(false);
        setSearchQuery('');
        setSearchResults([]);
    };

    return (
        <div className="w-4/5 mx-auto">
            <Head title={messages.create_team || 'Créer une équipe'} />
            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
                    {messages.create_team || 'Créer une équipe'}
                </h2>
                <p className="mt-2 text-sm text-gray-600">
                    {messages.create_team_subtext || 'Créez une nouvelle équipe pour participer aux événements d\'orientation.'}
                </p>
            </div>
            <form onSubmit={submit} className="space-y-6">
                <div>
                    <div className="flex items-center space-x-4">
                        <InputLabel htmlFor="name" value={messages.team_name || 'Nom de l\'équipe'} />
                        <TextInput
                            id="name"
                            type="text"
                            name="name"
                            value={data.name}
                            className="mt-1 block w-full"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                    </div>
                    <InputError message={errors.name} className="mt-2" />
                </div>
                <div>
                    <div className="flex items-center space-x-4">
                        <InputLabel htmlFor="image" value={messages.team_image || 'Image'} />
                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/*"
                            onChange={(e) => setData('image', e.target.files[0])}
                            className="mt-1 block w-full"
                        />
                    </div>
                    <InputError message={errors.image} className="mt-2" />
                </div>
                <div>
                    <div className="flex items-center space-x-4">
                        <InputLabel value={messages.team_leader || 'Chef d\'équipe'} />
                        {selectedLeader ? (
                            <div className="mt-1 p-2 border rounded">
                                {selectedLeader.first_name} {selectedLeader.last_name} ({selectedLeader.email})
                                <button
                                    type="button"
                                    onClick={() => { setSelectedLeader(null); setData('leader_id', ''); }}
                                    className="ml-2 text-red-500"
                                >
                                    Supprimer
                                </button>
                            </div>
                        ) : (
                            <SecondaryButton type="button" onClick={() => setShowModal(true)}>
                                {messages.select_leader || 'Sélectionner un chef d\'équipe'}
                            </SecondaryButton>
                        )}
                    </div>
                    <InputError message={errors.leader_id} className="mt-2" />
                </div>
                <div className="flex items-center justify-end">
                    <PrimaryButton className="ml-4" disabled={processing}>
                        {messages.create_team || 'Créer l\'équipe'}
                    </PrimaryButton>
                </div>
            </form>

            <Modal show={showModal} onClose={() => setShowModal(false)}>
                <div className="p-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                        {messages.select_leader || 'Sélectionner un chef d\'équipe'}
                    </h3>
                    <div className="flex space-x-2 mb-4">
                        <TextInput
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder={messages.search_placeholder || 'Rechercher par nom, email ou téléphone'}
                            className="flex-1"
                        />
                        <PrimaryButton onClick={handleSearch}>
                            {messages.search || 'Rechercher'}
                        </PrimaryButton>
                    </div>
                    <div className="max-h-60 overflow-y-auto">
                        {searchResults.map((user) => (
                            <div
                                key={user.id}
                                className="p-2 border-b cursor-pointer hover:bg-gray-100"
                                onClick={() => selectLeader(user)}
                            >
                                {user.first_name} {user.last_name} - {user.email} - {user.phone}
                            </div>
                        ))}
                    </div>
                </div>
            </Modal>
        </div>
    );
}
