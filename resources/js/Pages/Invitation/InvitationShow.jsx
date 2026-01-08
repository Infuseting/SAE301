import { Head, Link, useForm } from '@inertiajs/react';
import { Calendar, MapPin, User, Clock } from 'lucide-react';

export default function InvitationShow({ auth, invitation }) {
    const { post, processing } = useForm();

    const handleAccept = () => {
        post(route('invitation.accept', invitation.token));
    };

    return (
        <>
            <Head title="Invitation à rejoindre une équipe" />

            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-6">
                <div className="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                        <h1 className="text-3xl font-bold mb-2">🏃 Invitation à rejoindre une équipe</h1>
                        <p className="text-blue-100">Vous avez été invité à participer à une course</p>
                    </div>

                    {/* Content */}
                    <div className="p-8">
                        {/* Inviter info */}
                        <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div className="flex items-center gap-3">
                                <User className="h-5 w-5 text-blue-600" />
                                <div>
                                    <p className="text-sm text-gray-600">Invité par</p>
                                    <p className="font-bold text-gray-900">{invitation.inviter.name}</p>
                                </div>
                            </div>
                        </div>

                        {/* Race info */}
                        <div className="mb-6">
                            <h2 className="text-2xl font-bold text-gray-900 mb-4">{invitation.race.name}</h2>

                            {invitation.race.description && (
                                <p className="text-gray-600 mb-4">{invitation.race.description}</p>
                            )}

                            <div className="space-y-3">
                                {invitation.race.date_start && (
                                    <div className="flex items-center gap-3 text-gray-700">
                                        <Calendar className="h-5 w-5 text-gray-400" />
                                        <span>
                                            {new Date(invitation.race.date_start).toLocaleDateString('fr-FR', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            })}
                                        </span>
                                    </div>
                                )}

                                {invitation.race.location && (
                                    <div className="flex items-center gap-3 text-gray-700">
                                        <MapPin className="h-5 w-5 text-gray-400" />
                                        <span>{invitation.race.location}</span>
                                    </div>
                                )}

                                <div className="flex items-center gap-3 text-amber-700 bg-amber-50 p-3 rounded-lg">
                                    <Clock className="h-5 w-5" />
                                    <span className="text-sm">
                                        <strong>Expire le :</strong> {invitation.expires_at}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="space-y-3">
                            {auth.user ? (
                                auth.user.email === invitation.email ? (
                                    <button
                                        onClick={handleAccept}
                                        disabled={processing}
                                        className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Acceptation...' : '✅ Accepter l\'invitation'}
                                    </button>
                                ) : (
                                    <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                                        Cette invitation est pour <strong>{invitation.email}</strong>.
                                        Vous êtes connecté en tant que <strong>{auth.user.email}</strong>.
                                    </div>
                                )
                            ) : (
                                <>
                                    <Link
                                        href={route('invitation.register', invitation.token)}
                                        className="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl transition-colors text-center"
                                    >
                                        ✅ Créer un compte et accepter
                                    </Link>
                                    <Link
                                        href={route('login')}
                                        className="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 px-6 rounded-xl transition-colors text-center"
                                    >
                                        Se connecter
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
