import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

/**
 * ClubForm component - Form for creating or editing clubs
 * 
 * @param {Object} club - Existing club data (for editing)
 * @param {string} submitRoute - Route name for form submission
 * @param {string} submitLabel - Label for submit button
 */
export default function ClubForm({ club = null, submitRoute, submitLabel }) {
    const messages = usePage().props.translations?.messages || {};
    const isEditing = club !== null;
    const [imagePreview, setImagePreview] = useState(
        club?.club_image ? `/storage/${club.club_image}` : null
    );

    const { data, setData, post, put, processing, errors } = useForm({
        club_name: club?.club_name || '',
        club_street: club?.club_street || '',
        club_city: club?.club_city || '',
        club_postal_code: club?.club_postal_code || '',
        ffso_id: club?.ffso_id || '',
        description: club?.description || '',
        club_image: null,
    });

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('club_image', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const submit = (e) => {
        e.preventDefault();

        if (isEditing) {
            post(route(submitRoute, club.club_id), {
                forceFormData: true,
                _method: 'PUT',
            });
        } else {
            post(route(submitRoute));
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            {/* Club Image */}
            <div>
                <InputLabel htmlFor="club_image" value={`${messages.club_image || 'Club Image'} (${messages.optional})`} />
                <div className="mt-2 flex items-center gap-6">
                    {/* Image Preview */}
                    <div className="flex-shrink-0">
                        <div className="w-32 h-32 rounded-lg overflow-hidden bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center border-2 border-gray-200">
                            {imagePreview ? (
                                <img
                                    src={imagePreview}
                                    alt="Club preview"
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                    className="w-12 h-12 text-emerald-600"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                                    />
                                </svg>
                            )}
                        </div>
                    </div>

                    {/* File Input */}
                    <div className="flex-1">
                        <input
                            id="club_image"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                            onChange={handleImageChange}
                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                        />
                        <p className="mt-2 text-xs text-gray-500">
                            PNG, JPG, GIF, WEBP up to 2MB
                        </p>
                    </div>
                </div>
                <InputError message={errors.club_image} className="mt-2" />
            </div>

            {/* Club Name */}
            <div>
                <InputLabel htmlFor="club_name" value={messages.club_name} />
                <TextInput
                    id="club_name"
                    type="text"
                    className="mt-1 block w-full"
                    value={data.club_name}
                    onChange={(e) => setData('club_name', e.target.value)}
                    required
                />
                <InputError message={errors.club_name} className="mt-2" />
            </div>

            {/* Address Section */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Street */}
                <div className="md:col-span-2">
                    <InputLabel htmlFor="club_street" value={messages.club_street} />
                    <TextInput
                        id="club_street"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.club_street}
                        onChange={(e) => setData('club_street', e.target.value)}
                        required
                    />
                    <InputError message={errors.club_street} className="mt-2" />
                </div>

                {/* City */}
                <div>
                    <InputLabel htmlFor="club_city" value={messages.club_city} />
                    <TextInput
                        id="club_city"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.club_city}
                        onChange={(e) => setData('club_city', e.target.value)}
                        required
                    />
                    <InputError message={errors.club_city} className="mt-2" />
                </div>

                {/* Postal Code */}
                <div>
                    <InputLabel htmlFor="club_postal_code" value={messages.club_postal_code} />
                    <TextInput
                        id="club_postal_code"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.club_postal_code}
                        onChange={(e) => setData('club_postal_code', e.target.value)}
                        required
                    />
                    <InputError message={errors.club_postal_code} className="mt-2" />
                </div>
            </div>

            {/* FFSO ID - Now Required */}
            <div>
                <InputLabel htmlFor="ffso_id" value={messages.ffso_id} />
                <TextInput
                    id="ffso_id"
                    type="text"
                    className="mt-1 block w-full"
                    value={data.ffso_id}
                    onChange={(e) => setData('ffso_id', e.target.value)}
                    required
                    placeholder="Ex: FFSO-12345"
                />
                <InputError message={errors.ffso_id} className="mt-2" />
            </div>

            {/* Description */}
            <div>
                <InputLabel htmlFor="description" value={`${messages.club_description} (${messages.optional})`} />
                <textarea
                    id="description"
                    className="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"
                    rows="4"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    maxLength="1000"
                />
                <InputError message={errors.description} className="mt-2" />
                <p className="mt-1 text-sm text-gray-500">
                    {data.description.length}/1000
                </p>
            </div>

            {/* Submit Button */}
            <div className="flex items-center justify-end">
                <PrimaryButton disabled={processing}>
                    {submitLabel}
                </PrimaryButton>
            </div>
        </form>
    );
}
