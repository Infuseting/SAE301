import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import UserAvatar from '@/Components/UserAvatar';

export default function Show({ user }) {
    const messages = usePage().props.translations?.messages || {};

    if (!user.is_public) {
        return (
            <AuthenticatedLayout>
                <Head title={messages.private_profile || 'Profil Privé'} />
                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="bg-white p-4 shadow-sm border border-gray-200 sm:rounded-lg sm:p-8 text-center">
                            <UserAvatar
                                user={user}
                                className="h-20 w-20 mx-auto mb-4 opacity-50 text-2xl"
                            />
                            <h3 className="text-lg font-medium text-gray-900">{user.name}</h3>
                            <p className="mt-2 text-gray-600">{messages.profile_is_private || 'Ce profil est privé.'}</p>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    const formattedDate = new Date(user.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });

    return (
        <AuthenticatedLayout>
            <Head title={(messages.profile_of || 'Profil de :name').replace(':name', user.name)} />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm border border-gray-200 sm:rounded-lg">
                        <div className="flex flex-col items-center sm:flex-row sm:items-start sm:space-x-8">
                            <div className="flex-shrink-0">
                                <UserAvatar
                                    user={user}
                                    className="h-32 w-32 border-4 border-gray-100 text-5xl"
                                />
                            </div>
                            <div className="mt-4 sm:mt-0 flex-1 text-center sm:text-left">
                                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                    <div>
                                        <h3 className="text-2xl font-bold text-gray-900">{user.name}</h3>
                                        <p className="text-sm text-gray-500">{(messages.member_since || 'Membre depuis le :date').replace(':date', formattedDate)}</p>
                                    </div>
                                    {usePage().props.auth.user && usePage().props.auth.user.id === user.id && (
                                        <Link
                                            href={route('profile.edit')}
                                            className="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-2">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                            {messages.edit_profile || 'Modifier'}
                                        </Link>
                                    )}
                                </div>

                                {user.description && (
                                    <div className="mt-4 prose prose-sm max-w-none text-gray-600">
                                        <p>{user.description}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
