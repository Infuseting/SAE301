import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const messages = usePage().props.translations?.messages || {};
    
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                    {messages.dashboard || 'Dashboard'}
                </h2>
            }
        >
            <Head title={messages.dashboard || 'Dashboard'} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] sm:rounded-lg dark:bg-[#18181b]">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            {messages.logged_in || "You're logged in!"}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
