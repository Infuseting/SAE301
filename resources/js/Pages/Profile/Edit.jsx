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
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout

        >
            <Head title={messages.profile || 'Profile'} />

            {/* Hero Background Extension */}
            <div className="absolute top-0 left-0 w-full h-80 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 -z-10"></div>

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        {/* Left Column: Identity */}
                        <div className="xl:col-span-2 space-y-6">
                            {/* Profile Info Card - Large */}
                            <div className="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden border border-white/50">
                                <div className="p-6 md:p-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-gray-100">
                                        Identité
                                    </h3>
                                    <UpdateProfileInformationForm
                                        mustVerifyEmail={mustVerifyEmail}
                                        status={status}
                                        className="w-full"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Security, Connected Accounts & Danger Zone */}
                        <div className="xl:col-span-1 space-y-6">
                            {hasPassword !== false && (
                                <div className="space-y-6">
                                    {/* Passwords */}
                                    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                                        <div className="p-6">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                Sécurité
                                            </h3>
                                            {hasPassword ? (
                                                <UpdatePasswordForm className="max-w-xl" />
                                        </div>
                                    </div>

                                    {/* 2FA */}
                                    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                                        <div className="p-6">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <svg className="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                Double authentification
                                            </h3>
                                            <TwoFactorAuthenticationForm className="max-w-xl" hasPassword={hasPassword} />
                                        </div>
                                    </div>
                                </div>
<<<<<<< HEAD
=======
                            </div>
>>>>>>> origin/infos
                            )}

                            <div className="space-y-6">
                                {/* Connected Accounts */}
                                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                                    <div className="p-6">
                                        <h3 className="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                            Comptes connectés
                                        </h3>
                                        <ConnectedAccountsForm className="w-full" connectedAccounts={connectedAccounts} />
                                    </div>
                                </div>

                                {/* Danger Zone */}
                                <div className="bg-red-50 rounded-2xl border border-red-100 overflow-hidden">
                                    <div className="p-6">
                                        <h3 className="text-base font-semibold text-red-700 mb-4 flex items-center gap-2">
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Zone de danger
                                        </h3>
                                        <DeleteUserForm className="max-w-xl" />
                                    </div>
                                </div>
                            </div>
<<<<<<< HEAD

    {/* Danger Zone */ }
    <div className="bg-red-50 rounded-2xl border border-red-100 overflow-hidden">
        <div className="p-6">
            <h3 className="text-lg font-semibold text-red-700 mb-4 flex items-center gap-2">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Zone de danger
            </h3>
            <DeleteUserForm className="max-w-xl" hasPassword={hasPassword} />
        </div>
    </div>

=======
>>>>>>> origin/infos
                        </div >
                    </div >
                </div >
            </div >
        </AuthenticatedLayout >
    );
}
