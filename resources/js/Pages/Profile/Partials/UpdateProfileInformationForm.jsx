import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import UserAvatar from '@/Components/UserAvatar';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;
    const messages = usePage().props.translations?.messages || {};

    const { data, setData, post, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            description: user.description || '',
            birth_date: user.birth_date ? user.birth_date.split('T')[0] : '',
            address: user.address || '',
            phone: user.phone || '',
            license_number: user.license_number || '',
            medical_certificate_code: user.medical_certificate_code || '',
            is_public: user.is_public || false,
            photo: null,
            _method: 'PATCH',
        });

    const submit = (e) => {
        e.preventDefault();

        post(route('profile.update'), {
            forceFormData: true,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 ">
                    {messages.profile_information || 'Profile Information'}
                </h2>

                <p className="mt-1 text-sm text-gray-600 ">
                    {messages.profile_update_subtext || "Update your account's profile information and email address."}
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6" encType="multipart/form-data">
                {/* Profile Photo */}
                {/* Profile Photo */}
                <div className="flex items-center gap-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div className="shrink-0 relative">
                        <UserAvatar
                            user={user}
                            src={data.photo ? URL.createObjectURL(data.photo) : null}
                            className="h-20 w-20 border-2 border-white shadow-md text-2xl"
                        />
                        <div className="absolute bottom-0 right-0 bg-white rounded-full p-1 shadow-sm border border-gray-100">
                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                    </div>
                    <div className="flex-1">
                        <label className="block">
                            <span className="sr-only">Choose profile photo</span>
                            <div className="flex items-center gap-3">
                                <label
                                    htmlFor="photo-upload"
                                    className="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    Select New Photo
                                </label>
                                <input
                                    id="photo-upload"
                                    type="file"
                                    className="hidden"
                                    onChange={async (e) => {
                                        const file = e.target.files[0];
                                        if (file) {
                                            if (file.size > 8 * 1024 * 1024) { // 8MB Hard Limit
                                                alert("La taille de l'image ne doit pas dépasser 8Mo.");
                                                e.target.value = "";
                                                return;
                                            }

                                            // If file is > 2MB, compress it
                                            if (file.size > 2 * 1024 * 1024) {
                                                try {
                                                    const compressImage = async (file, { quality = 0.7, type = 'image/jpeg' }) => {
                                                        const imageBitmap = await createImageBitmap(file);
                                                        const canvas = document.createElement('canvas');
                                                        canvas.width = imageBitmap.width;
                                                        canvas.height = imageBitmap.height;
                                                        const ctx = canvas.getContext('2d');
                                                        ctx.drawImage(imageBitmap, 0, 0);
                                                        return await new Promise((resolve) => canvas.toBlob(resolve, type, quality));
                                                    };

                                                    let compressedBlob = await compressImage(file, { quality: 0.7 });

                                                    // If still too big, try more aggressive compression
                                                    if (compressedBlob.size > 2 * 1024 * 1024) {
                                                        compressedBlob = await compressImage(file, { quality: 0.5 });
                                                    }

                                                    const compressedFile = new File([compressedBlob], file.name, {
                                                        type: compressedBlob.type,
                                                    });
                                                    setData('photo', compressedFile);
                                                } catch (error) {
                                                    console.error("Compression failed", error);
                                                    alert("Impossible de compresser l'image. Veuillez choisir une image plus petite.");
                                                }
                                            } else {
                                                setData('photo', file);
                                            }
                                        }
                                    }}
                                    accept="image/*"
                                />
                                {data.photo && <span className="text-sm text-green-600 font-medium">Photo selected</span>}
                            </div>
                            <p className="mt-2 text-xs text-gray-500">JPG, GIF or PNG. Max 8MB (Auto-compressed).</p>
                        </label>
                        <InputError className="mt-2" message={errors.photo} />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="name" value={messages.name || 'Name'} />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value={messages.email || 'Email'} />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="description" value={messages.description_bio || 'Description / Bio'} />
                    <textarea
                        id="description"
                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        rows="3"
                    ></textarea>
                    <InputError className="mt-2" message={errors.description} />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel htmlFor="birth_date" value="Date de naissance" />
                        <TextInput
                            id="birth_date"
                            type="date"
                            className="mt-1 block w-full"
                            defaultValue={data.birth_date}
                            onChange={(e) => setData('birth_date', e.target.value)}
                        />
                        <InputError className="mt-2" message={errors.birth_date} />
                    </div>

                    <div>
                        <InputLabel htmlFor="phone" value="Téléphone" />
                        <TextInput
                            id="phone"
                            className="mt-1 block w-full"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError className="mt-2" message={errors.phone} />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div className="md:col-span-2">
                        <InputLabel htmlFor="address" value="Adresse" className="text-gray-700 font-medium mb-1" />
                        <TextInput
                            id="address"
                            className="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white focus:border-[#9333ea] focus:ring-[#9333ea] transition-all duration-200"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            placeholder="Votre adresse complète"
                        />
                        <InputError className="mt-2" message={errors.address} />
                    </div>

                    <div>
                        <InputLabel htmlFor="license_number" value="Numéro de Licence" className="text-gray-700 font-medium mb-1" />
                        <TextInput
                            id="license_number"
                            className={`mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white focus:border-[#9333ea] focus:ring-[#9333ea] transition-all duration-200 ${data.medical_certificate_code ? 'opacity-50 cursor-not-allowed bg-gray-100' : ''}`}
                            value={data.license_number}
                            onChange={(e) => {
                                setData(prev => ({ ...prev, license_number: e.target.value, medical_certificate_code: '' }));
                            }}
                            disabled={!!data.medical_certificate_code && data.medical_certificate_code.length > 0}
                            placeholder="Ex: 123456"
                        />
                        <InputError className="mt-2" message={errors.license_number} />
                        {!data.medical_certificate_code && <p className="text-xs text-gray-500 mt-1">Saisissez votre licence OU votre code PPS ci-contre.</p>}
                    </div>
                    <div>
                        <InputLabel htmlFor="medical_certificate_code" value="Code PPS / Certificat" className="text-gray-700 font-medium mb-1" />
                        <TextInput
                            id="medical_certificate_code"
                            className={`mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white focus:border-[#9333ea] focus:ring-[#9333ea] transition-all duration-200 ${data.license_number ? 'opacity-50 cursor-not-allowed bg-gray-100' : ''}`}
                            value={data.medical_certificate_code}
                            onChange={(e) => {
                                setData(prev => ({ ...prev, medical_certificate_code: e.target.value, license_number: '' }));
                            }}
                            disabled={!!data.license_number && data.license_number.length > 0}
                            placeholder="Ex: PPS-123"
                        />
                        <InputError className="mt-2" message={errors.medical_certificate_code} />
                    </div>
                </div>

                <div className="block mt-4">
                    <label className="flex items-center">
                        <input
                            type="checkbox"
                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            checked={data.is_public}
                            onChange={(e) => setData('is_public', e.target.checked)}
                        />
                        <span className="ml-2 text-sm text-gray-600">Rendre mon profil public (Visible par les autres utilisateurs)</span>
                    </label>
                    <InputError className="mt-2" message={errors.is_public} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800 ">
                            {messages.email_unverified || 'Your email address is unverified.'}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600  underline hover:text-gray-900  focus:outline-none focus:ring-2 focus:ring-[#9333ea] focus:ring-offset-2"
                            >
                                {messages.resend_verification || 'Click here to re-send the verification email.'}
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                {messages.verification_link_sent || 'A new verification link has been sent to your email address.'}
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4 pt-4 border-t border-gray-100">
                    <div className="flex-1">
                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-green-600 font-medium flex items-center gap-1">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                {messages.saved || 'Saved.'}
                            </p>
                        </Transition>
                    </div>

                    <PrimaryButton disabled={processing} className="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 border-0 shadow-lg shadow-indigo-500/30 px-8 py-3 rounded-xl transition-all duration-200 transform hover:scale-[1.02]">
                        {messages.save || 'Save Changes'}
                    </PrimaryButton>
                </div>
            </form>
        </section>
    );
}
