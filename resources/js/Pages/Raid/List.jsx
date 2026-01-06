import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, usePage } from '@inertiajs/react';

/**
 * Raid List Component
 * Displays a list of all raids
 */
export default function List({ raids }) {
    const messages = usePage().props.translations?.messages || {};

    /**
     * Format date for display
     * @param {string} dateString - ISO date string
     * @returns {string} Formatted date
     */
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={messages.raids || 'Raids'} />

            {/* Green Header */}
            <div className="bg-green-500 py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <Link href={route('dashboard')} className="text-white hover:text-white/80">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 className="text-2xl font-bold text-white absolute left-1/2 transform -translate-x-1/2">
                            {messages.raids || 'Raids'}
                        </h1>
                        <Link href={route('raids.create')}>
                            <PrimaryButton>
                                {messages.create_raid || 'Créer un Raid'}
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    {raids.length === 0 ? (
                        <div className="bg-white rounded-lg shadow-md p-12 text-center">
                            <p className="text-gray-500 text-lg mb-4">
                                {messages.no_raids || 'Aucun raid disponible'}
                            </p>
                            <Link href={route('raids.create')}>
                                <PrimaryButton>
                                    {messages.create_first_raid || 'Créer le premier raid'}
                                </PrimaryButton>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {raids.map((raid) => (
                                <Link key={raid.id} href={route('raids.show', raid.id)}>
                                    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                                        <img
                                            src={raid.image || '/images/default-raid.jpg'}
                                            alt={raid.name}
                                            className="w-full h-48 object-cover"
                                        />
                                        <div className="p-6">
                                            <h3 className="text-xl font-bold text-gray-900 mb-2">
                                                {raid.name}
                                            </h3>
                                            <p className="text-gray-600 text-sm mb-2">
                                                {raid.address}, {raid.postal_code}
                                            </p>
                                            <p className="text-gray-500 text-sm">
                                                {formatDate(raid.event_start_date)} - {formatDate(raid.event_end_date)}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
