import { usePage } from '@inertiajs/react';
import Header from '@/Components/Header';
import Footer from '@/Components/Footer';
import ProfileCompletionModal from '@/Components/ProfileCompletionModal';

/**
 * PublicLayout - Layout for public pages with Header and Footer
 */
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50">
            {auth.user && <ProfileCompletionModal />}

            <Header />

            <main>{children}</main>

            <Footer />
        </div>
    );
}
