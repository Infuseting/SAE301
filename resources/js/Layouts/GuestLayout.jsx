import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
            {/* Left Side - Visual */}
            <div className="relative hidden lg:flex flex-col justify-end p-12 bg-gray-900 overflow-hidden">
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
                        "La sécurité et l'efficacité au cœur de votre expérience."
                    </blockquote>
                    <p className="mt-4 text-gray-300 font-medium tracking-wide">SAE R.3.01 - Secure Framework</p>
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="flex flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white relative">
                <div className="absolute top-8 left-8">
                    <Link href="/" className="flex items-center text-purple-600 hover:text-purple-800 transition-colors">
                        <span className="mr-2">←</span> Retour à l'accueil
                    </Link>
                </div>
                <div className="mx-auto w-full max-w-sm lg:w-96">
                    <div>
                        <Link href="/" className="flex justify-center lg:justify-start">
                            <ApplicationLogo className="h-16 w-16 text-purple-600" />
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
