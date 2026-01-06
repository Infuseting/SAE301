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
        leader_id: '',
        join_team: false,
    });
    const [showModal, setShowModal] = useState(false);
    const [search, setSearch] = useState('');
    const messages = usePage().props.translations?.messages || {};
    const submit = (e) => {
        e.preventDefault();
        post(route('team.store'));
    };

    return (
        <AuthenticatedLayout>
        <div className="w-4/5 mx-auto bg-white p-5 shadow-sm border border-gray-200 sm:rounded-lg mt-10">
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
                        <Checkbox
                            id="join_team"
                            name="join_team"
                        />
                        <InputLabel htmlFor="join_team" value="Voulez-vous être dans l'équipe ?" />
                    </div>
                </div>
                <div>
                    <div className="flex items-center space-x-4">
                        <InputLabel value={messages.team_composed || 'Equipe : '} />
                        <SecondaryButton type="button" onClick={() => setShowModal(true)}>
                            {messages.select_leader || 'Sélectionner une personne'}
                        </SecondaryButton>
                    </div>
                    <InputError message={errors.leader_id} className="mt-2" />
                </div>
                <div className="flex items-center justify-center mt-4">
                    <PrimaryButton className="ml-4" disabled={processing}>
                        {messages.create_team || 'Créer l\'équipe'}
                    </PrimaryButton>
                </div>
            </form>

            <Modal show={showModal} onClose={() => setShowModal(false)}>
                <div className="p-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                        {messages.select_mate || 'Sélectionner une personne'}
                    </h3>
                    <div className="flex space-x-2 mb-4">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={messages.search_placeholder || 'Rechercher par nom, email ou téléphone'}
                            className="flex-1"
                        />
                        <PrimaryButton >
                            {messages.search || 'Rechercher'}
                        </PrimaryButton>
                    </div>
                    <div className="max-h-60 overflow-y-auto">
                    </div>
                </div>
            </Modal>
        </div>
        </AuthenticatedLayout>
    );
}
