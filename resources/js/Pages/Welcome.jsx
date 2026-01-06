import { Head, Link, usePage } from "@inertiajs/react";
import LanguageSwitcher from "@/Components/LanguageSwitcher";
import UserMenu from "@/Components/UserMenu";
import ApplicationLogo from "@/Components/ApplicationLogo";
import ProfileCompletionModal from "@/Components/ProfileCompletionModal";
import Footer from "@/Components/Footer";

export default function Welcome({ auth }) {
    const messages = usePage().props.translations?.messages || {};

    const upcomingRaces = [
        {
            id: 1,
            title: "La Boussole de la Forêt",
            date: "12 Oct 2026",
            location: "Fontainebleau, FR",
            type: "Moyenne Distance",
            image: "https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80",
        },
        {
            id: 2,
            title: "Sprint Urbain de Paris",
            date: "25 Oct 2026",
            location: "Paris, FR",
            type: "Sprint",
            image: "https://images.unsplash.com/photo-1552674605-5d226a5beb38?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80",
        },
        {
            id: 3,
            title: "Nocturne des Vosges",
            date: "05 Nov 2026",
            location: "Gerardmer, FR",
            type: "Nuit",
            image: "https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80",
        },
    ];
    const [startDate, setStartDate] = useState(null);
    const [distanceRange, setDistanceRange] = useState([0, 50]);

    return (
        <>
            <Head title={messages.welcome_title} />

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
                                    <UserMenu
                                        user={auth.user}
                                        className="text-white"
                                    />
                                ) : (
                                    <div className="flex gap-4">
                                        <Link
                                            href={route("login")}
                                            className="px-4 py-2 text-white hover:text-emerald-400 transition font-medium cursor-pointer"
                                        >
                                            {messages.login}
                                        </Link>
                                        <Link
                                            href={route("register")}
                                            className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition font-bold shadow-lg shadow-emerald-900/20 cursor-pointer"
                                        >
                                            {messages.register}
                                        </Link>
                                    </div>
                                )}
                            </nav>
                        </header>
                    </div>

                    {/* Hero Content */}
                    <div className="relative z-10 h-full flex flex-col items-center justify-center px-4 text-center">
                        <h1 className="text-5xl md:text-7xl font-extrabold text-white tracking-tight mb-6 drop-shadow-lg">
                            <span className="block">
                                {messages.find_next_race}
                            </span>
                            <span className="block text-emerald-400">
                                {messages.orienteering}
                            </span>
                        </h1>
                        <p className="mt-4 max-w-2xl text-xl text-gray-200 mb-10 drop-shadow-md">
                            {messages.hero_subtitle}
                        </p>

                        {/* Search Bar Component */}
                        <div className="w-full max-w-5xl bg-white rounded-2xl p-2 shadow-2xl flex flex-col md:flex-row gap-2">
                            {/* Where */}
                            <div className="flex-[2] px-6 py-4 border-b md:border-b-0 md:border-r border-gray-100 relative group transition-colors hover:bg-gray-50/50 rounded-l-2xl">
                                <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                                    {messages.search_where}
                                </label>
                                <div className="flex items-center">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={2}
                                        stroke="currentColor"
                                        className="w-5 h-5 text-gray-300 mr-3 group-hover:text-emerald-500 transition-colors"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                        />
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                                        />
                                    </svg>
                                    <div className="w-full">
                                        <div className="flex justify-between items-baseline mb-2">
                                            <input
                                                type="text"
                                                placeholder={
                                                    messages.search_placeholder_where
                                                }
                                                className="w-full bg-transparent border-none p-0 text-gray-900 placeholder-gray-400 focus:ring-0 font-semibold text-lg"
                                            />
                                            <span className="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full whitespace-nowrap ml-2">
                                                {distanceRange[0]}km -{" "}
                                                {distanceRange[1]}km
                                            </span>
                                        </div>
                                        <div className="px-1">
                                            <Slider
                                                range
                                                min={0}
                                                max={1000}
                                                step={10}
                                                defaultValue={[0, 50]}
                                                value={distanceRange}
                                                onChange={(value) =>
                                                    setDistanceRange(value)
                                                }
                                                trackStyle={[
                                                    {
                                                        backgroundColor:
                                                            "#10b981",
                                                        height: 4,
                                                    },
                                                ]}
                                                handleStyle={[
                                                    {
                                                        borderColor: "#10b981",
                                                        backgroundColor: "#fff",
                                                        opacity: 1,
                                                        height: 16,
                                                        width: 16,
                                                        marginTop: -6,
                                                        boxShadow:
                                                            "0 2px 4px rgba(0,0,0,0.1)",
                                                    },
                                                    {
                                                        borderColor: "#10b981",
                                                        backgroundColor: "#fff",
                                                        opacity: 1,
                                                        height: 16,
                                                        width: 16,
                                                        marginTop: -6,
                                                        boxShadow:
                                                            "0 2px 4px rgba(0,0,0,0.1)",
                                                    },
                                                ]}
                                                railStyle={{
                                                    backgroundColor: "#f3f4f6",
                                                    height: 4,
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* When */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100 flex items-center gap-3">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                    className="w-6 h-6 text-gray-400"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0h18M5.25 12h13.5h-13.5Zm0 3.75h13.5h-13.5Z"
                                    />
                                </svg>
                                <div className="w-full">
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                        {messages.search_when}
                                    </label>
                                    <DatePicker
                                        selected={startDate}
                                        onChange={(date) => setStartDate(date)}
                                        placeholderText={
                                            messages.search_placeholder_when
                                        }
                                        className="w-full bg-transparent border-none p-0 text-gray-800 placeholder-gray-400 focus:ring-0 font-medium"
                                        dateFormat="dd/MM/yyyy"
                                        popperContainer={({ children }) =>
                                            createPortal(
                                                children,
                                                document.body
                                            )
                                        }
                                        popperClassName="!z-[100]"
                                    />
                                </div>
                            </div>

                            {/* Type (Loisir/Compétition) */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100">
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                    {messages.search_category}
                                </label>
                                <select className="w-full bg-transparent border-none p-0 text-gray-800 focus:ring-0 font-medium cursor-pointer">
                                    <option value="all">{messages.all}</option>
                                    <option value="loisir">
                                        {messages.leisure}
                                    </option>
                                    <option value="competition">
                                        {messages.competition}
                                    </option>
                                </select>
                            </div>

                            {/* Age */}
                            <div className="flex-1 px-4 py-3">
                                <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">
                                    {messages.search_age}
                                </label>
                                <select className="w-full bg-transparent border-none p-0 text-gray-800 focus:ring-0 font-medium cursor-pointer">
                                    <option value="">
                                        {messages.all_ages}
                                    </option>
                                    {Object.entries(
                                        messages.age_categories || {}
                                    ).map(([key, label]) => (
                                        <option key={key} value={key}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <button className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-8 py-4 font-bold transition flex items-center justify-center gap-2 md:w-auto w-full">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={2.5}
                                    stroke="currentColor"
                                    className="w-5 h-5"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                                    />
                                </svg>
                                {messages.search_button}
                            </button>
                        </div>
                    </div>
                </div>

                {/* How it Works (Moved Up) */}
                <section className="py-24 bg-white border-b border-gray-100">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="text-center max-w-3xl mx-auto mb-16">
                            <h2 className="text-3xl font-bold text-gray-900 mb-4">
                                {messages.how_it_works_title}
                            </h2>
                            <p className="text-gray-600 text-lg">
                                {messages.how_it_works_subtitle}
                            </p>
                        </div>

                        <div className="grid md:grid-cols-3 gap-12">
                            <div className="text-center">
                                <div className="bg-emerald-50 w-20 h-20 mx-auto rounded-3xl shadow-sm flex items-center justify-center mb-6 text-emerald-600">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="w-10 h-10"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                                        />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    {messages.step_1_title}
                                </h3>
                                <p className="text-gray-500">
                                    {messages.step_1_desc}
                                </p>
                            </div>
                            <div className="text-center">
                                <div className="bg-emerald-50 w-20 h-20 mx-auto rounded-3xl shadow-sm flex items-center justify-center mb-6 text-emerald-600">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="w-10 h-10"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"
                                        />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    {messages.step_2_title}
                                </h3>
                                <p className="text-gray-500">
                                    {messages.step_2_desc}
                                </p>
                            </div>
                            <div className="text-center">
                                <div className="bg-emerald-50 w-20 h-20 mx-auto rounded-3xl shadow-sm flex items-center justify-center mb-6 text-emerald-600">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={1.5}
                                        stroke="currentColor"
                                        className="w-10 h-10"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"
                                        />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">
                                    {messages.step_3_title}
                                </h3>
                                <p className="text-gray-500">
                                    {messages.step_3_desc}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Raids (Previously Upcoming Races) */}
                <section className="py-24 bg-gray-50">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="flex justify-between items-end mb-12">
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900">
                                    {messages.upcoming_raids_title}
                                </h2>
                                <p className="mt-2 text-gray-600">
                                    {messages.upcoming_raids_subtitle}
                                </p>
                            </div>
                            <a
                                href="#"
                                className="hidden md:flex text-emerald-600 font-bold items-center hover:underline"
                            >
                                {messages.view_calendar}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={2}
                                    stroke="currentColor"
                                    className="w-4 h-4 ml-1"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                    />
                                </svg>
                            </a>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {(messages.upcoming_races_list || []).map(
                                (race) => (
                                    <div
                                        key={race.id}
                                        className="group relative bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition duration-300"
                                    >
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
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        strokeWidth={1.5}
                                                        stroke="currentColor"
                                                        className="w-4 h-4 mr-1"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0h18M5.25 12h13.5h-13.5Zm0 3.75h13.5h-13.5Z"
                                                        />
                                                    </svg>
                                                    {race.date}
                                                </span>
                                            </div>
                                            <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-emerald-600 transition">
                                                {race.title}
                                            </h3>
                                            <div className="flex items-center text-gray-500 text-sm">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    strokeWidth={1.5}
                                                    stroke="currentColor"
                                                    className="w-4 h-4 mr-1"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                                    />
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                                                    />
                                                </svg>
                                                {race.location}
                                            </div>
                                        </div>
                                    </div>
                                )
                            )}
                        </div>

                        <div className="mt-8 text-center md:hidden">
                            <a
                                href="#"
                                className="inline-flex text-emerald-600 font-bold items-center hover:underline"
                            >
                                {messages.view_calendar}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={2}
                                    stroke="currentColor"
                                    className="w-4 h-4 ml-1"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                    />
                                </svg>
                            </a>
                        </div>
                    </div>
                </section>
                <Footer />
            </div>
        </>
    );
}
