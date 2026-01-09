import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { QrCode, Calendar, MapPin, Users, Download, Printer, CheckCircle, Trophy, User, ArrowLeft } from 'lucide-react';

/**
 * Registration Ticket Component
 * Displays team registration details with QR code for event check-in
 */
export default function RegistrationTicket({ registration, team, race, raid }) {
    const messages = usePage().props.translations?.messages || {};

    const handlePrint = () => {
        window.print();
    };

    const handleDownloadQR = () => {
        if (registration.qr_code_url) {
            const link = document.createElement('a');
            link.href = registration.qr_code_url;
            link.download = `qrcode-team-${team.equ_id}-reg-${registration.reg_id}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    const handleBack = () => {
        window.history.back();
    };

    return (
        <>
            <Head title={`Ticket - ${team.equ_name}`}>
                <style>{`
                    @media print {
                        body * {
                            visibility: hidden;
                        }
                        #qr-code-print-area,
                        #qr-code-print-area * {
                            visibility: visible;
                        }
                        #qr-code-print-area {
                            position: absolute;
                            left: 50%;
                            top: 50%;
                            transform: translate(-50%, -50%);
                        }
                    }
                `}</style>
            </Head>
            
        <AuthenticatedLayout>

            <div className="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Action Buttons */}
                    <div className="flex justify-between items-center gap-3 mb-6 print:hidden">
                        <button
                            onClick={handleBack}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Retour
                        </button>
                        <div className="flex gap-3">
                            <button
                                onClick={handleDownloadQR}
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm"
                            >
                                <Download className="w-4 h-4 mr-2" />
                                Télécharger QR
                            </button>
                            <button
                                onClick={handlePrint}
                                className="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition shadow-md"
                            >
                                <Printer className="w-4 h-4 mr-2" />
                                Imprimer QR Code
                            </button>
                        </div>
                    </div>

                    {/* Main Ticket Card */}
                    <div className="bg-white rounded-2xl shadow-2xl overflow-hidden border-2 border-emerald-100">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-emerald-600 to-emerald-500 px-8 py-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <Trophy className="w-6 h-6" />
                                        <h1 className="text-2xl font-bold">{raid.raid_name}</h1>
                                    </div>
                                    <p className="text-emerald-100 text-sm">{race.race_name}</p>
                                </div>
                                {registration.reg_dossard && (
                                    <div className="text-right">
                                        <div className="text-emerald-100 text-xs font-medium uppercase tracking-wider mb-1">
                                            Dossard
                                        </div>
                                        <div className="text-5xl font-black">
                                            {registration.reg_dossard}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="p-8">
                            {/* QR Code Section */}
                            <div className="flex flex-col md:flex-row gap-8 mb-8">
                                {/* QR Code - Will be printed */}
                                <div className="flex-shrink-0" id="qr-code-print-area">
                                    <div className="bg-gradient-to-br from-emerald-50 to-white p-6 rounded-xl border-2 border-emerald-200 shadow-inner">
                                        {registration.qr_code_url ? (
                                            <div className="text-center">
                                                <img
                                                    src={registration.qr_code_url}
                                                    alt="QR Code"
                                                    className="w-64 h-64 mx-auto"
                                                />
                                                <div className="mt-4 flex items-center justify-center gap-2 text-emerald-700">
                                                    <QrCode className="w-4 h-4" />
                                                    <p className="text-xs font-medium">
                                                        Présentez ce code le jour J
                                                    </p>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="w-64 h-64 flex items-center justify-center text-gray-400">
                                                <div className="text-center">
                                                    <QrCode className="w-16 h-16 mx-auto mb-2" />
                                                    <p className="text-sm">QR Code en cours de génération...</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Team & Race Info */}
                                <div className="flex-1 space-y-6">
                                    {/* Team Info */}
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                            <Users className="w-5 h-5 text-emerald-600" />
                                            Équipe
                                        </h2>
                                        <div className="space-y-3">
                                            <div className="flex items-start gap-3">
                                                {team.equ_image && (
                                                    <img
                                                        src={team.equ_image}
                                                        alt={team.equ_name}
                                                        className="w-12 h-12 rounded-lg object-cover border-2 border-emerald-100"
                                                    />
                                                )}
                                                <div>
                                                    <h3 className="font-bold text-lg text-gray-900">
                                                        {team.equ_name}
                                                    </h3>
                                                    {team.leader && (
                                                        <div className="text-sm text-gray-600 flex items-center gap-1 mt-1">
                                                            <User className="w-3 h-3" />
                                                            <span className="font-medium">Capitaine:</span>
                                                            {team.leader.name}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Team Members */}
                                            {team.members && team.members.length > 0 && (
                                                <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                    <div className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                                        Membres ({team.members.length})
                                                    </div>
                                                    <div className="grid grid-cols-1 gap-2">
                                                        {team.members.map((member) => (
                                                            <div
                                                                key={member.id}
                                                                className="flex items-center justify-between text-sm"
                                                            >
                                                                <span className="font-medium text-gray-700">
                                                                    {member.name}
                                                                </span>
                                                                <span className="text-gray-500 text-xs">
                                                                    {member.email}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Event Details */}
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                            <Calendar className="w-5 h-5 text-emerald-600" />
                                            Détails de l'événement
                                        </h2>
                                        <div className="space-y-3 text-sm">
                                            <div className="flex items-start gap-3">
                                                <Calendar className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <div className="text-gray-500 text-xs font-medium uppercase">
                                                        Date
                                                    </div>
                                                    <div className="font-semibold text-gray-900">
                                                        {new Date(raid.raid_date_start).toLocaleDateString('fr-FR', {
                                                            weekday: 'long',
                                                            year: 'numeric',
                                                            month: 'long',
                                                            day: 'numeric',
                                                        })}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-start gap-3">
                                                <MapPin className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <div className="text-gray-500 text-xs font-medium uppercase">
                                                        Lieu
                                                    </div>
                                                    <div className="font-semibold text-gray-900">
                                                        {raid.raid_city} ({raid.raid_postal_code})
                                                    </div>
                                                </div>
                                            </div>
                                            {race.race_distance && (
                                                <div className="flex items-start gap-3">
                                                    <Trophy className="w-4 h-4 text-gray-400 mt-0.5" />
                                                    <div>
                                                        <div className="text-gray-500 text-xs font-medium uppercase">
                                                            Distance
                                                        </div>
                                                        <div className="font-semibold text-gray-900">
                                                            {race.race_distance} km
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Status Banner */}
                            {registration.is_present && (
                                <div className="bg-emerald-50 border-2 border-emerald-200 rounded-xl p-4">
                                    <div className="flex items-center gap-3 text-emerald-700">
                                        <CheckCircle className="w-6 h-6" />
                                        <div>
                                            <div className="font-bold">Présence confirmée ✓</div>
                                            <div className="text-sm">Votre équipe a été enregistrée sur place</div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Instructions */}
                            <div className="mt-8 bg-amber-50 border-2 border-amber-200 rounded-xl p-6">
                                <h3 className="font-bold text-amber-900 mb-3 flex items-center gap-2">
                                    <QrCode className="w-5 h-5" />
                                    Instructions importantes
                                </h3>
                                <ul className="space-y-2 text-sm text-amber-800">
                                    <li className="flex items-start gap-2">
                                        <span className="font-bold">•</span>
                                        <span>Présentez ce QR code à l'accueil le jour de l'événement</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="font-bold">•</span>
                                        <span>Imprimez ce ticket ou gardez-le sur votre téléphone</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="font-bold">•</span>
                                        <span>Arrivez au moins 30 minutes avant le départ</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="font-bold">•</span>
                                        <span>Tous les membres de l'équipe doivent être présents</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center text-xs text-gray-500">
                            Ticket d'inscription #{registration.reg_id} • Généré le{' '}
                            {new Date().toLocaleDateString('fr-FR')}
                        </div>
                    </div>
                </div>
            </div>

            {/* Print Styles */}
            <style>{`
                @media print {
                    body {
                        background: white !important;
                    }
                    .print\\:hidden {
                        display: none !important;
                    }
                }
            `}</style>
        </AuthenticatedLayout>
        </>
    );
}
