import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    const messages = usePage().props.translations?.messages || {};

    return (
        <GuestLayout>
            <Head title={messages.forgot_password_title || 'Forgot Password'} />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    {messages.forgot_password_title || 'Forgot Password'}
                </h2>
                <div className="mt-2 text-sm text-gray-600">
                    {messages.forgot_password_subtext || "No problem. Just let us know your email address and we'll send you a password reset link that will allow you to choose a new one."}
                </div>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <InputLabel htmlFor="email" value={messages.email_address || 'Email address'} className="text-gray-700 dark:text-gray-200" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                                className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="flex items-center justify-end mt-4">
                    <PrimaryButton className="w-full flex justify-center py-3" disabled={processing}>
                        {messages.send_reset_link_button || 'Send password reset link'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
