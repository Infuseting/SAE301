import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import { Link } from "@inertiajs/react";
import { Calendar, MapPin, Users, Clock, CheckCircle2, AlertCircle } from "lucide-react";

/**
 * Card displaying a race registration.
 */
function RegistrationCard({ registration }) {
    const { race, team, status, registered_at } = registration;

    if (!race) return null;

    const getStatusBadge = () => {
        switch (status) {
            case 'confirmed':
                return { bg: 'bg-emerald-100', text: 'text-emerald-700', icon: CheckCircle2, label: 'Confirmée' };
            case 'pending':
                return { bg: 'bg-amber-100', text: 'text-amber-700', icon: Clock, label: 'En attente' };
            default:
                return { bg: 'bg-slate-100', text: 'text-slate-700', icon: AlertCircle, label: status };
        }
    };

    const getRaceStatusBadge = () => {
        switch (race.status) {
            case 'completed':
                return { bg: 'bg-slate-500', label: 'Terminée' };
            case 'ongoing':
                return { bg: 'bg-emerald-500', label: 'En cours' };
            default:
                return { bg: 'bg-blue-500', label: 'À venir' };
        }
    };

    const statusBadge = getStatusBadge();
    const raceStatus = getRaceStatusBadge();
    const StatusIcon = statusBadge.icon;

    return (
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
            {/* Race image or gradient */}
            <div className="relative h-32 bg-gradient-to-br from-blue-600 to-blue-800">
                {race.image && (
                    <img
                        src={`/storage/${race.image}`}
                        alt={race.name}
                        className="w-full h-full object-cover"
                    />
                )}
                {/* Race status badge */}
                <div className={`absolute top-3 left-3 px-3 py-1 ${raceStatus.bg} text-white text-xs font-bold rounded-full uppercase tracking-wider`}>
                    {raceStatus.label}
                </div>
            </div>

            {/* Content */}
            <div className="p-5">
                {/* Race name */}
                <h3 className="text-lg font-bold text-slate-800 mb-2 line-clamp-1">
                    {race.name}
                </h3>

                {/* Race info */}
                <div className="space-y-2 mb-4">
                    <div className="flex items-center gap-2 text-sm text-slate-500">
                        <Calendar className="h-4 w-4 text-slate-400" />
                        <span>
                            {race.date_start ? new Date(race.date_start).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            }) : 'Date à définir'}
                        </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-slate-500">
                        <MapPin className="h-4 w-4 text-slate-400" />
                        <span className="line-clamp-1">{race.location}</span>
                    </div>
                    {team && (
                        <div className="flex items-center gap-2 text-sm text-slate-500">
                            <Users className="h-4 w-4 text-slate-400" />
                            <span>{team.name}</span>
                        </div>
                    )}
                </div>

                {/* Registration status */}
                <div className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold ${statusBadge.bg} ${statusBadge.text}`}>
                    <StatusIcon className="h-3.5 w-3.5" />
                    Inscription {statusBadge.label.toLowerCase()}
                </div>

                {/* Action button */}
                <Link
                    href={route("races.show", { id: race.id })}
                    className="mt-4 block w-full text-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-colors uppercase tracking-wider"
                >
                    Voir la course
                </Link>
            </div>
        </div>
    );
}

export default function MyRaceIndex({ registrations }) {
    return (
        <div className="min-h-screen flex flex-col bg-slate-50">
            <Header />
            <div className="mt-20 flex-grow">
                <main className="max-w-6xl mx-auto px-6 py-10">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-black text-slate-800 uppercase tracking-tight">
                            Mes Inscriptions
                        </h1>
                        <p className="text-slate-500 mt-2">
                            Retrouvez toutes vos inscriptions aux courses
                        </p>
                    </div>

                    {/* Registrations grid */}
                    {registrations && registrations.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {registrations.map((registration) => (
                                <RegistrationCard
                                    key={registration.registration_id}
                                    registration={registration}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white rounded-2xl shadow-lg p-12 text-center">
                            <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                                <Users className="h-8 w-8 text-slate-400" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-700 mb-2">
                                Aucune inscription
                            </h3>
                            <p className="text-slate-500 mb-6">
                                Vous n'êtes inscrit à aucune course pour le moment.
                            </p>
                            <Link
                                href={route("raids.index")}
                                className="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors uppercase tracking-wider text-sm"
                            >
                                Découvrir les courses
                            </Link>
                        </div>
                    )}
                </main>
            </div>
            <Footer />
        </div>
    );
}
