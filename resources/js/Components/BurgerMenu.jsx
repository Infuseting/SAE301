import RaidButton from "./RaidButton";
import MyRaceButton from "./MyRaceButton";
import ClubsDropdown from "./ClubsDropdown";
import LeaderboardButton from "./LeaderboardButton";
import { useState, useRef } from "react";
import { usePage } from "@inertiajs/react";

export default function BurgerMenu() {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);
    const messages = usePage().props.translations?.messages || {};
    const user = usePage().props.auth.user;
    return (
        <div className="relative" ref={dropdownRef}>
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
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                    />
                </svg>

                {messages.menu || "Menu"}
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
                <div className="absolute  mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div className="py-1"></div>
                    <div className=" flex flex-col items-center gap-2">
                        <RaidButton />
                        <MyRaceButton />
                        <ClubsDropdown />
                        <LeaderboardButton />
                    </div>
                </div>
            )}
        </div>
    );
}
