import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage, Link } from '@inertiajs/react';

/**
 * Create Raid Form Component
 * Form for creating a new raid with event and registration dates
 */
export default function Create() {
    const messages = usePage().props.translations?.messages || {};

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        location: '',
        description: '',
        event_start_date: '',
        event_end_date: '',
        contact: '',
        organizer_id: '',
        club_id: '',
        website_url: '',
        image: null,
    });

    /**
     * Handle form submission
     * @param {Event} e - Form submit event
     */
    const submit = (e) => {
        e.preventDefault();
        post(route('raids.store'));
    };

    /**
     * Handle image upload
     * @param {Event} e - File input change event
     */
    const handleImageUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('image', file);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.new_raid || 'Nouveau raid'} />

            {/* Green Header */}
            <div className="bg-green-500 py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-center relative">
                        <Link href={route('raids.index')} className="text-white hover:text-white/80 absolute left-0">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 className="text-2xl font-bold text-white">
                            {messages.new_raid || 'Nouveau raid'}
                        </h1>
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {/* Left Column - Required Elements */}
                            <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Eléments Obligatoires
                                </h2>

                                {/* Raid Name */}
                                <div>
                                    <InputLabel htmlFor="name" value="Nom du raid" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        name="name"
                                        value={data.name}
                                        className="mt-1 block w-full"
                                        autoComplete="off"
                                        isFocused={true}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Location */}
                                <div>
                                    <InputLabel htmlFor="location" value="Lieu" />
                                    <TextInput
                                        id="location"
                                        type="text"
                                        name="location"
                                        value={data.location}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('location', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.location} className="mt-2" />
                                </div>

                                {/* Description */}
                                <div>
                                    <InputLabel htmlFor="description" value="Description" />
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={6}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        required
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                {/* Date Range */}
                                <div>
                                    <InputLabel value="Date de début et de fin" />
                                    <div className="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <TextInput
                                                type="date"
                                                name="event_start_date"
                                                value={data.event_start_date}
                                                className="block w-full"
                                                onChange={(e) => setData('event_start_date', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.event_start_date} className="mt-2" />
                                        </div>
                                        <div>
                                            <TextInput
                                                type="date"
                                                name="event_end_date"
                                                value={data.event_end_date}
                                                className="block w-full"
                                                onChange={(e) => setData('event_end_date', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.event_end_date} className="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Right Column - Contact & Optional */}
                            <div className="space-y-8">
                                {/* Contact Info */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    {/* Contact */}
                                    <div>
                                        <InputLabel htmlFor="contact" value="Téléphone ou adresse mail de contact" />
                                        <TextInput
                                            id="contact"
                                            type="text"
                                            name="contact"
                                            value={data.contact}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('contact', e.target.value)}
                                            placeholder="email@example.com"
                                            required
                                        />
                                        <InputError message={errors.contact} className="mt-2" />
                                    </div>

                                    {/* Organizer Selection */}
                                    <div>
                                        <InputLabel htmlFor="organizer_id" value="Sélection du responsable" />
                                        <select
                                            id="organizer_id"
                                            name="organizer_id"
                                            value={data.organizer_id}
                                            onChange={(e) => setData('organizer_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        >
                                            <option value="">Sélectionner...</option>
                                            {/* TODO: Load organizers from backend */}
                                        </select>
                                        <InputError message={errors.organizer_id} className="mt-2" />
                                    </div>

                                    {/* Club Selection */}
                                    <div>
                                        <InputLabel htmlFor="club_id" value="Club de rattachement" />
                                        <select
                                            id="club_id"
                                            name="club_id"
                                            value={data.club_id}
                                            onChange={(e) => setData('club_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        >
                                            <option value="">Sélectionner...</option>
                                            {/* TODO: Load clubs from backend */}
                                        </select>
                                        <InputError message={errors.club_id} className="mt-2" />
                                    </div>
                                </div>

                                {/* Optional Elements */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Eléments facultatifs
                                    </h2>

                                    {/* Website URL */}
                                    <div>
                                        <InputLabel htmlFor="website_url" value="Site Web" />
                                        <TextInput
                                            id="website_url"
                                            type="url"
                                            name="website_url"
                                            value={data.website_url}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('website_url', e.target.value)}
                                            placeholder="https://example.com"
                                        />
                                        <InputError message={errors.website_url} className="mt-2" />
                                    </div>

                                    {/* Image Upload */}
                                    <div>
                                        <div className="flex items-center justify-center w-full">
                                            <label className="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg className="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                    <p className="mb-2 text-sm text-gray-500">
                                                        <span className="font-semibold">ajouter une image</span>
                                                    </p>
                                                    <p className="text-xs text-gray-500">PNG, JPG (MAX. 5MB)</p>
                                                </div>
                                                <input
                                                    id="image"
                                                    type="file"
                                                    className="hidden"
                                                    accept="image/*"
                                                    onChange={handleImageUpload}
                                                />
                                            </label>
                                        </div>
                                        {data.image && (
                                            <p className="mt-2 text-sm text-gray-600">
                                                Fichier sélectionné : {data.image.name}
                                            </p>
                                        )}
                                        <InputError message={errors.image} className="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="mt-8 flex justify-center">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-16 py-3 bg-gray-800 text-white font-semibold rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Créer le raid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
