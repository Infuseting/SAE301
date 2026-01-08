import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClubForm from '@/Components/ClubForm';
import ClubMembersList from '@/Components/ClubMembersList';

export default function Edit({ club }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title={`${messages.edit_club} - ${club.club_name}`} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-5xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            {messages.edit_club}: {club.club_name}
                        </h1>
                        <p className="text-gray-600">
                            {messages.edit_club_subtitle}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Club Details Form */}
                        <div className="lg:col-span-2">
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                                <h2 className="text-xl font-bold text-gray-900 mb-6">{messages.club_details}</h2>
                                <ClubForm
                                    club={club}
                                    submitRoute="clubs.update"
                                    submitLabel={messages.save}
                                />
                            </div>
                        </div>

                        {/* Member Management */}
                        <div className="lg:col-span-1">
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
                                <h2 className="text-xl font-bold text-gray-900 mb-6">{messages.manage_members}</h2>
                                <ClubMembersList
                                    members={club.members || []}
                                    pendingRequests={club.pending_requests || []}
                                    isManager={true}
                                    clubId={club.club_id}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
