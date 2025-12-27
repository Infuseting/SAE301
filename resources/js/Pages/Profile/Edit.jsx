import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import ConnectedAccountsForm from './Partials/ConnectedAccountsForm';
import SetPasswordForm from './Partials/SetPasswordForm';

export default function Edit({ mustVerifyEmail, status, connectedAccounts, hasPassword }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {!hasPassword && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <SetPasswordForm className="max-w-xl" />
                        </div>
                    )}

                    {hasPassword && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <ConnectedAccountsForm className="max-w-xl" connectedAccounts={connectedAccounts} />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <TwoFactorAuthenticationForm className="max-w-xl" hasPassword={hasPassword} />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
