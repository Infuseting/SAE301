import { Head, Link, useForm } from '@inertiajs/react';

export default function AcceptInvitation({ invitation, team }) {
    const { post, processing } = useForm();

    const handleAccept = () => {
        post(`/invitations/accept/${invitation.token}`);
    };

    return (
        <>
            <Head title="Accepter l'invitation" />
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
                    <h1 className="text-2xl font-bold mb-4">Invitation d'équipe</h1>
                    <p className="text-gray-600 mb-6">
                        <strong>{invitation.inviterName}</strong> vous invite à rejoindre l'équipe <strong>{team.name}</strong>.
                    </p>
                    <div className="flex gap-3 justify-center">
                        <Link
                            href="/"
                            className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                        >
                            Refuser
                        </Link>
                        <button
                            onClick={handleAccept}
                            disabled={processing}
                            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? '...' : 'Accepter'}
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
