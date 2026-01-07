import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage, Link } from '@inertiajs/react';

/**
 * Edit Raid Form Component
 * Form for editing an existing raid with event and registration dates
 */
export default function Edit() {
    const { raid, userClub, clubMembers } = usePage().props;
    const messages = usePage().props.translations?.messages || {};

    const { data, setData, put, processing, errors } = useForm({
        raid_name: raid.raid_name || '',
        raid_description: raid.raid_description || '',
        raid_date_start: raid.raid_date_start || '',
        raid_date_end: raid.raid_date_end || '',
        raid_contact: raid.raid_contact || '',
        raid_street: raid.raid_street || '',
        raid_city: raid.raid_city || '',
        raid_postal_code: raid.raid_postal_code || '',
        raid_number: raid.raid_number || '',
        adh_id: raid.adh_id || '',
        clu_id: raid.clu_id || '',
        ins_start_date: raid.ins_start_date || '',
        ins_end_date: raid.ins_end_date || '',
        raid_site_url: raid.raid_site_url || '',
        raid_image: raid.raid_image || '',
    });

    /**
     * Handle form submission
     * @param {Event} e - Form submit event
     */
    const submit = (e) => {
        e.preventDefault();
        put(route('raids.update', raid.raid_id));
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

    /**
     * Format datetime for datetime-local input
     * @param {string} datetime - ISO datetime string
     * @returns {string} Formatted datetime string (YYYY-MM-DDTHH:mm)
     */
    const formatDateTimeLocal = (datetime) => {
        if (!datetime) return '';
        const date = new Date(datetime);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.edit_raid || 'Modifier le raid'} />

            {/* Green Header */}
            <div className="bg-green-500 py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-center relative">
                        <Link href={route('raids.show', raid.raid_id)} className="text-white hover:text-white/80 absolute left-0">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 className="text-2xl font-bold text-white">
                            {messages.edit_raid || 'Modifier le raid'}
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
                                                value={formatDateTimeLocal(data.raid_date_start)}
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
                                                value={formatDateTimeLocal(data.raid_date_end)}
                                                className="block w-full"
                                                onChange={(e) => setData('raid_date_end', e.target.value)}
                                                min={data.raid_date_start || undefined}
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

                                    {/* Organizer Selection - Club Members */}
                                    <div>
                                        <InputLabel htmlFor="adh_id" value="Responsable du raid" />
                                        <select
                                            id="adh_id"
                                            name="adh_id"
                                            value={data.adh_id}
                                            onChange={(e) => setData('adh_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                            required
                                        >
                                            <option value="">Sélectionner un membre du club...</option>
                                            {clubMembers?.map((member) => (
                                                <option key={member.adh_id} value={member.adh_id}>
                                                    {member.full_name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.adh_id} className="mt-2" />
                                        <p className="mt-1 text-sm text-gray-500">
                                            Membres adhérents du club {userClub?.club_name || ''}
                                        </p>
                                    </div>

                                    {/* Club Display (read-only) */}
                                    <div>
                                        <InputLabel value="Club de rattachement" />
                                        <div className="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700">
                                            {userClub?.club_name || 'Aucun club trouvé'}
                                        </div>
                                    </div>

                                    {/* Registration Period */}
                                    <div>
                                        <InputLabel value="Période d'inscription" />
                                        <div className="space-y-3 mt-2">
                                            <div>
                                                <label className="text-sm text-gray-600">Début des inscriptions</label>
                                                <TextInput
                                                    type="datetime-local"
                                                    name="ins_start_date"
                                                    value={formatDateTimeLocal(data.ins_start_date)}
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
                                                    value={formatDateTimeLocal(data.ins_end_date)}
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
                                        {data.raid_image && typeof data.raid_image === 'string' && (
                                            <div className="mb-4">
                                                <label className="text-sm text-gray-600">Image actuelle</label>
                                                <img 
                                                    src={data.raid_image} 
                                                    alt="Raid current" 
                                                    className="mt-2 w-full h-48 object-cover rounded-lg"
                                                />
                                            </div>
                                        )}
                                        <div className="flex items-center justify-center w-full">
                                            <label className="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg className="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                    <p className="mb-2 text-sm text-gray-500">
                                                        <span className="font-semibold">modifier l'image</span>
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
                                        {data.raid_image && typeof data.raid_image !== 'string' && (
                                            <p className="mt-2 text-sm text-gray-600">
                                                Nouveau fichier sélectionné : {data.raid_image.name}
                                            </p>
                                        )}
                                        <InputError message={errors.raid_image} className="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="mt-8 flex justify-center gap-4">
                            <Link
                                href={route('raids.show', raid.raid_id)}
                                className="px-16 py-3 bg-gray-300 text-gray-800 font-semibold rounded-md hover:bg-gray-400 transition-colors inline-block text-center"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-16 py-3 bg-gray-800 text-white font-semibold rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
