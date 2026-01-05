import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const locale = usePage().props.locale || 'en';
    const messages = usePage().props.translations?.messages || {};
    return (
        <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
            {/* Left Side - Visual */}
            <div className="relative hidden lg:flex flex-col justify-end p-12 bg-[#18181b] overflow-hidden">
                <div className="absolute inset-0">
                    <img
                        src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop"
                        alt="Background"
                        className="h-full w-full object-cover opacity-80"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                </div>

                <div className="relative z-10 max-w-lg">
                    <blockquote className="text-3xl font-bold text-white tracking-tight">
                        {messages.security_efficiency || 'Security and efficiency at the heart of your experience.'}
                    </blockquote>
                    <p className="mt-4 text-gray-300 font-medium tracking-wide">SAE 3.01 - Secure Framework</p>
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="flex flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:shadow-[0px_10px_30px_rgba(147,51,234,0.06)] dark:bg-[#18181b] relative">
                <div className="absolute top-8 end-8">
                    <LanguageSwitcher />
                </div>
                <div className="absolute top-8 left-8">
                    <Link href="/" className="flex items-center text-[#9333ea] hover:text-[#7a2ce6] transition-colors">
                        <span className="mr-2">←</span> {messages.back_to_home || 'Back to home'}
                    </Link>
                </div>
                <div className="mx-auto w-full max-w-sm lg:w-96">
                    <div>
                        <Link href="/" className="flex justify-center lg:justify-start">
                            <ApplicationLogo className="block h-16 w-16 fill-current text-gray-800 dark:text-gray-100 hover:text-[#9333ea] dark:hover:text-[#9333ea] transition-colors" />
                        </Link>
                    </div>

                    <div className="mt-8">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
