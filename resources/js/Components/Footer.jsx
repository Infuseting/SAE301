import { Link, usePage } from "@inertiajs/react";
import ApplicationLogo from "@/Components/ApplicationLogo";

/**
 * Footer component - Reusable footer for all pages
 */
export default function Footer() {
    const messages = usePage().props.translations?.messages || {};

    return (
        <footer className="bg-gray-900 text-white py-12 border-t border-gray-800">
            <div className="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-8">
                {/* Brand */}
                <div>
                    <ApplicationLogo className="h-8 w-auto fill-current text-emerald-500 mb-4" />
                    <p className="text-gray-400 text-sm">
                        {messages.footer_tagline}
                    </p>
                </div>

                {/* Navigation */}
                <div>
                    <h4 className="font-bold mb-4">
                        {messages.footer_navigation}
                    </h4>
                    <ul className="space-y-2 text-gray-400 text-sm">
                        <li>
                            <Link
                                href="/"
                                className="hover:text-emerald-400 transition"
                            >
                                {messages.welcome_title || "Accueil"}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={route("clubs.index")}
                                className="hover:text-emerald-400 transition"
                            >
                                {messages.footer_links_clubs}
                            </Link>
                        </li>
                    </ul>
                </div>

                {/* Legal */}
                <div>
                    <h4 className="font-bold mb-4">{messages.footer_legal}</h4>
                    <ul className="space-y-2 text-gray-400 text-sm">
                        <li>
                            <a
                                href="#"
                                className="hover:text-emerald-400 transition"
                            >
                                {messages.footer_links_legal_notice}
                            </a>
                        </li>
                        <li>
                            <a
                                href="#"
                                className="hover:text-emerald-400 transition"
                            >
                                {messages.footer_links_privacy}
                            </a>
                        </li>
                        <li>
                            <a
                                href="#"
                                className="hover:text-emerald-400 transition"
                            >
                                {messages.footer_links_terms}
                            </a>
                        </li>
                    </ul>
                </div>

                {/* Contact */}
                <div>
                    <h4 className="font-bold mb-4">
                        {messages.footer_contact}
                    </h4>
                    <p className="text-gray-400 text-sm">contact@sae301.fr</p>
                </div>
            </div>

            {/* Copyright */}
            <div className="max-w-7xl mx-auto px-6 mt-12 pt-8 border-t border-gray-800 text-center text-gray-500 text-sm">
                &copy; {new Date().getFullYear()} SAE301.{" "}
                {messages.footer_copyright}
            </div>
        </footer>
    );
}
