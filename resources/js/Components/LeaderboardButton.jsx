import { useState, useRef } from "react";
import { Link, usePage } from "@inertiajs/react";
import { FaTrophy, FaMedal } from "react-icons/fa";
import { LuLayoutGrid } from "react-icons/lu";

/**
 * LeaderboardButton component - Dropdown navigation for leaderboard pages
 * Provides access to public leaderboard and personal leaderboard (my-leaderboard)
 */
export default function LeaderboardButton() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);
    const messages = usePage().props.translations?.messages || {};

    return (
        <div className="relative text-center" ref={dropdownRef}>
            {/* Dropdown Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="inline-flex items-center px-3 py-2 border border-transparent text-md leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
            >
                <FaTrophy className="h-5 w-5 mr-1" />
                {messages.leaderboard || "Classement"}
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
                <div className="md:absolute right-0 mt-2 w-56 rounded-md md:shadow-lg shadow-sm bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div className="py-1">
                        <Link
                            href={route("leaderboard.index")}
                            className="block px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                        >
                            <LuLayoutGrid className="inline mr-2 mb-1" />
                            {messages.public_leaderboard || "Classement public"}
                        </Link>
                        <Link
                            href={route("my-leaderboard.index")}
                            className="block px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <FaMedal className="inline mr-2 mb-1" />
                            {messages.my_leaderboard || "Mon classement"}
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
