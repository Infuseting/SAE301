import { useRef, useState } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

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

    const messages = usePage().props.translations?.messages || {};

    return (
        <GuestLayout>
            <Head title={messages.twofactor_title || 'Two-factor Confirmation'} />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900 ">
                    {messages.twofactor_title || 'Two-factor Confirmation'}
                </h2>
                <p className="mt-2 text-sm text-gray-600 ">
                    {recovery ? (messages.twofactor_recovery_text || "Please confirm access to your account by entering one of your emergency recovery codes.") : (messages.twofactor_required || "Please confirm access to your account by entering the authentication code provided by your authenticator application.")}
                </p>
            </div>


            <form onSubmit={submit} className="space-y-6">
                {recovery ? (
                    <div>
                        <InputLabel htmlFor="recovery_code" value={messages.recovery_code_label || 'Recovery Code'} className="text-gray-700 " />
                        <TextInput
                            id="recovery_code"
                            ref={recoveryCodeInput}
                            type="text"
                            name="recovery_code"
                            value={data.recovery_code}
                            className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                        />
                        <InputError message={errors.recovery_code} className="mt-2" />
                    </div>
                ) : (
                    <div>
                        <InputLabel htmlFor="code" value={messages.code_label || 'Code'} className="text-gray-700 " />
                        <TextInput
                            id="code"
                            ref={codeInput}
                            type="text"
                            name="code"
                            value={data.code}
                            className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
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
                        className="text-sm text-gray-600  hover:text-gray-900  underline cursor-pointer"
                        onClick={toggleRecovery}
                    >
                        {recovery ? (messages.twofactor_use_code || 'Use an authentication code') : (messages.twofactor_use_recovery || 'Use a recovery code')}
                    </button>
                </div>

                <div className="flex justify-end mt-4">
                    <PrimaryButton className="w-full flex justify-center py-3" disabled={processing}>
                        {messages.twofactor_login_button || 'Log in'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
