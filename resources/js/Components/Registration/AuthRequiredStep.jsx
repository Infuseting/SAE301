import React from 'react';
import { Link } from '@inertiajs/react';
import { LogIn, UserPlus } from 'lucide-react';

/**
 * Authentication step for unauthenticated users.
 * Redirects to login/register pages with return URL.
 * 
 * @param {object} translations - Translation strings
 * @param {number} raceId - Race ID for return URL
 */
export default function AuthRequiredStep({ translations, raceId }) {
    const currentUrl = window.location.pathname;
    const returnUrl = encodeURIComponent(currentUrl);

    return (
        <div className="space-y-6 text-center py-8">
            <div className="mb-8">
                <h3 className="text-2xl font-bold text-slate-800 mb-2">
                    Connexion requise
                </h3>
                <p className="text-slate-600">
                    Vous devez être connecté pour vous inscrire à cette course
                </p>
            </div>

            <div className="space-y-4">
                <Link
                    href={`/login?redirect=${returnUrl}`}
                    className="block w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-blue-200 transition-all"
                >
                    <LogIn className="inline h-4 w-4 mr-2" />
                    {translations.login || 'Se connecter'}
                </Link>

                <Link
                    href={`/register?redirect=${returnUrl}`}
                    className="block w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-200 transition-all"
                >
                    <UserPlus className="inline h-4 w-4 mr-2" />
                    {translations.register || "Créer un compte"}
                </Link>
            </div>

            <p className="text-sm text-slate-500 mt-6">
                Après connexion, vous serez redirigé vers cette page pour finaliser votre inscription
            </p>
        </div>
    );
}
