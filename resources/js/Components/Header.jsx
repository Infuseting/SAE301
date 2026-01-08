import { Link, usePage } from "@inertiajs/react";
import ApplicationLogo from "@/Components/ApplicationLogo";
import LanguageSwitcher from "@/Components/LanguageSwitcher";
import ClubsDropdown from "@/Components/ClubsDropdown";
import UserMenu from "@/Components/UserMenu";
import MyRaceButton from "./MyRaceButton";
import RaidButton from "./RaidButton";
import LeaderboardButton from "./LeaderboardButton";
import BurgerMenu from "./BurgerMenu";

import ManagerButton from "./ManagerButton";

/**
 * Header component - Reusable header for all pages
 *
 * @param {boolean} transparent - If true, header has transparent background (for hero sections)
 * @param {string} className - Additional CSS classes
 */
export default function Header({ transparent = false, className = "" }) {
    const { auth } = usePage().props;
    const messages = usePage().props.translations?.messages || {};
    const user = auth?.user;

    const headerClasses = transparent
        ? "absolute top-0 w-full z-20 p-6"
        : "bg-white border-b border-gray-100 shadow-sm";

    const logoClasses = transparent
        ? "h-20 w-auto fill-current text-white"
        : "h-9 w-auto fill-current text-gray-800 hover:text-emerald-600 transition-colors";

    const linkClasses = transparent
        ? "px-4 py-2 text-white hover:text-emerald-400 transition font-medium"
        : "px-4 py-2 text-gray-700 hover:text-emerald-600 transition font-medium";

    const buttonClasses = transparent
        ? "px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition font-bold shadow-lg shadow-emerald-900/20"
        : "px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition font-bold";

    return (
        <header className={`${headerClasses} ${className}`}>
            <div
                className={
                    transparent
                        ? "max-w-7xl mx-auto flex items-center justify-between "
                        : "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
                }
            >
                {!transparent && (
                    <div className="flex h-16 items-center justify-between w-full">
                        <div className="flex-1 flex justify-start">
                            <Link href="/">
                                <ApplicationLogo className={logoClasses} />
                            </Link>
                        </div>

                        <nav className="hidden md:flex flex-none justify-center items-center gap-3">
                            <div className="flex bg-white  p-1">
                                {user && (
                                    <>
                                        <RaidButton />
                                        <MyRaceButton />
                                        <ClubsDropdown />
                                        <LeaderboardButton />
                                    </>
                                )}
                            </div>
                        </nav>

                        <div className=" hidden md:flex flex-1 flex items-center justify-end gap-3">
                            <LanguageSwitcher className="text-gray-700 hover:text-emerald-600 transition" />

                            {user ? (
                                <UserMenu user={user} />
                            ) : (
                                <div className="flex gap-4">
                                    <Link
                                        href={route("login")}
                                        className={linkClasses}
                                    >
                                        {messages.login}
                                    </Link>
                                    <Link
                                        href={route("register")}
                                        className={buttonClasses}
                                    >
                                        {messages.register}
                                    </Link>
                                </div>
                            )}
                        </div>

                        <nav className=" md:hidden flex items-center justify-between gap-6">
                            <BurgerMenu />
                            <LanguageSwitcher className="text-gray-700 hover:text-emerald-600 transition" />

                            {user ? (
                                <UserMenu user={user} className="text-white" />
                            ) : (
                                <div className="flex gap-4">
                                    <Link
                                        href={route("login")}
                                        className={linkClasses}
                                    >
                                        {messages.login}
                                    </Link>
                                    <Link
                                        href={route("register")}
                                        className={buttonClasses}
                                    >
                                        {messages.register}
                                    </Link>
                                </div>
                            )}
                        </nav>
                    </div>
                )}

                {transparent && (
                    <div className="flex items-center justify-between w-full">
                        {/* 1. Section GAUCHE : Logo */}
                        <div className="flex-1 flex  justify-start">
                            <Link href="/" className="flex items-center ">
                                <ApplicationLogo
                                    className={logoClasses}
                                    big={true}
                                />
                            </Link>
                        </div>

                        {/* 2. Section MILIEU : Navigation centrée */}
                        <nav className="hidden md:flex flex-none items-center justify-center">
                            {user && (
                                <div className="flex bg-white backdrop-blur-md  rounded-xl p-1 shadow-lg shadow-emerald-900/20">
                                    <RaidButton />
                                    <MyRaceButton />
                                    <ClubsDropdown />
                                    <LeaderboardButton />
                                </div>
                            )}
                        </nav>

                        {/* 3. Section DROITE : Actions & Langue */}
                        <div className="hidden flex-1 md:flex items-center justify-end gap-6">
                            {" "}
                            <LanguageSwitcher className="text-white hover:text-emerald-400 transition" />
                            {user ? (
                                <UserMenu user={user} className="text-white" />
                            ) : (
                                <div className="flex gap-4">
                                    <Link
                                        href={route("login")}
                                        className={linkClasses}
                                    >
                                        {messages.login}
                                    </Link>
                                    <Link
                                        href={route("register")}
                                        className={buttonClasses}
                                    >
                                        {messages.register}
                                    </Link>
                                </div>
                            )}
                        </div>

                        <nav className=" md:hidden flex items-center justify-between gap-6">
                            <BurgerMenu />
                            <LanguageSwitcher className="text-white hover:text-emerald-400 transition" />

                            {user ? (
                                <UserMenu user={user} className="text-white" />
                            ) : (
                                <div className="flex gap-4">
                                    <Link
                                        href={route("login")}
                                        className={linkClasses}
                                    >
                                        {messages.login}
                                    </Link>
                                    <Link
                                        href={route("register")}
                                        className={buttonClasses}
                                    >
                                        {messages.register}
                                    </Link>
                                </div>
                            )}
                        </nav>
                    </div>
                )}
            </div>
        </header>
    );
}
