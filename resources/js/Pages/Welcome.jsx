import { Head, Link, usePage, router } from "@inertiajs/react";
import { useState } from "react";
import { createPortal } from "react-dom";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import ProfileCompletionModal from "@/Components/ProfileCompletionModal";

export default function Welcome({ auth, upcomingRaids, ageCategories }) {
    const messages = usePage().props.translations?.messages || {};
    const [location, setLocation] = useState("");
    const [locationType, setLocationType] = useState("city");
    const [startDate, setStartDate] = useState(null);
    const [category, setCategory] = useState("all");
    const [ageCategory, setAgeCategory] = useState("");

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

                    <Header transparent />

                    {/* Hero Content */}
                    <div className="relative z-10 h-full flex flex-col items-center justify-center px-4 pt-24 lg:pt-0 text-center">
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
                        <div className="w-full max-w-5xl bg-white rounded-xl p-2 shadow-2xl flex flex-col md:flex-row gap-2">
                            {/* Where */}
                            <div className="flex-[2] px-5 py-3 border-b md:border-b-0 md:border-r border-gray-100 relative group transition-colors hover:bg-gray-50/40 rounded-l-xl">
                                <label className="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                                    {messages.search_where}
                                </label>
                                <div className="flex items-center gap-2">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={2}
                                        stroke="currentColor"
                                        className="w-4 h-4 text-gray-300 group-hover:text-emerald-500 transition-colors flex-shrink-0"
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
                                    <div className="w-full flex gap-2.5 items-end">
                                        <div className="flex-1">
                                            <select 
                                                value={locationType}
                                                onChange={(e) => setLocationType(e.target.value)}
                                                className="w-full bg-transparent border-none p-0 text-gray-900 focus:ring-0 font-medium cursor-pointer text-sm"
                                            >
                                                <option value="city">Ville</option>
                                                <option value="department">Département</option>
                                                <option value="region">Région</option>
                                            </select>
                                        </div>
                                        <div className="flex-1">
                                            <input
                                                type="text"
                                                placeholder={
                                                    messages.search_placeholder_where
                                                }
                                                value={location}
                                                onChange={(e) => setLocation(e.target.value)}
                                                className="w-full bg-transparent border-none p-0 text-gray-900 placeholder-gray-400 focus:ring-0 font-semibold text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* When */}
                            <div className="flex-1 px-4 py-3 border-b md:border-b-0 md:border-r border-gray-100 flex items-center gap-2">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                    className="w-4 h-4 text-gray-400 flex-shrink-0"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0h18M5.25 12h13.5h-13.5Zm0 3.75h13.5h-13.5Z"
                                    />
                                </svg>
                                <div className="w-full min-w-0">
                                    <label className="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">
                                        {messages.search_when}
                                    </label>
                                    <DatePicker
                                        selected={startDate}
                                        onChange={(date) => setStartDate(date)}
                                        placeholderText={
                                            messages.search_placeholder_when
                                        }
                                        className="w-full bg-transparent border-none p-0 text-gray-800 placeholder-gray-400 focus:ring-0 font-medium text-sm"
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
                                <label className="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">
                                    {messages.search_category}
                                </label>
                                <select 
                                    value={category}
                                    onChange={(e) => setCategory(e.target.value)}
                                    className="w-full bg-transparent border-none p-0 text-gray-800 focus:ring-0 font-medium cursor-pointer text-sm">
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
                                <label className="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">
                                    {messages.search_age}
                                </label>
                                <select 
                                    value={ageCategory}
                                    onChange={(e) => setAgeCategory(e.target.value)}
                                    className="w-full bg-transparent border-none p-0 text-gray-800 focus:ring-0 font-medium cursor-pointer text-sm">
                                    <option value="">
                                        {messages.all_ages}
                                    </option>
                                    {(ageCategories || []).map((cat) => (
                                        <option key={cat.id} value={cat.nom}>
                                            {cat.nom}
                                        </option>
                                    ))}
                                </select>
                            </div>
 
                            <button
                                onClick={() => {
                                    const params = new URLSearchParams();
                                    if (location) {
                                        params.append("location", location);
                                        params.append("location_type", locationType);
                                    }
                                    if (startDate) params.append("date", startDate.toISOString().split('T')[0]);
                                    if (category !== "all") params.append("category", category);
                                    if (ageCategory) params.append("age_category", ageCategory);
                                    
                                    router.visit(route("raids.index") + (params.toString() ? `?${params.toString()}` : ""));
                                }}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-6 py-3 font-bold transition flex items-center justify-center gap-2 md:w-auto w-full text-sm"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={2.5}
                                    stroke="currentColor"
                                    className="w-4 h-4"
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
                            <Link
                                href={route("raids.index")}
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
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {(upcomingRaids || []).map(
                                (race) => (
                                    <Link
                                        key={race.id}
                                        href={route("raids.show", race.id)}
                                        className="group relative bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition duration-300 block"
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
                                    </Link>
                                )
                            )}
                        </div>

                        <div className="mt-8 text-center md:hidden">
                            <Link
                                href={route("raids.index")}
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
                            </Link>
                        </div>
                    </div>
                </section>

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

                <Footer />
            </div>
        </>
    );
}
