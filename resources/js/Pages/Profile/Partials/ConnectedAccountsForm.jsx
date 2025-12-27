import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';

export default function ConnectedAccountsForm({ className = '', connectedAccounts = [] }) {
    const hasAccount = (provider) => {
        return connectedAccounts.some(account => account.provider === provider);
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Connected Accounts</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Manage your connected social accounts.
                </p>
            </header>

            <div className="mt-6 space-y-6">
                {/* GitHub */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <InputLabel value="GitHub" className="text-lg" />
                    </div>
                    <div>
                        {hasAccount('github') ? (
                            <span className="px-4 py-2 text-sm text-green-600 font-semibold bg-green-100 rounded-lg">Connected</span>
                        ) : (
                            <a href={route('socialite.redirect', 'github')}>
                                <SecondaryButton>Connect GitHub</SecondaryButton>
                            </a>
                        )}
                    </div>
                </div>

                {/* Google */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <InputLabel value="Google" className="text-lg" />
                    </div>
                    <div>
                        {hasAccount('google') ? (
                            <span className="px-4 py-2 text-sm text-green-600 font-semibold bg-green-100 rounded-lg">Connected</span>
                        ) : (
                            <a href={route('socialite.redirect', 'google')}>
                                <SecondaryButton>Connect Google</SecondaryButton>
                            </a>
                        )}
                    </div>
                </div>

                {/* Discord */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <InputLabel value="Discord" className="text-lg" />
                    </div>
                    <div>
                        {hasAccount('discord') ? (
                            <span className="px-4 py-2 text-sm text-green-600 font-semibold bg-green-100 rounded-lg">Connected</span>
                        ) : (
                            <a href={route('socialite.redirect', 'discord')}>
                                <SecondaryButton>Connect Discord</SecondaryButton>
                            </a>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
