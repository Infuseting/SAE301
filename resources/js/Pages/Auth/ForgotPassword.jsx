import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
                    Mot de passe oublié ?
                </h2>
                <div className="mt-2 text-sm text-gray-600">
                    Pas de problème. Indiquez simplement votre adresse email et nous vous enverrons un lien de réinitialisation de mot de passe qui vous permettra d'en choisir un nouveau.
                </div>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <InputLabel htmlFor="email" value="Adresse email" className="text-gray-700" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="flex items-center justify-end mt-4">
                    <PrimaryButton className="w-full flex justify-center bg-purple-600 hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-800 py-3" disabled={processing}>
                        Envoyer le lien de réinitialisation
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
