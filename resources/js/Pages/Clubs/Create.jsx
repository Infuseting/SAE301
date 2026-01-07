import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClubForm from '@/Components/ClubForm';

export default function Create() {
    const messages = usePage().props.translations?.messages || {};

    return (
        <AuthenticatedLayout>
            <Head title={messages.create_club} />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="max-w-3xl mx-auto px-6">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">{messages.create_club}</h1>
                        <p className="text-gray-600">
                            {messages.create_club_subtitle}
                        </p>
                    </div>

                    {/* Info Alert */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <div className="flex">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6 text-blue-600 mr-3 flex-shrink-0">
                                <path strokeLinecap="round" strokeLinejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <div>
                                <h3 className="font-semibold text-blue-900 mb-1">{messages.approval_required}</h3>
                                <p className="text-blue-800 text-sm">
                                    {messages.approval_required_description}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <ClubForm
                            submitRoute="clubs.store"
                            submitLabel={messages.create_club}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
