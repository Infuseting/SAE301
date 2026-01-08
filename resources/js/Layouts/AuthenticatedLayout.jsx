import ProfileCompletionModal from '@/Components/ProfileCompletionModal';
import LicenceRequiredModal from '@/Components/LicenceRequiredModal';
import Header from '@/Components/Header';
import Footer from '@/Components/Footer';
import { usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const { auth, requiresLicenceUpdate } = usePage().props;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Licence Required Modal - Shows first and blocks access */}
            {user && <LicenceRequiredModal show={requiresLicenceUpdate} />}
            
            {/* Profile Completion Modal - Shows after licence is valid */}
            {user && !requiresLicenceUpdate && <ProfileCompletionModal />}

            <Header />

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    );
}
