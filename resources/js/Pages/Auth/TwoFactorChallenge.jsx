import { useRef, useState } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    const recoveryCodeInput = useRef();
    const codeInput = useRef();

    const submit = (e) => {
        e.preventDefault();

        post(route('two-factor.login'));
    };

    const toggleRecovery = (e) => {
        e.preventDefault();
        const isRecovery = !recovery;
        setRecovery(isRecovery);

        if (isRecovery) {
            setTimeout(() => recoveryCodeInput.current.focus(), 100);
            setData('code', '');
        } else {
            setTimeout(() => codeInput.current.focus(), 100);
            setData('recovery_code', '');
        }
    };

    return (
        <GuestLayout>
            <Head title="Two-factor Confirmation" />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
                    Authentification Requise
                </h2>
                <p className="mt-2 text-sm text-gray-600">
                    {recovery
                        ? 'Veuillez confirmer l\'accès à votre compte en entrant l\'un de vos codes de récupération d\'urgence.'
                        : 'Veuillez confirmer l\'accès à votre compte en entrant le code d\'authentification fourni par votre application.'}
                </p>
            </div>


            <form onSubmit={submit} className="space-y-6">
                {recovery ? (
                    <div>
                        <InputLabel htmlFor="recovery_code" value="Code de récupération" className="text-gray-700" />
                        <TextInput
                            id="recovery_code"
                            ref={recoveryCodeInput}
                            type="text"
                            name="recovery_code"
                            value={data.recovery_code}
                            className="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                        />
                        <InputError message={errors.recovery_code} className="mt-2" />
                    </div>
                ) : (
                    <div>
                        <InputLabel htmlFor="code" value="Code" className="text-gray-700" />
                        <TextInput
                            id="code"
                            ref={codeInput}
                            type="text"
                            name="code"
                            value={data.code}
                            className="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <button
                        type="button"
                        className="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                        onClick={toggleRecovery}
                    >
                        {recovery ? 'Utiliser un code d\'authentification' : 'Utiliser un code de récupération'}
                    </button>
                </div>

                <div className="flex justify-end mt-4">
                    <PrimaryButton className="w-full flex justify-center bg-purple-600 hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-800 py-3" disabled={processing}>
                        Se connecter
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
