import { useState, useRef, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { FaRegCompass } from "react-icons/fa6";
import { FaTrophy } from "react-icons/fa";
import { LuLayoutGrid } from "react-icons/lu";
export default function RaidButton() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);
    const messages = usePage().props.translations?.messages || {};
    const user = usePage().props.auth.user;

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
                className="inline-flex items-center  px-3 py-2 border border-transparent text-md leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
            >
                <FaRegCompass className="h-5 w-5 mr-1" />
                {messages['navbar.raids'] || "Raids"}
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
                            href={route("raids.index")}
                            className="block px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                        >
                            <LuLayoutGrid className="inline mr-2 mb-1" />
                            {messages['navbar.view_all_raids'] || "Voir tous les raids"}
                        </Link>
                        {user && (
                            <Link
                                href={route("myraid.index")}
                                className="block px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <FaTrophy className="inline mr-2 mb-1" />
                                {messages['navbar.my_raids'] || "Mes raids"}
                            </Link>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
