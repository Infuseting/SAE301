import { useState, useRef, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";

export default function ClubsDropdown() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);
    const messages = usePage().props.translations?.messages || {};
    const user = usePage().props.auth?.user;

    // Get user's clubs (clubs where user is a member)
    const userClubs = user?.clubs || [];
    const hasClubs = userClubs.length > 0;

    // Check if user can create clubs (must be adherent or admin)
    const userRoles = user?.roles || [];
    const canCreateClub = user && userRoles.some(role => 
        ['adherent', 'admin'].includes(role.name || role)
    );

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target)
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    return (
        <div className="relative text-center" ref={dropdownRef}>
            {/* Dropdown Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="inline-flex items-center px-3 py-2 border border-transparent text-md leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={1.5}
                    stroke="currentColor"
                    className="w-5 h-5 mr-2"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                    />
                </svg>
                {messages['navbar.clubs'] || "Clubs"}
                <svg
                    className={`ml-2 h-4 w-4 transition-transform ${
                        isOpen ? "rotate-180" : ""
                    }`}
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                    />
                </svg>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div className="md:absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div className="py-1">
                        {/* My Clubs Section - Only for logged users */}
                        {user && hasClubs && (
                            <>
                                <div className="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {messages['navbar.my_clubs'] || "My Clubs"}
                                </div>
                                {userClubs.map((club) => (
                                    <Link
                                        key={club.club_id}
                                        href={`/clubs/${club.club_id}`}
                                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 text-center hover:text-emerald-600 transition"
                                        onClick={() => setIsOpen(false)}
                                    >
                                        <div className="flex items-center">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                strokeWidth={1.5}
                                                stroke="currentColor"
                                                className="w-4 h-4 mr-2"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                                />
                                            </svg>
                                            {club.club_name}
                                        </div>
                                    </Link>
                                ))}
                                <div className="border-t border-gray-100 my-1"></div>
                            </>
                        )}

                        {/* Browse All Clubs - Visible for everyone */}
                        <Link
                            href="/clubs"
                            className="block text-center px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition"
                            onClick={() => setIsOpen(false)}
                        >
                            <div className="flex items-center">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={1.5}
                                    stroke="currentColor"
                                    className="w-4 h-4 mr-2"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"
                                    />
                                </svg>
                                {messages['navbar.browse_all_clubs'] || "Browse All Clubs"}
                            </div>
                        </Link>

                        {/* Create Club - Only for authenticated users with adherent/admin role */}
                        {canCreateClub && (
                            <Link
                                href="/clubs/create"
                                className="block px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50 font-medium transition"
                                onClick={() => setIsOpen(false)}
                            >
                                <div className="flex items-center">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth={2}
                                        stroke="currentColor"
                                        className="w-4 h-4 mr-2"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15"
                                        />
                                    </svg>
                                    {messages['navbar.create_club'] || "Create Club"}
                                </div>
                            </Link>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
