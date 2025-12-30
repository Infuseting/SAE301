import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    const messages = usePage().props.translations?.messages || {};

    return (
        <GuestLayout>
            <Head title={messages.email_verification || 'Email Verification'} />

            <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {messages.verify_instructions || "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another."}
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {messages.verification_link_sent || 'A new verification link has been sent to the email address you provided during registration.'}
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {messages.resend_verification_button || 'Resend Verification Email'}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-[#9333ea] focus:ring-offset-2"
                    >
                        {messages.logout || 'Log Out'}
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
