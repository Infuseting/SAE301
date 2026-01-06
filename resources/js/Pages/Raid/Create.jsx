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
        raid_name: '',
        raid_description: '',
        raid_date_start: '',
        raid_date_end: '',
        raid_contact: '',
        raid_street: '',
        raid_city: '',
        raid_postal_code: '',
        raid_number: '',
        adh_id: '',
        clu_id: '',
        ins_id: '',
        raid_site_url: '',
        raid_image: null,
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
            setData('raid_image', file);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.new_raid || 'Nouveau raid'} />

            {/* Green Header */}
            <div className="bg-green-500 py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-center relative">
                        <Link href={route('home')} className="text-white hover:text-white/80 absolute left-0">
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
                                    <InputLabel htmlFor="raid_name" value="Nom du raid" />
                                    <TextInput
                                        id="raid_name"
                                        type="text"
                                        name="raid_name"
                                        value={data.raid_name}
                                        className="mt-1 block w-full"
                                        autoComplete="off"
                                        isFocused={true}
                                        onChange={(e) => setData('raid_name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.raid_name} className="mt-2" />
                                </div>

                                {/* Description */}
                                <div>
                                    <InputLabel htmlFor="raid_description" value="Description" />
                                    <textarea
                                        id="raid_description"
                                        name="raid_description"
                                        value={data.raid_description}
                                        onChange={(e) => setData('raid_description', e.target.value)}
                                        rows={6}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        required
                                    />
                                    <InputError message={errors.raid_description} className="mt-2" />
                                </div>

                                {/* Date Range */}
                                <div>
                                    <InputLabel value="Date de début et de fin" />
                                    <div className="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <TextInput
                                                type="datetime-local"
                                                name="raid_date_start"
                                                value={data.raid_date_start}
                                                className="block w-full"
                                                onChange={(e) => setData('raid_date_start', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.raid_date_start} className="mt-2" />
                                        </div>
                                        <div>
                                            <TextInput
                                                type="datetime-local"
                                                name="raid_date_end"
                                                value={data.raid_date_end}
                                                className="block w-full"
                                                onChange={(e) => setData('raid_date_end', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.raid_date_end} className="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                {/* Address Fields */}
                                <div>
                                    <InputLabel htmlFor="raid_street" value="Rue" />
                                    <TextInput
                                        id="raid_street"
                                        type="text"
                                        name="raid_street"
                                        value={data.raid_street}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('raid_street', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.raid_street} className="mt-2" />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="raid_city" value="Ville" />
                                        <TextInput
                                            id="raid_city"
                                            type="text"
                                            name="raid_city"
                                            value={data.raid_city}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_city', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.raid_city} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="raid_postal_code" value="Code postal" />
                                        <TextInput
                                            id="raid_postal_code"
                                            type="text"
                                            name="raid_postal_code"
                                            value={data.raid_postal_code}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_postal_code', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.raid_postal_code} className="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel htmlFor="raid_number" value="Numéro" />
                                    <TextInput
                                        id="raid_number"
                                        type="number"
                                        name="raid_number"
                                        value={data.raid_number}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('raid_number', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.raid_number} className="mt-2" />
                                </div>
                            </div>

                            {/* Right Column - Contact & Optional */}
                            <div className="space-y-8">
                                {/* Contact Info */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                        Contact et Organisation
                                    </h2>

                                    {/* Contact */}
                                    <div>
                                        <InputLabel htmlFor="raid_contact" value="Téléphone ou adresse mail de contact" />
                                        <TextInput
                                            id="raid_contact"
                                            type="text"
                                            name="raid_contact"
                                            value={data.raid_contact}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_contact', e.target.value)}
                                            placeholder="email@example.com"
                                            required
                                        />
                                        <InputError message={errors.raid_contact} className="mt-2" />
                                    </div>

                                    {/* Organizer Selection */}
                                    <div>
                                        <InputLabel htmlFor="adh_id" value="Sélection du responsable" />
                                        <select
                                            id="adh_id"
                                            name="adh_id"
                                            value={data.adh_id}
                                            onChange={(e) => setData('adh_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                            required
                                        >
                                            <option value="">Sélectionner...</option>
                                            {/* TODO: Load organizers from backend */}
                                        </select>
                                        <InputError message={errors.adh_id} className="mt-2" />
                                    </div>

                                    {/* Club Selection */}
                                    <div>
                                        <InputLabel htmlFor="clu_id" value="Club de rattachement" />
                                        <select
                                            id="clu_id"
                                            name="clu_id"
                                            value={data.clu_id}
                                            onChange={(e) => setData('clu_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                            required
                                        >
                                            <option value="">Sélectionner...</option>
                                            {/* TODO: Load clubs from backend */}
                                        </select>
                                        <InputError message={errors.clu_id} className="mt-2" />
                                    </div>

                                    {/* Registration Period Selection */}
                                    <div>
                                        <InputLabel htmlFor="ins_id" value="Période d'inscription" />
                                        <select
                                            id="ins_id"
                                            name="ins_id"
                                            value={data.ins_id}
                                            onChange={(e) => setData('ins_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                            required
                                        >
                                            <option value="">Sélectionner...</option>
                                            {/* TODO: Load registration periods from backend */}
                                        </select>
                                        <InputError message={errors.ins_id} className="mt-2" />
                                    </div>
                                </div>

                                {/* Optional Elements */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Eléments facultatifs
                                    </h2>

                                    {/* Website URL */}
                                    <div>
                                        <InputLabel htmlFor="raid_site_url" value="Site Web" />
                                        <TextInput
                                            id="raid_site_url"
                                            type="url"
                                            name="raid_site_url"
                                            value={data.raid_site_url}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_site_url', e.target.value)}
                                            placeholder="https://example.com"
                                        />
                                        <InputError message={errors.raid_site_url} className="mt-2" />
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
                                                    id="raid_image"
                                                    type="file"
                                                    className="hidden"
                                                    accept="image/*"
                                                    onChange={handleImageUpload}
                                                />
                                            </label>
                                        </div>
                                        {data.raid_image && (
                                            <p className="mt-2 text-sm text-gray-600">
                                                Fichier sélectionné : {data.raid_image.name}
                                            </p>
                                        )}
                                        <InputError message={errors.raid_image} className="mt-2" />
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
