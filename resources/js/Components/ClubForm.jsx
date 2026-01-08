import { useForm, usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import ImageUpload from '@/Components/ImageUpload';

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

    const { data, setData, post, processing, errors } = useForm({
        club_name: club?.club_name || '',
        club_street: club?.club_street || '',
        club_city: club?.club_city || '',
        club_postal_code: club?.club_postal_code || '',
        ffso_id: club?.ffso_id || '',
        description: club?.description || '',
        club_image: null,
        _method: isEditing ? 'PUT' : 'POST',
    });

    /**
     * Handle form submission
     * Uses POST with _method for editing to support file uploads (FormData)
     */
    const submit = (e) => {
        e.preventDefault();

        if (isEditing) {
            post(route(submitRoute, club.club_id), {
                forceFormData: true,
            });
        } else {
            post(route(submitRoute), {
                forceFormData: true,
            });
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            {/* Club Image */}
            <ImageUpload
                label={`${messages.club_image || 'Logo/Image du Club'} (${messages.optional})`}
                name="club_image"
                onChange={(file) => setData('club_image', file)}
                error={errors.club_image}
                currentImage={club?.club_image ? `/storage/${club.club_image}` : null}
                maxSize={5}
                helperText="PNG, JPG, GIF, WEBP jusqu'à 5MB"
            />

            {/* Club Name */}
            <div>
                <InputLabel htmlFor="club_name" value={messages.club_name} />
                <TextInput
                    id="club_name"
                    type="text"
                    className="mt-1 block w-full"
                    value={data.club_name}
                    onChange={(e) => setData('club_name', e.target.value)}
                    maxLength={100}
                    required
                />
                <p className="text-xs text-gray-500 mt-1">{data.club_name.length}/100 caractères</p>
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
                        maxLength={100}
                        required
                    />
                    <p className="text-xs text-gray-500 mt-1">{data.club_street.length}/100 caractères</p>
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
                        maxLength={100}
                        required
                    />
                    <p className="text-xs text-gray-500 mt-1">{data.club_city.length}/100 caractères</p>
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
                        maxLength={20}
                        required
                    />
                    <p className="text-xs text-gray-500 mt-1">{data.club_postal_code.length}/20 caractères</p>
                    <InputError message={errors.club_postal_code} className="mt-2" />
                </div>
            </div>

            {/* FFCO ID - Now Required */}
            <div>
                <InputLabel htmlFor="ffso_id" value={messages.ffso_id} />
                <TextInput
                    id="ffso_id"
                    type="text"
                    className="mt-1 block w-full"
                    value={data.ffso_id}
                    onChange={(e) => setData('ffso_id', e.target.value)}
                    maxLength={50}
                    required
                    placeholder="Ex: FFCO-12345"
                />
                <p className="text-xs text-gray-500 mt-1">{data.ffso_id.length}/50 caractères</p>
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
