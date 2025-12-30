import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import ConnectedAccountsForm from './Partials/ConnectedAccountsForm';
import SetPasswordForm from './Partials/SetPasswordForm';

export default function Edit({ mustVerifyEmail, status, connectedAccounts, hasPassword }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                    {messages.profile || 'Profile'}
                </h2>
            }
        >
            <Head title={messages.profile || 'Profile'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {!hasPassword && (
                        <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                            <SetPasswordForm className="max-w-xl" />
                        </div>
                    )}

                    {hasPassword && (
                        <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>
                    )}

                    <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                        <ConnectedAccountsForm className="max-w-xl" connectedAccounts={connectedAccounts} />
                    </div>

                    <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                        <TwoFactorAuthenticationForm className="max-w-xl" hasPassword={hasPassword} />
                    </div>

                    <div className="bg-white p-4 shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] border border-gray-200 dark:border-zinc-700 sm:rounded-lg sm:p-8 dark:bg-[#18181b]">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
