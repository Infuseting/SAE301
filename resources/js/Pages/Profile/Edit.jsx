import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage, Link } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import ConnectedAccountsForm from './Partials/ConnectedAccountsForm';

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

                    <div className="relative">
                        <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                            {/* Left Column: Identity */}
                            <div className="xl:col-span-2 space-y-6">
                                {/* Profile Info Card - Large */}
                                <div className="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden border border-white/50">
                                    <div className="p-6 md:p-8">
                                        <div className="flex items-center gap-3 mb-4">
                                            <Link href="/profile" className="w-8 h-8 flex-shrink-0 hover:opacity-70 transition-opacity">
                                                <svg width="100%" height="100%" viewBox="0 0 650 650" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                    <g id="Layer_1" />
                                                    <g id="Layer_2">
                                                        <g>
                                                            <path fill="black" d="M217,129.88c-6.25-6.25-16.38-6.25-22.63,0L79.61,244.64c-0.39,0.39-0.76,0.8-1.11,1.23    c-0.11,0.13-0.2,0.27-0.31,0.41c-0.21,0.28-0.42,0.55-0.62,0.84c-0.14,0.21-0.26,0.43-0.39,0.64c-0.14,0.23-0.28,0.46-0.41,0.7    c-0.13,0.24-0.24,0.48-0.35,0.73c-0.11,0.23-0.22,0.45-0.32,0.68c-0.11,0.26-0.19,0.52-0.28,0.78c-0.08,0.23-0.17,0.46-0.24,0.69    c-0.09,0.29-0.15,0.58-0.22,0.86c-0.05,0.22-0.11,0.43-0.16,0.65c-0.08,0.38-0.13,0.76-0.17,1.14c-0.02,0.14-0.04,0.27-0.06,0.41    c-0.11,1.07-0.11,2.15,0,3.22c0.01,0.06,0.02,0.12,0.03,0.18c0.05,0.46,0.12,0.92,0.21,1.37c0.03,0.13,0.07,0.26,0.1,0.39    c0.09,0.38,0.18,0.76,0.29,1.13c0.04,0.13,0.09,0.26,0.14,0.4c0.12,0.36,0.25,0.73,0.4,1.09c0.05,0.11,0.1,0.21,0.15,0.32    c0.17,0.37,0.34,0.74,0.53,1.1c0.04,0.07,0.09,0.14,0.13,0.21c0.21,0.38,0.44,0.76,0.68,1.13c0.02,0.03,0.04,0.06,0.06,0.09    c0.55,0.81,1.18,1.58,1.89,2.29l114.81,114.81c3.12,3.12,7.22,4.69,11.31,4.69s8.19-1.56,11.31-4.69c6.25-6.25,6.25-16.38,0-22.63    l-87.5-87.5h291.62c8.84,0,16-7.16,16-16s-7.16-16-16-16H129.51L217,152.5C223.25,146.26,223.25,136.13,217,129.88z" />
                                                        </g>
                                                    </g>
                                                </svg>
                                            </Link>
                                            <h3 className="text-lg font-semibold text-gray-900 border-b pb-2 border-gray-100 flex-grow">
                                                Identité
                                            </h3>
                                        </div>
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
                                                ) : null}

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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout >
    );
}
