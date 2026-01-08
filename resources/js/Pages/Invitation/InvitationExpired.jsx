import React from 'react';
import { Link } from '@inertiajs/react';
import { Clock, Home, Mail } from 'lucide-react';

/**
 * Page displayed when invitation has expired.
 * 
 * @param {object} invitation - Invitation data
 */
export default function InvitationExpired({ invitation }) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-amber-50 flex items-center justify-center p-4">
            <div className="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8">
                {/* Icon */}
                <div className="text-center mb-6">
                    <div className="w-20 h-20 mx-auto rounded-full bg-amber-100 flex items-center justify-center">
                        <Clock className="h-10 w-10 text-amber-600" />
                    </div>
                </div>

                {/* Title */}
                <h1 className="text-2xl font-black text-slate-800 text-center mb-4">
                    Invitation expirée
                </h1>

                {/* Message */}
                <div className="text-center space-y-4 mb-8">
                    <p className="text-slate-600">
                        Cette invitation pour{' '}
                        <span className="font-bold text-blue-600">{invitation.race_name}</span>
                        {' '}a expiré le{' '}
                        <span className="font-bold">{invitation.expired_at}</span>.
                    </p>

                    <div className="p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                        <p className="text-sm text-amber-800">
                            <Mail className="inline h-4 w-4 mr-1" />
                            Les invitations sont valables pendant 7 jours.
                        </p>
                    </div>

                    <p className="text-sm text-slate-500">
                        Contactez l'organisateur de l'équipe pour recevoir une nouvelle invitation.
                    </p>
                </div>

                {/* Actions */}
                <div className="space-y-3">
                    <Link
                        href="/"
                        className="block w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm uppercase tracking-wider rounded-xl transition-colors text-center"
                    >
                        <Home className="inline h-4 w-4 mr-2" />
                        Retour à l'accueil
                    </Link>
                </div>
            </div>
        </div>
    );
}
