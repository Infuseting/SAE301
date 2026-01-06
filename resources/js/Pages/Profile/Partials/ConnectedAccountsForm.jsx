import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';

export default function ConnectedAccountsForm({ className = '', connectedAccounts = [] }) {
    const messages = usePage().props.translations?.messages || {};

    const hasAccount = (provider) => {
        return connectedAccounts.some(account => account.provider === provider);
    };

    return (
        <section className={className}>
            <div className="mt-6 space-y-6">

                {/* Google */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <InputLabel value="Google" className="text-lg" />
                    </div>
                    <div>
                        {hasAccount('google') ? (
                            <span className="px-4 py-2 text-sm text-green-600 font-semibold bg-green-100   rounded-lg">Connected</span>
                        ) : (
                            <a href={route('socialite.redirect', 'google')}>
                                <SecondaryButton>{messages.connect_google || 'Connect Google'}</SecondaryButton>
                            </a>
                        )}
                    </div>
                </div>

            </div>
        </section>
    );
}
