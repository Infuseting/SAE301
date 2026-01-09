import React from "react";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import { Link } from "@inertiajs/react";
import { MdDateRange, MdLocationOn } from "react-icons/md";
import { GiMountainRoad } from "react-icons/gi";

function MyRaidCard({ raid }) {
    const formatDate = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleDateString("fr-FR", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    };

    return (
        <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border-l-4 border-emerald-500 flex flex-col">
            {/* Image du Raid */}
            <div className="h-40 w-full overflow-hidden bg-gray-200">
                <img
                    src={raid.image || "/images/default-raid.jpg"}
                    alt={raid.name}
                    className="w-full h-full object-cover"
                />
            </div>

            <div className="p-6 flex-grow flex flex-col">
                <h3 className="text-lg font-bold text-gray-900 mb-2">
                    {raid.name}
                </h3>

                <p className="text-gray-600 text-sm mb-4 line-clamp-2 flex-grow">
                    {raid.description}
                </p>

                {/* Date Info */}
                <div className="flex items-center gap-2 text-gray-700 text-sm mb-2">
                    <MdDateRange className="text-emerald-600 w-4 h-4" />
                    <span>
                        Du {formatDate(raid.date_start)} au{" "}
                        {formatDate(raid.date_end)}
                    </span>
                </div>

                {/* Ville/Lieu Info (si disponible dans vos données) */}
                {raid.city && (
                    <div className="flex items-center gap-2 text-gray-700 text-sm mb-4">
                        <MdLocationOn className="text-red-500 w-4 h-4" />
                        <span>
                            {raid.city} ({raid.postal_code})
                        </span>
                    </div>
                )}

                {/* Status Badge */}
                <div className="flex gap-2 mb-4">
                    <span className="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">
                        ✓ Participant
                    </span>
                </div>

                {/* Action Button */}
                <div className="mt-auto">
                    <Link
                        href={route("raids.show", { id: raid.id })}
                        className="w-full inline-block px-4 py-2 bg-emerald-600 text-white text-center rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
                    >
                        Voir les détails du raid
                    </Link>
                </div>
            </div>
        </div>
    );
}

export default function MyRaidIndex({ raids = [] }) {
    const isEmpty = raids.length === 0;

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Header />

            <main className="flex-grow pt-24 pb-20">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                    {/* Page Title Section */}
                    <div className="mb-12">
                        <div className="flex items-center gap-3 mb-2">
                            <div className="w-1 h-8 bg-emerald-600 rounded"></div>
                            <h1 className="text-3xl sm:text-4xl font-bold text-gray-900">
                                Mes Raids
                            </h1>
                        </div>
                        <p className="text-gray-600 ml-4">
                            Retrouvez l'ensemble des raids auxquels vous
                            participez ou avez participé
                        </p>
                    </div>

                    {/* Empty State */}
                    {isEmpty ? (
                        <div className="bg-white rounded-lg shadow-sm p-12 text-center border border-gray-100">
                            <GiMountainRoad className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-600 text-lg font-medium">
                                Aucun raid à afficher pour le moment.
                            </p>
                            <p className="text-gray-500 text-sm mt-2">
                                Parcourez les raids à venir et inscrivez votre
                                équipe !
                            </p>
                            <Link
                                href={route("raids.index")}
                                className="mt-6 inline-block text-emerald-600 font-semibold hover:underline"
                            >
                                Explorer les raids disponibles →
                            </Link>
                        </div>
                    ) : (
                        <section>
                            <div className="mb-6 flex justify-between items-end">
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                        <span className="inline-block w-1 h-6 bg-emerald-500 rounded"></span>
                                        Historique de mes raids
                                    </h2>
                                    <p className="text-gray-600 text-sm ml-3 mt-1">
                                        {raids.length} raid(s) enregistré(s)
                                    </p>
                                </div>
                            </div>

                            {/* Grid Layout */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                {raids.map((raid) => (
                                    <MyRaidCard key={raid.id} raid={raid} />
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
