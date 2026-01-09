import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import { Link, router, usePage } from "@inertiajs/react";
import { RiRunLine } from "react-icons/ri";
import { MdDateRange } from "react-icons/md";
import { useState } from "react";

function formatTime(seconds) {
    if (!seconds) return "N/A";
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.round(seconds % 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    }
    return `${minutes}m ${secs}s`;
}

function MyRaceCard({ race, isRegistered = false }) {
    const formatDate = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleDateString("fr-FR", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    };

    return (
        <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border-l-4 border-blue-500">
            {race.image && (
                <div className="h-40 w-full overflow-hidden bg-gray-200">
                    <img
                        src={race.image}
                        alt={race.name}
                        className="w-full h-full object-cover"
                    />
                </div>
            )}

            <div className="p-6">
                <h3 className="text-lg font-bold text-gray-900 mb-2">
                    {race.name}
                </h3>

                <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                    {race.description}
                </p>

                {/* Date Info */}
                <div className="flex items-center gap-2 text-gray-700 text-sm mb-4">
                    <MdDateRange className="w-4 h-4" />
                    <span>
                        {formatDate(race.date_start)} -{" "}
                        {formatDate(race.date_end)}
                    </span>
                </div>

                {/* Leaderboard Stats for Historical Races */}
                {!isRegistered && race.leaderboard && (
                    <div className="mb-4 p-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200">
                        <p className="text-xs font-bold text-purple-900 mb-3 uppercase tracking-wide">
                            📊 Vos résultats
                        </p>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-white rounded p-2 text-center">
                                <p className="text-gray-600 text-xs">Temps</p>
                                <p className="font-bold text-purple-600 text-sm">
                                    {formatTime(race.leaderboard.temps)}
                                </p>
                            </div>
                            <div className="bg-white rounded p-2 text-center">
                                <p className="text-gray-600 text-xs">
                                    Temps final
                                </p>
                                <p className="font-bold text-purple-600 text-sm">
                                    {formatTime(race.leaderboard.temps_final)}
                                </p>
                            </div>
                            <div className="bg-white rounded p-2 text-center">
                                <p className="text-gray-600 text-xs">Malus</p>
                                <p className="font-bold text-red-600 text-sm">
                                    {formatTime(race.leaderboard.malus)}
                                </p>
                            </div>
                            <div className="bg-white rounded p-2 text-center">
                                <p className="text-gray-600 text-xs">Points</p>
                                <p className="font-bold text-green-600 text-sm">
                                    {race.leaderboard.points || 0}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Team Infos */}
                {race.team && (
                    <div className="mb-4 p-3 bg-blue-50 rounded-md">
                        <p className="text-xs font-semibold text-blue-700 mb-1">
                            Mon équipe
                        </p>
                        <span className="font-medium text-blue-900">
                            {race.team.name}
                        </span>
                    </div>
                )}

                {/* Status Badge */}
                <div className="flex items-center justify-between mb-4">
                    <div className="flex gap-2">
                        {isRegistered && (
                            <span className="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                ✓ Inscrit
                            </span>
                        )}
                        {race.is_open && !race.leaderboard && (
                            <span className="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">
                                Ouvert
                            </span>
                        )}
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-2">
                    <Link
                        href={route("races.show", { id: race.id })}
                        className="flex-1 inline-block px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                    >
                        Voir les détails
                    </Link>
                    {isRegistered && (
                        <Link
                            href={route("teams.registration.ticket", { 
                                team: race.team?.id, 
                                registration: race.registration_id 
                            })}
                            className="flex-1 inline-block px-4 py-2 bg-green-600 text-white text-center rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                        >
                            Mon QR Code
                        </Link>
                    )} 
                </div>
            </div>
        </div>
    );
}

export default function MyRaceIndex({ races = [], registers = [], currentPeriod = 'all' }) {
    const isEmpty = races.length === 0 && registers.length === 0;
    const messages = usePage().props.translations?.messages || {};
    
    const periods = [
        { value: 'all', label: messages.filter_all || 'Tous' },
        { value: '1month', label: messages.filter_1month || 'Dernier mois' },
        { value: '6months', label: messages.filter_6months || '6 mois' },
        { value: '1year', label: messages.filter_1year || '1 an' },
    ];

    const handlePeriodChange = (period) => {
        router.post(route('myrace.index'), { period }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Header />

            <main className="flex-grow pt-5 pb-20">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                    {/* Page Title */}
                    <div className="mb-8">
                        <div className="flex items-center gap-3 mb-2">
                            <div className="w-1 h-8 bg-blue-600 rounded"></div>
                            <h1 className="text-3xl sm:text-4xl font-bold text-gray-900">
                                Mes Courses
                            </h1>
                        </div>
                        <p className="text-gray-600 ml-4">
                            Consultez votre historique et vos inscriptions
                        </p>
                    </div>

                    {/* Period Filter */}
                    <div className="mb-8">
                        <div className="bg-white rounded-lg shadow-sm p-4">
                            <div className="flex flex-wrap gap-2">
                                {periods.map((period) => (
                                    <button
                                        key={period.value}
                                        onClick={() => handlePeriodChange(period.value)}
                                        className={`px-4 py-2 rounded-lg font-medium transition-all ${
                                            currentPeriod === period.value
                                                ? 'bg-blue-600 text-white shadow-md'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        }`}
                                    >
                                        {period.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Empty State */}
                    {isEmpty && (
                        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
                            <RiRunLine className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-600 text-lg">
                                Vous n'avez ni courses complétées ni
                                inscriptions actuelles.
                            </p>
                            <p className="text-gray-500 text-sm mt-2">
                                Découvrez les courses disponibles et
                                inscrivez-vous !
                            </p>
                        </div>
                    )}

                    {/* Historique de courses */}
                    {races.length > 0 && (
                        <section className="mb-12">
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    <span className="inline-block w-1 h-6 bg-purple-600 rounded"></span>
                                    Historique de mes courses
                                </h2>
                                <p className="text-gray-600 text-sm ml-3 mt-1">
                                    {races.length} course(s) complétée(s)
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {races.map((race) => (
                                    <MyRaceCard
                                        key={race.id}
                                        race={race}
                                        isRegistered={false}
                                    />
                                ))}
                            </div>
                        </section>
                    )}

                    {/* Courses inscrites */}
                    {registers.length > 0 && (
                        <section>
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    <span className="inline-block w-1 h-6 bg-green-600 rounded"></span>
                                    Mes inscriptions
                                </h2>
                                <p className="text-gray-600 text-sm ml-3 mt-1">
                                    {registers.length} course(s) en attente ou
                                    en cours
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {registers.map((register) => (
                                    <MyRaceCard
                                        key={register.id}
                                        race={register}
                                        isRegistered={true}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </main>

            <Footer />
        </div>
    );
}
