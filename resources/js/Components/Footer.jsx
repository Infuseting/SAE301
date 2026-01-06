import ApplicationLogo from "./ApplicationLogo";

export default function Footer() {
    return (
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
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                Calendrier
                            </a>
                        </li>
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                Clubs
                            </a>
                        </li>
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                Résultats
                            </a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 className="font-bold mb-4">Légal</h4>
                    <ul className="space-y-2 text-gray-400 text-sm">
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                Mentions légales
                            </a>
                        </li>
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                Confidentialité
                            </a>
                        </li>
                        <li>
                            <a href="#" className="hover:text-emerald-400">
                                CGU
                            </a>
                        </li>
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
    );
}
