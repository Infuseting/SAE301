import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import UserSelect from '@/Components/UserSelect';
import ImageUpload from '@/Components/ImageUpload';

/**
 * Create Raid Form Component
 * Form for creating a new raid with event and registration dates
 */
export default function Create() {
    const { userClub, clubMembers } = usePage().props;
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
        adh_id: '',
        ins_start_date: '',
        ins_end_date: '',
        raid_site_url: '',
        raid_image: null,
    });

    /**
     * Handle form submission
     * @param {Event} e - Form submit event
     */
    const submit = (e) => {
        e.preventDefault();
        post(route('raids.store'), {
            forceFormData: true,
        });
    };

    /**
     * Calculate max date for inscription (raid start date - 1 day)
     * @returns {string} ISO datetime string for max inscription date
     */
    const getMaxInscriptionDate = () => {
        if (!data.raid_date_start) return undefined;

        const raidStart = new Date(data.raid_date_start);
        raidStart.setDate(raidStart.getDate() - 1);

        // Format to datetime-local format (YYYY-MM-DDTHH:mm)
        const year = raidStart.getFullYear();
        const month = String(raidStart.getMonth() + 1).padStart(2, '0');
        const day = String(raidStart.getDate()).padStart(2, '0');
        const hours = String(raidStart.getHours()).padStart(2, '0');
        const minutes = String(raidStart.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
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
                                    Informations générales
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
                                    <InputLabel value="Date du raid" />
                                    <div className="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <label className="text-sm text-gray-600">Début</label>
                                            <TextInput
                                                type="datetime-local"
                                                name="raid_date_start"
                                                value={data.raid_date_start}
                                                className="block w-full mt-1"
                                                onChange={(e) => setData('raid_date_start', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.raid_date_start} className="mt-2" />
                                        </div>
                                        <div>
                                            <label className="text-sm text-gray-600">Fin</label>
                                            <TextInput
                                                type="datetime-local"
                                                name="raid_date_end"
                                                value={data.raid_date_end}
                                                className="block w-full mt-1"
                                                onChange={(e) => setData('raid_date_end', e.target.value)}
                                                min={data.raid_date_start || undefined}
                                                required
                                            />
                                            <InputError message={errors.raid_date_end} className="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                {/* Registration Period */}
                                <div>
                                    <InputLabel value="Période d'inscription" />
                                    <div className="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <label className="text-sm text-gray-600">Début des inscriptions</label>
                                            <TextInput
                                                type="datetime-local"
                                                name="ins_start_date"
                                                value={data.ins_start_date}
                                                className="block w-full mt-1"
                                                onChange={(e) => setData('ins_start_date', e.target.value)}
                                                max={getMaxInscriptionDate()}
                                                required
                                            />
                                            <InputError message={errors.ins_start_date} className="mt-2" />
                                        </div>
                                        <div>
                                            <label className="text-sm text-gray-600">Fin des inscriptions</label>
                                            <TextInput
                                                type="datetime-local"
                                                name="ins_end_date"
                                                value={data.ins_end_date}
                                                className="block w-full mt-1"
                                                onChange={(e) => setData('ins_end_date', e.target.value)}
                                                min={data.ins_start_date || undefined}
                                                max={getMaxInscriptionDate()}
                                                required
                                            />
                                            <InputError message={errors.ins_end_date} className="mt-2" />
                                        </div>
                                    </div>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Les inscriptions doivent se terminer au plus tard la veille du début du raid
                                    </p>
                                </div>

                                {/* Lieu */}
                                <div>
                                    <InputLabel value="Lieu" />
                                    <div className="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <InputLabel htmlFor="raid_city" value="Ville *" />
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
                                            <InputLabel htmlFor="raid_postal_code" value="Code postal *" />
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
                                    <div className="mt-4">
                                        <InputLabel htmlFor="raid_street" value="Rue (optionnel)" />
                                        <TextInput
                                            id="raid_street"
                                            type="text"
                                            name="raid_street"
                                            value={data.raid_street}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_street', e.target.value)}
                                            placeholder="Adresse du point de rendez-vous"
                                        />
                                        <InputError message={errors.raid_street} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Right Column - Contact & Optional */}
                            <div className="space-y-8">
                                {/* Contact Info */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                        Organisation
                                    </h2>

                                    {/* Club Display (auto-assigned) */}
                                    <div>
                                        <InputLabel value="Club organisateur" />
                                        <div className="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700">
                                            {userClub?.club_name || 'Aucun club trouvé'}
                                        </div>
                                    </div>

                                    {/* Organizer Selection - Club Members */}
                                    <div>
                                        <InputLabel htmlFor="adh_id" value="Responsable du raid" />
                                        <div className="mt-1">
                                            <UserSelect
                                                users={clubMembers}
                                                selectedId={data.adh_id}
                                                onSelect={(user) => setData('adh_id', user.adh_id)}
                                                label="Responsable"
                                                idKey="adh_id"
                                            />
                                        </div>
                                        <InputError message={errors.adh_id} className="mt-2" />
                                        <p className="mt-1 text-sm text-gray-500">
                                            Membres adhérents du club {userClub?.club_name || ''}
                                        </p>
                                    </div>

                                    {/* Contact */}
                                    <div>
                                        <InputLabel htmlFor="raid_contact" value="Email de contact" />
                                        <TextInput
                                            id="raid_contact"
                                            type="email"
                                            name="raid_contact"
                                            value={data.raid_contact}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('raid_contact', e.target.value)}
                                            placeholder="email@example.com"
                                            required
                                        />
                                        <InputError message={errors.raid_contact} className="mt-2" />
                                    </div>
                                </div>

                                {/* Optional Elements */}
                                <div className="bg-white rounded-lg shadow-md p-6 space-y-6">
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Informations complémentaires
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
                                    <ImageUpload
                                        label="Image du raid"
                                        name="raid_image"
                                        onChange={(file) => setData('raid_image', file)}
                                        error={errors.raid_image}
                                        maxSize={5}
                                        helperText="Image principale qui sera affichée sur la page du raid"
                                    />
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
