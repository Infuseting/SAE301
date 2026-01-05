import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const messages = usePage().props.translations?.messages || {};

    return (
        <GuestLayout>
            <Head title={messages.reset_password_title || 'Reset Password'} />

            <div className="mb-6">
                <h2 className="mt-6 text-3xl font-extrabold text-gray-900 ">
                    {messages.reset_password_title || 'Reset Password'}
                </h2>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <InputLabel htmlFor="email" value={messages.email_address || 'Email address'} className="text-gray-700 " />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value={messages.new_password || 'New password'} className="text-gray-700 " />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
                        autoComplete="new-password"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password_confirmation"
                        value={messages.confirm_password || 'Confirm Password'}
                        className="text-gray-700 "
                    />

                    <TextInput
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full border-gray-300 focus:border-[#9333ea] focus:ring-[#9333ea] rounded-md shadow-sm"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="flex items-center justify-end mt-4">
                    <PrimaryButton className="w-full flex justify-center py-3" disabled={processing}>
                        {messages.reset_password_button || 'Reset Password'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
