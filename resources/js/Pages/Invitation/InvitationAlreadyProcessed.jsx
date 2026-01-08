import React from 'react';
import { Link } from '@inertiajs/react';
import { CheckCircle, XCircle, Home } from 'lucide-react';

/**
 * Page displayed when invitation has already been processed.
 * 
 * @param {string} status - Invitation status (accepted/rejected)
 * @param {string} race_name - Name of the race
 */
export default function InvitationAlreadyProcessed({ status, race_name }) {
    const isAccepted = status === 'accepted';

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-emerald-50 flex items-center justify-center p-4">
            <div className="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8">
                {/* Icon */}
                <div className="text-center mb-6">
                    <div className={`w-20 h-20 mx-auto rounded-full flex items-center justify-center ${isAccepted ? 'bg-emerald-100' : 'bg-red-100'
                        }`}>
                        {isAccepted ? (
                            <CheckCircle className="h-10 w-10 text-emerald-600" />
                        ) : (
                            <XCircle className="h-10 w-10 text-red-600" />
                        )}
                    </div>
                </div>

                {/* Title */}
                <h1 className="text-2xl font-black text-slate-800 text-center mb-4">
                    {isAccepted ? 'Invitation déjà acceptée' : 'Invitation déjà traitée'}
                </h1>

                {/* Message */}
                <div className="text-center space-y-4 mb-8">
                    <p className="text-slate-600">
                        {isAccepted ? (
                            <>
                                Vous avez déjà accepté cette invitation pour{' '}
                                <span className="font-bold text-emerald-600">{race_name}</span>.
                            </>
                        ) : (
                            <>
                                Cette invitation pour{' '}
                                <span className="font-bold text-slate-800">{race_name}</span>
                                {' '}a déjà été traitée.
                            </>
                        )}
                    </p>

                    {isAccepted && (
                        <p className="text-sm text-slate-500">
                            Vous faites déjà partie de l'équipe pour cette course.
                        </p>
                    )}
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
