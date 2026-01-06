import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, usePage } from '@inertiajs/react';

/**
 * Raid Detail Component
 * Displays raid information and associated courses
 */
export default function Index({ raid, courses = [] }) {
    const messages = usePage().props.translations?.messages || {};
    const user = usePage().props.auth.user;

    /**
     * Format date range for display
     * @param {string} startDate - Start date string
     * @param {string} endDate - End date string
     * @returns {string} Formatted date range
     */
    const formatDateRange = (startDate, endDate) => {
        const start = new Date(startDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
        const end = new Date(endDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
        return `du ${start} au ${end}`;
    };

    /**
     * Format single date with time
     * @param {string} dateString - ISO date string
     * @returns {string} Formatted date
     */
    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Check if current user is the raid organizer
    const isOrganizer = user?.id === raid?.organizer_id;

    return (
        <AuthenticatedLayout>
            <Head title={raid?.raid_name || messages.raid || 'Raid'} />

            {/* Green Header */}
            <div className="bg-green-500 py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-center relative">
                        <Link href={route('home')} className="text-white hover:text-white/80 absolute left-0">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 className="text-2xl font-bold text-white">
                            {raid?.raid_name || messages.raid || 'Raid'}
                        </h1>
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Raid Header Card */}
                    <div className="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
                            {/* Left Column - Raid Information */}
                            <div className="lg:col-span-2 space-y-4">
                                <h1 className="text-2xl font-bold text-gray-900 mb-4">
                                    {raid?.raid_name}
                                </h1>

                                <div className="space-y-2 text-sm">
                                    <p className="text-gray-700">
                                        {raid?.raid_street}, {raid?.raid_city} {raid?.raid_postal_code}
                                    </p>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 mt-4">
                                        <div>
                                            <span className="font-semibold">Contact :</span>
                                            <p className="text-gray-700">{raid?.raid_contact || 'N/A'}</p>
                                        </div>

                                        <div>
                                            <span className="font-semibold">Numéro :</span>
                                            <p className="text-gray-700">{raid?.raid_number || 'N/A'}</p>
                                        </div>

                                        <div>
                                            <span className="font-semibold">Site Web :</span>
                                            <p className="text-gray-700">
                                                {raid?.raid_site_url ? (
                                                    <a href={raid.raid_site_url} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:underline">
                                                        {raid.raid_site_url}
                                                    </a>
                                                ) : 'N/A'}
                                            </p>
                                        </div>

                                        <div>
                                            <span className="font-semibold">Dates :</span>
                                            <p className="text-gray-700">
                                                {formatDateRange(raid?.event_start_date, raid?.event_end_date)}
                                            </p>
                                        </div>
                                    </div>

                                    <p className="text-xs text-gray-500 italic mt-4">
                                        Inscriptions possibles {formatDateRange(raid?.registration_start_date, raid?.registration_end_date)}
                                    </p>
                                </div>
                            </div>

                            {/* Right Column - Image */}
                            <div className="lg:col-span-1">
                                <img
                                    src={raid?.image || '/images/default-raid.jpg'}
                                    alt={raid?.name}
                                    className="w-full h-48 lg:h-full object-cover rounded-lg"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Courses Section */}
                    <div className="space-y-6">
                        <h2 className="text-2xl font-bold text-gray-900">Les courses :</h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {courses.map((course) => (
                                <div key={course.id} className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                                    {/* Course Image */}
                                    <img
                                        src={course.image || '/images/default-course.jpg'}
                                        alt={course.name}
                                        className="w-full h-48 object-cover"
                                    />

                                    {/* Course Details */}
                                    <div className="p-4 space-y-2">
                                        <h3 className="text-lg font-semibold text-gray-900">{course.name}</h3>

                                        <div className="space-y-1 text-sm text-gray-600">
                                            <p>
                                                <span className="font-medium">Responsable :</span> {course.organizer_name}
                                            </p>
                                            <p>
                                                <span className="font-medium">Difficulté :</span> {course.difficulty}
                                            </p>
                                            <p>
                                                <span className="font-medium">Date :</span> {formatDateTime(course.start_date)}
                                            </p>
                                            <p>
                                                <span className="font-medium">Heure :</span> {new Date(course.start_date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                                            </p>
                                            <p className="text-gray-500">À partir de {course.min_age} ans</p>
                                        </div>

                                        <div className="pt-4">
                                            <SecondaryButton className="w-full">
                                                Découvrir
                                            </SecondaryButton>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Add Course Button - Only for organizer */}
                        {isOrganizer && (
                            <div className="flex items-center gap-4 bg-white rounded-lg shadow-md p-6">
                                <PrimaryButton className="flex items-center gap-2">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Ajouter une course
                                </PrimaryButton>
                                <p className="text-sm text-red-600">
                                    Seulement pour le responsable de ce raid
                                </p>
                            </div>
                        )}

                        {courses.length === 0 && (
                            <div className="bg-white rounded-lg shadow-md p-12 text-center">
                                <p className="text-gray-500 text-lg mb-4">
                                    Aucune course disponible pour ce raid
                                </p>
                                {isOrganizer && (
                                    <PrimaryButton>
                                        Ajouter la première course
                                    </PrimaryButton>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
