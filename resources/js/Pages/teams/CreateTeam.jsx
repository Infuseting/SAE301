import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import Modal from '@/Components/Modal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CreateTeam() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        image: null,
        teammates: [],
        join_team: false,
    });
    const [showModal, setShowModal] = useState(false);
    const [search, setSearch] = useState('');
    const [imagePreview, setImagePreview] = useState(null);
    const messages = usePage().props.translations?.messages || {};

    const submit = (e) => {
        e.preventDefault();
        post(route('team.store'));
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
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
        setShowModal(false);
        setSearch('');
    };

    const removeTeammate = (userId) => {
        setData('teammates', data.teammates.filter(t => t.id !== userId));
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
                                            <span className="text-sm font-medium text-gray-700">
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
                                <SecondaryButton 
                                    type="button" 
                                    onClick={() => setShowModal(true)}
                                    className="flex items-center gap-2"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    {messages.add_teammate || 'Ajouter un coéquipier'}
                                </SecondaryButton>
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

            {/* Modal for Teammate Selection */}
            <Modal show={showModal} onClose={() => setShowModal(false)}>
                <div className="p-6 sm:p-8 max-w-2xl">
                    <div className="flex items-center justify-between mb-6">
                        <h3 className="text-xl sm:text-2xl font-bold text-gray-900">
                            {messages.add_teammate || 'Ajouter un coéquipier'}
                        </h3>
                        <button
                            onClick={() => setShowModal(false)}
                            className="text-gray-400 hover:text-gray-600 transition"
                        >
                            <span className="sr-only">Fermer</span>
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Search Field */}
                    <div className="mb-6 space-y-2">
                        <InputLabel htmlFor="search" value="Rechercher une personne" />
                        <div className="flex flex-col sm:flex-row gap-2">
                            <TextInput
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Nom, email ou téléphone..."
                                className="flex-1 px-4 py-3"
                            />
                            <PrimaryButton className="w-full sm:w-auto" style={{backgroundColor: 'rgb(4, 120, 87)'}}>
                                {messages.search || 'Rechercher'}
                            </PrimaryButton>
                        </div>
                    </div>

                    {/* Results Area */}
                    <div className="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto border" style={{borderColor: 'rgba(4, 120, 87, 0.2)'}}>
                        <p className="text-center text-gray-500 py-8">
                            Aucun résultat pour le moment. Commencez une recherche.
                        </p>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
