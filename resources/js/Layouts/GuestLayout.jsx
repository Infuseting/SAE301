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
                        src="/images/hero.png"
                        alt={messages.hero_background_alt || "Arrière-plan"}
                        className="h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-emerald-900/20 mix-blend-multiply"></div>
                </div>

                <div className="relative z-10 max-w-lg">
                    <div className="mb-6">
                        <ApplicationLogo className="h-12 w-auto fill-current text-emerald-400" />
                    </div>
                    <blockquote className="text-4xl font-extrabold text-white tracking-tight leading-tight drop-shadow-lg">
                        {messages.reg_title || 'Explorez la France, une balise à la fois.'}
                    </blockquote>
                    <p className="mt-4 text-emerald-100/80 font-medium tracking-wide text-lg border-l-4 border-emerald-500 pl-4">
                        {messages.reg_subtext || 'La plateforme de référence pour tous les passionnés de course d\'orientation.'}
                    </p>
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="flex flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white   relative">
                <div className="absolute top-8 end-8">
                    <LanguageSwitcher />
                </div>
                <div className="absolute top-8 left-8">
                    <Link href="/" className="flex items-center text-important transition-colors">
                        <span className="mr-2">←</span> {messages.back_to_home || "Retour à l'accueil"}
                    </Link>
                </div>
                <div className="mx-auto w-full max-w-sm lg:w-96">
                    <div>
                        <Link href="/" className="flex justify-center lg:justify-start">
                            <ApplicationLogo className="block h-16 w-16 fill-current text-gray-800  hover:text-[#9333ea]  transition-colors" />
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
