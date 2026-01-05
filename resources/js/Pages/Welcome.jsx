import { Head, Link, usePage } from '@inertiajs/react';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import UserMenu from '@/Components/UserMenu';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ProfileCompletionModal from '@/Components/ProfileCompletionModal';

export default function Welcome({ auth }) {
    const messages = usePage().props.translations?.messages || {};

    const upcomingRaces = [
        {
            id: 1,
            title: "La Boussole de la Forêt",
            date: "12 Oct 2026",
            location: "Fontainebleau, FR",
            type: "Moyenne Distance",
            image: "https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
        },
        {
            id: 2,
            title: "Sprint Urbain de Paris",
            date: "25 Oct 2026",
            location: "Paris, FR",
            type: "Sprint",
            image: "https://images.unsplash.com/photo-1552674605-5d226a5beb38?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
        },
        {
            id: 3,
            title: "Nocturne des Vosges",
            date: "05 Nov 2026",
            location: "Gerardmer, FR",
            type: "Nuit",
            image: "https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
        }
    ];

    return (
        <>
            <Head title={messages.welcome_title || 'Accueil'} />

            {auth.user && <ProfileCompletionModal />}

            <div className="min-h-screen bg-gray-50 text-gray-900 font-sans ">
                {/* Hero Section */}
                <div className="relative h-screen max-h-[900px]">
                    <div className="absolute inset-0">
                        <img
                            src="/images/hero.png"
                            alt="Orienteering Runner"
                            className="w-full h-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-r from-black/70 to-black/30 mix-blend-multiply" />
                    </div>

                    {/* Navigation Overlay */}
                    <div className="absolute top-0 w-full z-20 p-6">
                        <header className="max-w-7xl mx-auto flex items-center justify-between">
                            <ApplicationLogo className="h-12 w-auto fill-current text-white" />

                            <nav className="flex items-center gap-6">
                                <LanguageSwitcher className="text-white hover:text-emerald-400 transition" />

                                {auth.user ? (
                                    <UserMenu user={auth.user} className="text-white" />
                                ) : (
                                    <div className="flex gap-4">
                                        <Link
                                            href={route('login')}
                                            className="px-4 py-2 text-white hover:text-emerald-400 transition font-medium cursor-pointer"
                                        >
                                            {messages.login || 'Se connecter'}
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition font-bold shadow-lg shadow-emerald-900/20 cursor-pointer"
                                        >
                                            {messages.register || 'S\'inscrire'}
                                        </Link>
                                    </div>
                                )}
                            </nav>
                        </header>
                    </div>

                    {/* Hero Content */}
                    <div className="relative z-10 h-full flex flex-col items-center justify-center px-4 text-center">
                        <h1 className="text-5xl md:text-7xl font-extrabold text-white tracking-tight mb-6 drop-shadow-lg">
                            <span className="block">{messages.find_next_race || "Trouvez votre prochaine"}</span>
                            <span className="block text-emerald-400">{messages.orienteering || "Course d'Orientation"}</span>
                        </h1>
                        <p className="mt-4 max-w-2xl text-xl text-gray-200 mb-10 drop-shadow-md">
                            {messages.hero_subtitle || "Explorez des centaines de cartes et de parcours à travers la France. Du sprint urbain à l'ultra-longue distance en montagne."}
                        </p>

                        {/* Search Bar Component */}
                        <div className="w-full max-w-4xl bg-white rounded-2xl p-2 shadow-2xl flex flex-col md:flex-row gap-2">
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">{messages.search_where || "Où ?"}</label>
                                <input
                                    type="text"
                                    placeholder={messages.search_placeholder_where || "Ville, Région..."}
                                    className="w-full bg-transparent border-none p-0 text-gray-800 placeholder-gray-400 focus:ring-0 font-medium"
                                />
                            </div>
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">{messages.search_when || "Quand ?"}</label>
                                <input
                                    type="text"
                                    placeholder={messages.search_placeholder_when || "Toutes les dates"}
                                    className="w-full bg-transparent border-none p-0 text-gray-800 placeholder-gray-400 focus:ring-0 font-medium"
                                />
                            </div>
                            <div className="flex-1 px-4 py-3">
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">{messages.search_type || "Type"}</label>
                                <select className="w-full bg-transparent border-none p-0 text-gray-800 focus:ring-0 font-medium cursor-pointer">
                                    <option>{messages.search_option_all_types || "Tous les types"}</option>
                                    <option>Sprint</option>
                                    <option>Moyenne Distance</option>
                                    <option>Longue Distance</option>
                                    <option>Relais</option>
                                    <option>Nuit</option>
                                </select>
                            </div>
                            <button className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-8 py-4 font-bold transition flex items-center justify-center gap-2 md:w-auto w-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-5 h-5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                {messages.search_button || "Rechercher"}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Upcoming Races Section */}
                <section className="py-24 bg-white">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="flex justify-between items-end mb-12">
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900">{messages.upcoming_races_title || "Prochaines Courses"}</h2>
                                <p className="mt-2 text-gray-600">{messages.upcoming_races_subtitle || "Ne manquez pas les événements à venir près de chez vous."}</p>
                            </div>
                            <a href="#" className="hidden md:flex text-emerald-600 font-bold items-center hover:underline">
                                {messages.view_calendar || "Voir tout le calendrier"}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4 ml-1">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {upcomingRaces.map((race) => (
                                <div key={race.id} className="group relative bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition duration-300">
                                    <div className="aspect-[4/3] overflow-hidden">
                                        <img
                                            src={race.image}
                                            alt={race.title}
                                            className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                        />
                                    </div>
                                    <div className="p-6">
                                        <div className="flex justify-between items-start mb-4">
                                            <span className="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                                {race.type}
                                            </span>
                                            <span className="flex items-center text-gray-500 text-sm font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-1">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0h18M5.25 12h13.5h-13.5Zm0 3.75h13.5h-13.5Z" />
                                                </svg>
                                                {race.date}
                                            </span>
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-emerald-600 transition">
                                            {race.title}
                                        </h3>
                                        <div className="flex items-center text-gray-500 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4 mr-1">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            {race.location}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-8 text-center md:hidden">
                            <a href="#" className="inline-flex text-emerald-600 font-bold items-center hover:underline">
                                {messages.view_calendar || "Voir tout le calendrier"}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4 ml-1">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </section>

                {/* How it Works */}
                <section className="py-24 bg-gray-50">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="text-center max-w-3xl mx-auto mb-16">
                            <h2 className="text-3xl font-bold text-gray-900 mb-4">{messages.how_it_works_title || "Comment ça marche ?"}</h2>
                            <p className="text-gray-600 text-lg">{messages.how_it_works_subtitle || "Rejoignez la plus grande communauté de course d'orientation en France en quelques étapes."}</p>
                        </div>

                        <div className="grid md:grid-cols-3 gap-12">
                            <div className="text-center">
                                <div className="bg-white w-20 h-20 mx-auto rounded-3xl shadow-lg flex items-center justify-center mb-6 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-10 h-10">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">{messages.step_1_title || "1. Trouvez une course"}</h3>
                                <p className="text-gray-500">{messages.step_1_desc || "Utilisez nos filtres avancés pour trouver l'épreuve qui correspond à votre niveau et vos envies."}</p>
                            </div>
                            <div className="text-center">
                                <div className="bg-white w-20 h-20 mx-auto rounded-3xl shadow-lg flex items-center justify-center mb-6 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-10 h-10">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">{messages.step_2_title || "2. Inscrivez-vous"}</h3>
                                <p className="text-gray-500">{messages.step_2_desc || "Créez votre compte, gérez vos licences et inscrivez-vous en quelques clics."}</p>
                            </div>
                            <div className="text-center">
                                <div className="bg-white w-20 h-20 mx-auto rounded-3xl shadow-lg flex items-center justify-center mb-6 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-10 h-10">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">{messages.step_3_title || "3. Courez !"}</h3>
                                <p className="text-gray-500">{messages.step_3_desc || "Participez à l'événement, suivez vos résultats et comparez vos performances."}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <footer className="bg-gray-900 text-white py-12 border-t border-gray-800">
                    <div className="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-8">
                        <div>
                            <ApplicationLogo className="h-8 w-auto fill-current text-emerald-500 mb-4" />
                            <p className="text-gray-400 text-sm">
                                La référence pour la course d'orientation en France.
                            </p>
                        </div>
                        <div>
                            <h4 className="font-bold mb-4">Navigation</h4>
                            <ul className="space-y-2 text-gray-400 text-sm">
                                <li><a href="#" className="hover:text-emerald-400">Calendrier</a></li>
                                <li><a href="#" className="hover:text-emerald-400">Clubs</a></li>
                                <li><a href="#" className="hover:text-emerald-400">Résultats</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-bold mb-4">Légal</h4>
                            <ul className="space-y-2 text-gray-400 text-sm">
                                <li><a href="#" className="hover:text-emerald-400">Mentions légales</a></li>
                                <li><a href="#" className="hover:text-emerald-400">Confidentialité</a></li>
                                <li><a href="#" className="hover:text-emerald-400">CGU</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-bold mb-4">Contact</h4>
                            <p className="text-gray-400 text-sm">contact@sae301.fr</p>
                        </div>
                    </div>
                    <div className="max-w-7xl mx-auto px-6 mt-12 pt-8 border-t border-gray-800 text-center text-gray-500 text-sm">
                        &copy; {new Date().getFullYear()} SAE301. Tous droits réservés.
                    </div>
                </footer>
            </div>
        </>
    );
}
