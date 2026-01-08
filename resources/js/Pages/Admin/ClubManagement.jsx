import React from "react";
import Header from "@/Components/Header";
import Footer from "@/Components/Footer";
import ClubCard from "@/Pages/Admin/ClubCard";

/**
 * ClubManagement page component for admin area.
 * Displays all clubs that the authenticated user manages (as leader or manager).
 * Accessible to users with the responsable-club role.
 *
 * @param {Object} props - Component props
 * @param {Array} props.clubs - Array of club objects managed by the user
 * @returns {JSX.Element} Club management page
 */
export default function ClubManagement({ clubs }) {
    return (
        <div className="min-h-screen">
            <Header />
            <div className="container mx-auto px-4 py-8">
                <h1 className="text-2xl font-bold mb-4">Club Management</h1>

                {clubs.length === 0 ? (
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth={1.5}
                            stroke="currentColor"
                            className="w-16 h-16 mx-auto text-gray-400 mb-4"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                            />
                        </svg>
                        <h3 className="text-xl font-semibold text-gray-900 mb-2">
                            No Clubs Found
                        </h3>
                        <p className="text-gray-500">
                            You don't have any clubs to manage yet.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {clubs.map((club) => (
                            <ClubCard key={club.club_id} club={club} />
                        ))}
                    </div>
                )}
            </div>
            <Footer />
        </div>
    );
}
