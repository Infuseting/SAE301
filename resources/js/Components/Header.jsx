import React from "react";
import ApplicationLogo from "@/Components/ApplicationLogo";
import LanguageSwitcher from "@/Components/LanguageSwitcher";
import UserMenu from "@/Components/UserMenu";
import { Head, Link, usePage } from "@inertiajs/react";

export default function Header({ auth }) {
    const messages = usePage().props.translations?.messages || {};

    return (
        <div className="absolute top-0 w-full z-20 p-6 ">
            <header className="max-w-7xl mx-auto flex items-center justify-between">
                <ApplicationLogo className="h-12 w-auto fill-current text-white" />

                <nav className="flex items-center gap-6">
                    <LanguageSwitcher className="text-white hover:text-emerald-400 transition" />

                    {auth.user ? (
                        <UserMenu user={auth.user} className="text-white" />
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
    );
}
